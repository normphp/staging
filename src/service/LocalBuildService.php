<?php
/**
 * @Author: pizepei
 * @ProductName: PhpStorm
 * @Created: 2019/7/4 21:45 $
 * @title 本地部署
 * @explain 类的说明
 */

namespace normphp\staging\service;

use normphp\staging\App;
use normphpCore\encryption\aes\Prpcrypt;
use normphpCore\encryption\SHA1;

/**
 * 搜索控制器文件、搜索菜单文件、搜索权限文件
 * 处理生成控制器人口文件
 * 处理生成菜单缓存文件(无固定模板 具体的格式自行设计)
 * 处理生成权限缓存文件
 * Class LocalBuildService
 * @package normphp\staging\service
 */
class LocalBuildService
{

    /**
     * LocalDeployServic constructor.
     */
    public function __construct()
    {

    }

    /**
     * @Author pizepei
     * @Created 2019/7/4 21:51
     * @title  从远处配置中心获取配置
     * @explain 一般是方法功能说明、逻辑说明、注意事项等。
     * @param array $data
     * @return array|mixed
     * @throws \Exception
     */
    public function getConfigCenter(array $data)
    {
        $Prpcrypt = new Prpcrypt(\Deploy::INITIALIZE['appSecret']);
        $encrypt_msg = $Prpcrypt->encrypt(json_encode($data),\Deploy::INITIALIZE['appid'],true);
        if(empty($encrypt_msg)){
            throw new \Exception('初始化配置失败：encrypt',10001);
        }
        # 准备签名
        $nonce = Helper()->str()->int_rand(10);
        $timestamp = time();
        $SHA1 = new SHA1();
        $signature = $SHA1->getSHA1(\Deploy::INITIALIZE['token'],$timestamp,$nonce, $encrypt_msg);
        if(!$signature){ throw new \Exception('初始化配置失败：signature',10002);}
        $postData =  [
            'domain'            =>$_SERVER['HTTP_HOST'],
            'nonce'             =>$nonce,
            'timestamp'         =>$timestamp,
            'signature'         =>$signature,
            'encrypt_msg'       =>$encrypt_msg,
        ];
        if (isset(\Deploy::INITIALIZE['versions']) &&  \Deploy::INITIALIZE['versions']==='V2'){
            $rws  = Helper()->httpRequest(\Deploy::INITIALIZE['configCenter'].'service-config/'.\Deploy::INITIALIZE['appid'].'.json',Helper::init()->json_encode($postData),empty(\Deploy::INITIALIZE['hostDomain'])?[]:['header'=>['Host:'.\Deploy::INITIALIZE['hostDomain']]]);
        }else{
            $rws  = Helper()->httpRequest(\Deploy::INITIALIZE['configCenter'].'service-config/'.\Deploy::INITIALIZE['appid'],Helper::init()->json_encode($postData),empty(\Deploy::INITIALIZE['hostDomain'])?[]:['header'=>['Host:'.\Deploy::INITIALIZE['hostDomain']]]);
        }
        if ($rws['RequestInfo']['http_code'] !== 200){
            throw new \Exception('初始化配置失败：请求配置中心失败',10004);
        }
        if (Helper()->init()->is_empty($rws,'body')){
            throw new \Exception('初始化配置失败：请求配置中心成功就行body失败',10005);
        }
        $body =  Helper()->init()->json_decode($rws['body']);
        if (Helper()->init()->is_empty($body,'data')){
            throw new \Exception($body['msg'],10005);
        }
        if ($body['code'] !==200){
            throw new \Exception('初始化配置失败：'.$body['msg'],10005);
        }
        $body = $body['data'];
        /**
         * 获取配置解密
         */
        $signature = $SHA1->getSHA1(\Deploy::INITIALIZE['token'],$body['timestamp'],$body['nonce'], $body['encrypt_msg']);
        if(!$signature){ throw new \Exception('初始化配置失败：signature',10013);}
        $msg = $Prpcrypt->decrypt($body['encrypt_msg']);
        if(empty($msg))
        {
            $msg = $Prpcrypt->decrypt($body['encrypt_msg']);
        }
        if(empty($msg))
        {
            throw new \Exception('初始化配置失败：解密错误',10009);
        }
        /**
         * 判断appid 和域名
         */
        $result = json_decode($msg[1],true);
        if(time() - $result['time'] > 120)
        {
            throw new \Exception('初始化配置失败：数据过期',10012);
        }
        if($msg[2] !== \Deploy::INITIALIZE['appid'] || $result['appid'] !==\Deploy::INITIALIZE['appid'] ||$result['domain'] !==$_SERVER['HTTP_HOST'] || $data['ProcurementType'] !== $result['ProcurementType'])
        {
            throw new \Exception('初始化配置失败：appid or domain 不匹配',10010);
        }
        /**
         * 解析数据
         * 写入配置
         * 结束
         */
        return $result??[];
    }


    /**
     * @Author 皮泽培
     * @Created 2019/11/14 16:13
     * @param App $App
     * @param array $param
     * @explain 规划为应用控制器全部由此方法创建不在记录在git中，因此在开发模式下在进入路由前执行此方法动态生成控制器
     *          同时开发模式下可能响应时间会更长
     * @title  控制器初始化
     * @throws \Exception
     */
    public static function cliInitDeploy(App $App,array $param)
    {
        # 获取控制器文件路径
        Helper()->getFilePathData($App->DOCUMENT_ROOT.'vendor',$pathData,'.php','namespaceControllerPath.json');
        $path = [];
        $baseAuthGroup = [];
        $constraint = [];
        foreach($pathData as &$value){
            # 处理包信息
            $packageInfo = json_decode($value['packageInfo'],true);
            if (empty($packageInfo)){continue;}
            $packageName = $packageInfo['name'];
            $packageAuthor = $packageInfo['author'];

            # 基础许可证权限注册（每个包都可注册一个或者多个）但是不可重复
            if (!is_array($packageInfo['baseAuthGroup'])) echo PHP_EOL.'baseAuthGroup 格式错误:'.$value['path'].PHP_EOL;
            $baseAuthGroupStr = '';
            foreach ($packageInfo['baseAuthGroup'] as $psk=>$pvalue){
                if (isset($baseAuthGroup[$psk]) && $baseAuthGroup[$psk]['packageName'] !==$packageName){
                    echo PHP_EOL.'基础许可证权限注册冲突'.PHP_EOL."apth:".$value['path'].PHP_EOL."baseAuthGroup:".$psk.PHP_EOL."source:".$baseAuthGroup[$psk]['name'].PHP_EOL;
                    continue;
                }
                $pvalue['packageName'] = $packageName;
                $baseAuthGroup[$psk] = $pvalue;
                $baseAuthGroupStr .= $psk.':'.$pvalue['name'].',';
            }
            $baseAuthGroupStr = rtrim($baseAuthGroupStr,',');
            # 清除../   替换  /  \  .php  和src  获取基础控制器的路径地址
            $baseControl = str_replace(['.php','/','..\\','../'],['','\\','',''],$value['path']);
            # 获取基础控制器的命名空间信息
            $useNamespace = '';
            Helper()->getUseNamespace($App->DOCUMENT_ROOT,$value['path'],$useNamespace);
            # 获取基础控制器的信息
            $reflection  = new \ReflectionClass($useNamespace);
            # 过滤非控制器类文件
            $controller_info = $reflection->hasConstant('CONTROLLER_INFO');
            if(!$controller_info){  continue;}
            $controllerInfo = $reflection->getConstant('CONTROLLER_INFO');

            # 通过 CONTROLLER_INFO['namespace'] 和 CONTROLLER_INFO['basePath'] 确定是否是有效的基础控制器信息
            if (empty($controllerInfo['basePath']) || empty($controllerInfo['title'])){continue;}
            # 支持在部署配置中设置需要排除的包控制器
            if (in_array($controllerInfo['namespace'],\Deploy::EXCLUDE_PACKAGE)){continue;}

            # 基础控制器类名
            $classBasicsExplode = explode('\\',$useNamespace);
            $classBasicsName = end($classBasicsExplode);
            # 判断是否有className
            if(!isset($controllerInfo['className']) && empty($controllerInfo['className'])){
                $controllerInfo['className'] = str_replace(['Basics'],[''],$classBasicsName);
            }
            # 由于由于命名空间不确定是否是app 所以参数来获取拼接
            $controllerInfo['namespace'] = $controllerInfo['namespace'] ==''?$App->__APP__:$App->__APP__.'\\'.$controllerInfo['namespace'];
            # 通过CONTROLLER_INFO['namespace']判断是否已经有门面控制器如果有就不重复参加（是否支持强制重新构建？）
            # 1、判断是否已经存在
            $controllerInfoPath = str_replace(['\\'],[DIRECTORY_SEPARATOR],$controllerInfo['namespace']);
            $controllerPath = $App->DOCUMENT_ROOT.$controllerInfoPath.DIRECTORY_SEPARATOR.$controllerInfo['className'].'.php';
            $controllerDir = $App->DOCUMENT_ROOT.$controllerInfoPath.DIRECTORY_SEPARATOR;
            if ( !isset($param['force']) || $param['force']!==true){
                # 文件存在跳过
                if (file_exists($controllerPath)){continue;}
            }
            /** 约束   配置 */
            if (isset($packageInfo['constraint']) && !empty($packageInfo['constraint']) && is_array($packageInfo['constraint']))
            {
                # 拼接命名空间
                $constraintNamespace = substr($useNamespace,0,strripos($useNamespace,'\\')+1);
                foreach ($packageInfo['constraint'] as $key=>&$value){
                    $value['namespace'] = $constraintNamespace.'Constraint';
                }
                $constraint = array_merge($constraint,$packageInfo['constraint']);
            }
            # 如果没有就按照CONTROLLER_INFO['namespace']写入对应的门面控制器文件类
            # 准备数据
            $data = [
                'User'=>$param['user']??$controllerInfo['User'],#检查人
                'Date'=>date('Y-m-d'),
                'Time'=>date('H:i:s'),
                'baseControl'=>$baseControl,#继承的基础控制器
                'baseAuth'=>$controllerInfo['baseAuth']??'Resource:public',# 基础权限控制器
                'title'=>$controllerInfo['title'],# 路由标题
                'baseAuthGroup'=>$controllerInfo['baseAuthGroup']??$baseAuthGroupStr,#权限分组
                'basePath'=>$controllerInfo['basePath'],#基础路由路径
                'baseParam'=>$controllerInfo['baseParam']??'[$Request:normphp\staging\Request]',# 依赖注入
                'namespace'=>$controllerInfo['namespace'],# 命名空间
                'use_namespace'=>$useNamespace,# 基础控制器的命名空间
                'className' =>$controllerInfo['className'],
                'classBasicsName'=>$classBasicsName,
                'packageName'=>$packageName,
                'packageAuthor'=>$packageAuthor,
            ];
            # 创建目录
            Helper()->file()->createDir($controllerDir);
            # 使用数据对模板进行替换
            $template = self::CONTROLLER_TEMPLATE;
            Helper()->str()->str_replace($data,$template);
            # 写入文件
            file_put_contents($controllerPath,$template);
        }
        $App->InitializeConfig()->set_config('BaseConstraint',['DATA'=>$constraint],$App->__DEPLOY_CONFIG_PATH__.DIRECTORY_SEPARATOR.$App->__APP__.DIRECTORY_SEPARATOR,'','基础约束集合');
        # 写入权限文件$permissions
        $App->InitializeConfig()->set_config('BaseAuthGroup',['DATA'=>$baseAuthGroup],$App->__DEPLOY_CONFIG_PATH__.DIRECTORY_SEPARATOR.$App->__APP__.DIRECTORY_SEPARATOR,'','基础权限集合');
    }

    /**
     * @Author 皮泽培
     * @Created 2019/12/26 15:16
     * @param App $App
     * @param array $param 暂时没有用
     * @return array
     * @throws \Exception
     * @title  自动生成导航菜单
     * @explain 注意：菜单的id理论上是唯一的是依赖包命名空间+菜单上下级+菜单name的md5值
     */
    public static function getMenuTemplate(App $App,array $param)
    {
        # 获取控制器文件路径/normative/vendor/normphp/deploy/src/controller/menuTemplatePath.json

        # 只有主项目 才合并
        if (\Deploy::SERVICE_MODULE !==[] && \Deploy::CENTRE_ID === \Deploy::PROJECT_ID ){
            foreach (\Deploy::SERVICE_MODULE as $v){
                $pathData =[];
                Helper()->getFilePathData(dirname($App->DOCUMENT_ROOT,1).DIRECTORY_SEPARATOR.$v['path'].DIRECTORY_SEPARATOR.'vendor',$pathData,'TemplatePath.json','menuTemplatePath.json');
                if (\Deploy::CENTRE_ID === (int)$v['id']){
                    # 获取单独一个服务模块的菜单  如果是主项目数据单独使用 在后面统一覆盖合并
                    $CentreAarry = static::getMenuTemplateInfo($App,$pathData,$v['path'],true);
                }else{
                    # 获取单独一个服务模块的菜单
                    $forArray = static::getMenuTemplateInfo($App,$pathData,$v['path'],false);
                    if (count($forArray) >1){
                        $baseArray = array_merge($baseArray??[],$forArray);
                    }else if (count($forArray) === 1){
                        $baseArray = $forArray;
                    }
                }
            }
            # 筛选主项目数据出来 在最后合并
            $data = Helper()->arrayList()->arrayAdditional($baseArray??[],$CentreAarry??[]);
            # 排序
            Helper()->arrayList()->sortMultiArray($data,['sort' => SORT_DESC]);
            # 写入菜单文件
            $App->InitializeConfig()->set_config('BaseMenu',['DATA'=>$data],$App->__DEPLOY_CONFIG_PATH__.DIRECTORY_SEPARATOR.$App->__APP__.DIRECTORY_SEPARATOR,'','导航菜单');
            return $data;
        }
        # 附属模块导航菜单(暂时没有使用）
        Helper()->getFilePathData('..'.DIRECTORY_SEPARATOR.'vendor',$pathData,'TemplatePath.json','menuTemplatePath.json');
        $baseArray = static::getMenuTemplateInfo($App,$pathData??[],\Deploy::MODULE_PREFIX);
        if (isset($baseArray)){
            # 筛选主项目数据出来 在最后合并
            $data = Helper()->arrayList()->arrayAdditional([],$baseArray);
        }
        $App->InitializeConfig()->set_config('BaseMenu',['DATA'=>$data??[]],$App->__DEPLOY_CONFIG_PATH__.DIRECTORY_SEPARATOR.$App->__APP__.DIRECTORY_SEPARATOR,'','附属模块导航菜单');

        return $data??[];
    }

    /**
     * @Author 皮泽培
     * @Created 12/28 9:44
     * @param App $App
     * @param $pathData
     * @param string $path
     * @param bool $centre
     * @return array
     * @throws \Exception
     * @title  获取单独一个服务模块的菜单
     * @explain 获取单独一个服务模块的菜单
     */
    public static function getMenuTemplateInfo(App $App,$pathData,string $path,bool$centre=false)
    {
        $arrayData = [];
        foreach($pathData as &$value){
            # 清除../   替换  /  \  .php  和src  获取基础控制器的路径地址
            $value['path'] = str_replace([dirname($App->DOCUMENT_ROOT,1).DIRECTORY_SEPARATOR.$path,DIRECTORY_SEPARATOR.'vendor'.DIRECTORY_SEPARATOR],['',''],$value['path']);
            $basePath = str_replace(['.php','/','..\\','../','\\src\\controller\\menuTemplatePath.json'],['','\\','','',''],$value['path']);
            if (!$centre  && in_array($basePath,self::__MENU__CENTRE) ){
                # 不是主项目  同时是基础包
                continue;
            }
            $data = json_decode($value['packageInfo'],true);
            if (!$data)throw new \Exception('json格式错误：'.$value['path']);
            static::buildMenuData($basePath,$data);    #构建菜单id
            $arrayData[$basePath] = $data;
        }
        return $arrayData;
    }

    /**
     * @Author 皮泽培
     * @Created 2019/12/26 15:14
     * @param string $key
     * @param array $data
     * @title  构建菜单id
     * @explain 路由功能说明
     */
    public static function buildMenuData(string $key,array &$data,$package ='')
    {
        #数据分析
        foreach ($data as &$value){
            $value['id'] = md5($key.$value['name']);
            $value['package'] = $package ==''?$key:$package;
            if (isset($value['list']) && is_array($value['list']) && $value['list'] !==[])
            {
                static::buildMenuData($value['id'],$value['list'],$value['package']);
            }
        }
    }
    const __MENU__CENTRE = [
        'normphp\deploy',
        'normphp\basics',
    ];
    /**
     * 统一的控制器文件模板
     */
    const CONTROLLER_TEMPLATE = <<<NEO
<?php
declare(strict_types=1);
namespace {{namespace}};

use {{use_namespace}};
/**
 * Class {{className}}
 * @package {{className}}
 * Created by PhpStorm.
 * User: {{User}}
 * Date: {{Date}}
 * Time: {{Time}}
 * @title {{title}}
 * @basePath {{basePath}}
 * @baseAuth {{baseAuth}}
 * @baseAuthGroup {{baseAuthGroup}}
 * @packageName {{packageName}}
 * @packageAuthor {{packageAuthor}}
 * @baseParam {{baseParam}}
 * @baseControl {{baseControl}}
 */
class {{className}} extends {{classBasicsName}}
{

}

    
NEO;


}
