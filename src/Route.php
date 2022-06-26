<?php

declare(strict_types=1);

namespace normphp\staging;

/**
 * Created by PhpStorm.
 * User: pizepei
 * Date: 2018/8/2
 * Time: 16:06
 * @title 路由
 */
class Route
{
    /**
     * 当前对象
     * @var null
     */
    private static $object = null;
    /**
     * 配置
     * @var null
     */
    protected $Config = null;
    /**
     * 支持的请求类型RequestType
     * REQUEST_TYPE
     */
    const REQUEST_TYPE =['All','GET','POST','PUT','PATCH','DELETE','COPY','HEAD','OPTIONS','LINK','UNLINK','PURGE','LOCK','UNLOCK','PROPFIND','VIEW','CLI'];
    /**
     * 请求path参数数据类型,用来限制路由生成
     * REQUEST_PATH_PARAM_DATA_TYPE
     */
    const REQUEST_PATH_PARAM_DATA_TYPE = ['int','string','float'];
    /**
     * get post 等请求参数数据类型
     * REQUEST_PARAM_DATA_TYPE
     */
    const REQUEST_PARAM_DATA_TYPE = ['int','string','bool','float','array','null'];
    /**
     * 返回数据类型
     */
    const RETURN_FORMAT = ['list','objectList','object','raw'];
    /**
     * 路由附加配置
     * debug 调试模式    auth  权限
     */
    const REYURN_ADD_ITION =  ['debug','auth'];
    /**
     * 路由资源类型  api 为默认传统类型    microservice为微服务类型（在进入控制器到控制器的权限判断时继续请求数据的单独处理 在文档中进行特殊显示）
     * resourceType
     */
    const RESOURCE_TYPE = ['api','microservice'];
    /**
     * 当前路由的资源类型
     * @var string
     */
    protected $resourceType = 'api';
    /**
     * 路由参数附加参数  参数名称=>['文字说明','表达式 empty或者函数','并在匹配成功后继续数据类型转换']
     * @var array|\string[][]
     */
    protected $returnSubjoin = [
        'required' => ['必须的', 'empty', 'string'],
        'enum' => ['枚举enum','enum','string'],
        'money' => ['money_format','money_format','string'],
        'mobile' => ['手机号码', '/^[1][3-9][0-9]{9}$/', 'int'],
        'identity' => ['身份证', '/^[1-9]\d{5}(18|19|2([0-9]))\d{2}(0[0-9]|10|11|12)([0-2][1-9]|30|31)\d{3}[0-9X]$/', 'string'],
        'phone' => ['电话号码', '/^(13[0-9]|14[5-9]|15[0-3,5-9]|16[2,5,6,7]|17[0-8]|18[0-9]|19[1,3,5,8,9])\d{8}$/', 'string'],
        'password' => ['密码格式', '/^[0-9A-Za-z_\@\*\,\.]{6,23}$/', 'string'],
        'mid' => ['账户ID', '/^(?![0-9]+$)[0-9A-Za-z_]{3,15}$/', 'string'],
        'uuid' => ['uuid', '/^[0-9A-Za-z]{8}[-][0-9A-Za-z]{4}[-][0-9A-Za-z]{4}[-][0-9A-Za-z]{4}[-][0-9A-Za-z]{12}$/', 'string'],//0152C794-4674-3E16-9A3E-62005CACC127
        'email' => ['email', '/^[A-Za-z\d]+([-_.][A-Za-z\d]+)*@([A-Za-z\d]+[-.])+[A-Za-z\d]{2,4}$/', 'string'],
    ];
    /**
     * 禁止外部获取的类属性
     */
    const FORBID_PRAM= [
        'noteRouter',//所有的路由信息
    ];
    /**
     * 应用目录下所有文件路径
     * @var array
     */
    protected array $filePathData = [];
    /**
     * 当前请求的控制器
     * @var string
     */
    protected string $controller = '';
    /**
     * 当前请求的模块
     * @var string
     */
    protected $module = '';
    /**
     * 当前请求的方法
     * @var string
     */
    protected $method = '';
    /**
     * 当前路径(控制器)
     * @var string
     */
    protected $atPath = '';
    /**
     * 当前路由
     * @var string
     */
    protected $atRoute = '';
    /**
     * 路由数据
     * @var null
     */
    protected $noteRouter = array();
    /**
     * 当前路由的所有信息
     * @var array
     */
    protected $atRouteData = [];
    /**
     * 控制器发返回类型
     * @var null|string
     */
    protected $ReturnType = null;
    /**
     * 当前路由的权限控制器
     * @var array
     */
    protected $baseAuth = [];
    /**
     * @var array
     */
    protected $RouterAdded = [];
    /**
     * 以命名空间为key的控制器路由注解快
     * @var array
     */
    protected $noteBlock = array();
    /**
     * 当前路由的tag
     * @var string
     */
    protected $authTag = '';
    /**
     * 当前路由return参数
     * @var array
     */
    protected $Return = [];
    /**
     * 匹配路由（都是从请求方法先过滤的）
     *      生成路由表时
     *          把常规路由与Restful路由分开
     *              Restful 路由 切割出来：变量名前的路由 字符串$routeStartStr（strpos()）
     *      进行匹配时
     *          1、通过in_array 匹配常规路由
     *          2、常规路由没有匹配到 使用Restful 路由表匹配
     *              1、循环Restful 路由表  使用strpos()函数查询$routeStartStr 在url（$s）中重新的位置（然后为0 代表匹配到 如果是false 或者>0 为没有匹配到）可能匹配多个使用数组$arr存储
     *              2、判断coumt($arr) >是否大于1 大于进行3步骤  == 0 路由不存在  ==1 路由存在进行4步骤
     *              3、循环使用正则表达式进行匹配$arr中路由（如何依然有超过1个匹配成功：记录日志 返回路由冲突）
     *              4、使用方法获取变量并且保存到routeParam(独立$_GET $_POST )
     * 匹配到路由进行请求转发（给控制器）
     *      1、通过路由表进行所以参数的处理（吧路由表相关参数给请求类Request）
     *      2、根据路由表Param 吧 请求类Request实例 注入给 路由对应的 控制器方法
     * 一个请求处理完
     */
    /**
     * @var App
     */
    protected App $app;
    /**
     * 当前工程项目的的基础权限集合分组
     * @var array
     */
    protected $baseAuthGroup = [];
    /**
     * 构造方法
     * Route constructor.
     * @param App $app
     * @throws \Exception
     */
    public function __construct(App $app)
    {

        $this->app = $app;
        # 合并returnSubjoin 自定义路由 数据格式
        # 判断路由没事 获取当前路由 atRoute 通常是通过配置nginx 通过$_SERVER['PATH_INFO']获取的请求路径（不包括域部分）
        if (array_key_exists('PATH_INFO',$_SERVER)){
            //默认路由
            $atRoute = $_SERVER['PATH_INFO'] !== ''?$_SERVER['PATH_INFO']:$this->app->__ROUTE__['index'];
        }else if (isset($_SERVER['PHP_SELF'])){
            # 在使用 php -S 127.0.0.1:8080 index.php 启动服务时才会进入到这里（至少目前是这样：2021-02-25）
            $atRoute = $_SERVER['PHP_SELF']==='/index.php'?$this->app->__ROUTE__['index']:$_SERVER['PHP_SELF'];
            $_SERVER['PATH_INFO'] = $_SERVER['PHP_SELF'];
        }else{
            throw new \Exception('服务配置错误，请检查NGINX配置PATH_INFO');
        }
        # postfix 自定义路由后缀在前后端完全分离时有用：nginx 配置中固定的后缀转发到后端
        if ($this->app->__ROUTE__['postfix'] !==[]){
            # 配配到对应的默认 后缀 替换成空格 避免开发过程中出现不必要的混乱，方便后期部署时随时随地替换后缀
            foreach ($this->app->__ROUTE__['postfix'] as $value){
                $atRoute = str_replace($value,'',$atRoute);
            }
        }
        # 定义当前路由
        $this->atRoute = $atRoute;
        # $_SERVER['REQUEST_METHOD'] 请求类型
        # $_SERVER['REQUEST_URI'] 请求url
        # $_SERVER['QUERY_STRING'] 请求url上的 GET参数
        # 生成 使用 注解路由
        $this->annotation();
        # $this->noteRoute();
    }
    /**
     * 判断是否存在对应的属性
     * @param $name
     * @return bool|null
     */
    public function __isset($name)
    {
        if (isset($this->$name)){
            return true;
        }return null;

    }
    /**
     * @Author 皮泽培
     * @Created 2020/8/15 15:43
     * @param $type
     * @param $RouteData
     * @title  快速获取对应请求类型的 路由信息集合
     * @explain 只有接收到对应的请求类型才会在框架路由类对象初始化时加载其他来请求类型的类文件不会加载
     */
    protected function getRouteTypeData($requestType, string $routeType, &$RouteData)
    {
        /**
         * 路由信息保存类
         * 框架会自动在config/route/目录下生成定义的路由类
         * 只有接收到对应的请求类型才会在框架路由类对象初始化时加载其他来请求类型的类文件不会加载
         */
        $RouteData = require ($this->app->__DEPLOY_CONFIG_PATH__.'route'.DIRECTORY_SEPARATOR.'RouteInfo'.ucfirst(strtolower($requestType)).ucfirst($routeType).'.php');
    }

    /**
     * 匹配path路由
     * @param $RouteData
     * @param $PathArray
     * @return void
     * @throws \Exception
     */
    protected function getPathNote(&$RouteData, &$PathArray)
    {
        # 不在常规路由中 同时也没有模糊匹配（path中确定的部分简单匹配）
        # 使用快捷匹配路由匹配
        $length = 0;
        $PathNote = [];
        foreach ($RouteData as $k=>$v){
            # 通过长度进行匹配
            if(strpos($this->atRoute,$v['PathNote']) === 0 || is_string(strpos($this->atRoute,$v['PathNote']))){
                # 使用最佳匹配长度结果（匹配长度最长的）
                if(strlen($v['PathNote']) > $length){
                    $length = strlen($v['PathNote']); # 重新定义长度
                    $PathNote[$length][$k] = $v;
                }else if(strlen($v['PathNote']) == $length){
                    $PathNote[$length][$k] = $v;
                }
            }
        }
        if(empty($PathNote)){
            //header("Status: 404 Not Found");
            //header("HTTP/1.0 404 Not Found");
            throw new \Exception('路由不存在',404);
        }
        # 判断匹配到的路由数量、使用正则表达式匹配并且获取参数、$length为strlen()获取的匹配长度，使用匹配最才$length做路由匹配
        if(count($PathNote[$length])>1){
            /**
             * 使用模糊匹配后仍然有多个路由
             *      strlen()获取的匹配长度一样的情况下导致有多个路由
             * 匹配到多个 使用正则表达式
             */
            $PathParamCount = 0;
            $PathNoteFor = $PathNote[$length];
            foreach ($PathNoteFor as $pnK=>$pnV){
                preg_match($pnV['MatchStr'],$this->atRoute,$PathDataFor);
                /**
                 * 判断正则表达式获取的参数数量是否和配置一致
                 * 路径参数在正则表达式中统一用(.*?)表示因此
                 *      如果路由前缀是相同的如/index/:id[uuid]   和 /index/:name[string] 会同时路由冲突
                 *      /index/:id[uuid]/:name[string] 和 /index/:name[string] 不会冲突并且进入到这里
                 *      因此这里只需要判断使用正则表达式匹配到的参数数量-1后 和$pnV['PathParam']一致并且匹配的参数的数量最长 就可以判断是正确路由了
                 */

                if(count($pnV['PathParam']) === (count($PathDataFor)-1) && count($pnV['PathParam'])>$PathParamCount)
                {
                    $PathParamCount = count($pnV['PathParam']);
                    $PathData = $PathDataFor;
                    $RouteData = $pnV;
                }
            }
        }else{
            # 只有一个
            $RouteData = current($PathNote[$length]);
            preg_match($RouteData['MatchStr'],$this->atRoute,$PathData);# 使用正则表达式匹配
        }
        if (!isset($PathData)){
            throw new \Exception('路由不存在'.$this->atRoute);
        }
        array_shift($PathData);
        if( !isset($RouteData['PathParam']) || (count($RouteData['PathParam']) != count($PathData))){
            # 严格匹配参数（如果对应的:name位置没有使用参数 或者为字符串空  认为是路由不存在 或者提示参数不存在）
            throw new \Exception(($RouteData['Router']??'').':路由不存在,请检查路由参数是否使用了特殊字符串（-_@）');
        }
        # 对参数进行强制过滤（根据路由上的规则：name[int]）
        $i=0;
        foreach ($RouteData['PathParam'] as $k=>$v){
            # 判断排除 空参数
            if(empty($PathData[$i]) && $PathData[$i] !=='0'){
                throw new \Exception($k.'缺少参数');
            }
            # 参数约束  array_key_exists($v,\BaseConstraint::DATA)
            if(isset(\BaseConstraint::DATA[$v])){
                if (\BaseConstraint::DATA[$v]['type'] !=='regexp'){throw new \Exception($k.'不支持PATH参数约束:'.$v);}
                # 开始 匹配路径参数
                /***自定义 数据格式类型 处理**/
                $method = \BaseConstraint::DATA[$v]['type'] === 'class'?$restrainKey:'regexp';
                /**使用自定义类方法 处理数据**/
                preg_match(\BaseConstraint::DATA[$v]['value'],$PathData[$i],$result);
                if(!isset($result[0]) && empty($result[0])){throw new \Exception($k.'非法的:'.\BaseConstraint::DATA[$v]['msg']['zh-cn']);}
            }else if(in_array($v,self::REQUEST_PATH_PARAM_DATA_TYPE)){
                if(!settype($PathData[$i],$v)){throw new \Exception($k.'参数约束失败:'.$v);}
            }else{
                throw new \Exception($k.'非法的参数约束:'.$v);
            }
            $PathArray[$k] = $PathData[$i];
            ++$i;
        }
    }

    /**
     * @Author pizepei
     * @return mixed
     * @throws \Exception
     * @title  注释路由（控制器方法上注释方式设置的路由）
     * @explain 一般是方法功能说明、逻辑说明、注意事项等。
     */
    protected function noteRoute()
    {
        $this->getRouteTypeData($_SERVER['REQUEST_METHOD'], 'Rule', $RouteData);
        if (array_key_exists($this->atRoute,$RouteData)) {
            # 匹配到常规路由
            $RouteData = $RouteData[$this->atRoute];
        }else{
            $this->getRouteTypeData($_SERVER['REQUEST_METHOD'], 'Path', $RouteData);
            # 使用路径路由匹配模式
            if(empty($RouteData)){
                throw new \Exception('路由不存在');
            }
            #  不在常规路由中 同时也没有模糊匹配（path中确定的部分简单匹配）
            $this->getPathNote($RouteData, $PathArray);
        }
        /**判断是否有对应参数（确定是先检查参数准确性、还是在控制器中获取参数时检查（可能出现参数不正确但是不提示错误）） */
        $function = $RouteData['function']['name'];
        $this->controller = &$RouteData['Namespace'];
        $this->method = &$RouteData['function']['name'];
        $this->atRoute = &$RouteData['Router'];             #路由
        $this->ReturnType = &$RouteData['ReturnType'];      #路由请求类型
        $this->resourceType = &$RouteData['resourceType'];    #资源类型
        $this->RouterAdded = &$RouteData['RouterAdded']??'';    #附加配置
        $this->atRouteData = &$RouteData;                   #路由
        $this->baseAuth = &$RouteData['baseAuth']??[];      #权限控制器
        $this->authTag = &$RouteData['tag']??'';            #路由标识
        $this->Return = &$RouteData['Return']??[];
        //权限控制器
        if(!empty($RouteData['auth'][0])){
            $this->baseAuth = &$RouteData['auth']??[];
        }
        # 避免在控制器中有输出导致Cannot modify header information - headers already sent by错误=>因此在控制器实例化前设置头部
        $this->app->Response()->setHeader($this->ReturnType);
        # 设置匹配到的路径参数
        $this->app->Request()->PATH = $PathArray??[];
        # 实例化控制器
        $controller = new $RouteData['Namespace']($this->app);
        if(empty($RouteData['function']['Param']) && empty($RouteData['ParamObject'])){
            return $controller->$function();
        }else{
            return $controller->$function($this->app->Request());
        }
    }

    /**
     * @param $data
     * @return \Generator
     */
    public function yieldForeach($data)
    {
        foreach ($data as $key=>$value){
            if(strpos($this->atRoute,$value['PathNote']) === 0){
                # 使用最佳匹配长度结果（匹配长度最长的）
                if(strlen($v['PathNote']) > $length){
                    $length = strlen($v['PathNote']); # 重新定义长度
                    (yield $key=>$value);
                }else if(strlen($v['PathNote']) == $length){
                    (yield $key=>$value);
                }
            }
        }
        if(count($PathNote[$length])>1) {
            (yield 0=>null);
        }
    }
    /**
     *  获取 property
     * @param $propertyName
     */
    public function __get($propertyName)
    {
        # 设置禁止获取的信息

        if(isset($this->$propertyName)){return $this->$propertyName;}
        return null;
    }
    /**
     * 生成注解路由->判断运行模式->判断是否存在缓存->获取到属性
     * @throws \Exception
     */
    public function annotation()
    {
        $fileData =array();
        # 判断应用模式  如果是开发模式  或者其中一个配置文件不存在 都会重新生成权限与路由配置文件
        if($this->app->__EXPLOIT__ == 1 || !file_exists($this->app->__DEPLOY_CONFIG_PATH__.'RouteInfo.php') || !file_exists($this->app->__DEPLOY_CONFIG_PATH__.'PermissionsInfo.php')){
            # 获取应用目录下所有文件路径
            $this->getFilePathData($this->app->DOCUMENT_ROOT.$this->app->__APP__,$fileData);
            $this->filePathData = $fileData;
            # 分离获取所有注解块
            $this->noteBlock();
            # 设置Route   Permissions  类
            $this->app->InitializeConfig()->set_config('RouteInfo',$this->noteRouter,$this->app->__DEPLOY_CONFIG_PATH__);
            foreach (self::REQUEST_TYPE as $value){
                # 转换名
                $name = ucfirst(strtolower($value));
                /**
                 * 生成路由配置
                 */
                $this->app->InitializeConfig()->set_arrayConfig('RouteInfo'.$name.'Path',$this->noteRouter[$value]['Path']??[],$this->app->__DEPLOY_CONFIG_PATH__.'route'.DIRECTORY_SEPARATOR);
                $this->app->InitializeConfig()->set_arrayConfig('RouteInfo'.$name.'Rule',$this->noteRouter[$value]['Rule']??[],$this->app->__DEPLOY_CONFIG_PATH__.'route'.DIRECTORY_SEPARATOR);
            }
            #$this->app->InitializeConfig()->set_arrayConfig('RouteInfo',$this->noteRouter,$this->app->__DEPLOY_CONFIG_PATH__);
            $this->app->InitializeConfig()->set_arrayConfig('RouteNoteBlock', $this->noteBlock, $this->app->__DEPLOY_CONFIG_PATH__);
            # 合并权限数据
            $this->setPermissions();
            $this->app->InitializeConfig()->set_config('PermissionsInfo', $this->Permissions, $this->app->__DEPLOY_CONFIG_PATH__);
        }
        # 包含配置
        require ($this->app->__DEPLOY_CONFIG_PATH__.'PermissionsInfo.php');
    }

    /**
     * 获取
     * @return void
     */
    public function getNoteBlockDocument()
    {
        if (\Deploy::__DOCUMENT__ && $this->noteBlock = 2) {
            $this->noteBlock = require ($this->app->__DEPLOY_CONFIG_PATH__.'RouteNoteBlock.php');
        }
        return $this->noteBlock;
    }

    /**
     * @Author 皮泽培
     * @Created 2019/11/19 11:17
     * @title  处理合并权限数据
     */
    public function setPermissions()
    {
        $data =\Deploy::PERMISSIONS;
        $data['disabled'] = true;
        foreach (\BaseAuthGroup::DATA as $key=>$value)
        {
            $children = [];
            foreach ($this->Permissions[$key]??[] as $k=>$v)
            {
                $children[]=$v;
            }
            $data['children'][] = [
                'title' => $value['name'],
                'id' => $key,
                'field' => $key,
                'disabled' => true,
                'children' => $children
            ];
        }
        $this->Permissions = $data;
    }
    /**
     * @Author 皮泽培
     * @Created 2019/8/19 14:38
     * @title  获取注解块
     * @throws \Exception
     */
    protected function noteBlock()
    {
        foreach ($this->filePathData as $k=>$v){
            $this->getNoteBlock($v);
            unset($this->filePathData[$k]);
        }
    }

    /**
     * 获取一个控制器中所有的注解块(使用正则表达式)
     * @param $filePath
     * @return false
     * @throws \ReflectionException
     */
    protected function getNoteBlock($filePath)
    {
        # 替换并获取到类命名空间
        $red = '\\'.str_ireplace([$this->app->DOCUMENT_ROOT,DIRECTORY_SEPARATOR,'.php'],['','\\',''],$filePath);
        # 使用反射
        $reflection  = new \ReflectionClass($red);
        # 命名空间
        $namespace = $reflection->getNamespaceName();
        # 类命名空间（包括类名）
        $classNamespace = $reflection->getName();
        # 获取类名称
        $class = substr($classNamespace,strrpos($classNamespace,'\\')+1);
        # 过滤非控制器类文件
        $controller_info = $reflection->hasConstant('CONTROLLER_INFO');
        if(!$controller_info){ return false;}
        $controller_info = $reflection->getConstant('CONTROLLER_INFO');
        $controllerTitle = $controller_info['title']??'未定义';
        $User = $controller_info['User']??'未定义';
        $basePath = $controller_info['basePath']??'/';
        # 基础root路由 如果有就删除 /
        $basePath = rtrim($basePath,'/');
        # 类注解
        $classDocComment = $reflection->getDocComment();
        preg_match('/@baseControl[\s]{1,6}(.*?)[\s]{1,4}/s',$classDocComment,$baseControl);
        preg_match('/@baseAuthGroup[\s]{1,6}(.*?)[\r\n]/s',$classDocComment,$baseAuthGroup);
        preg_match('/@baseAuth[\s]{1,6}(.*?)[\r\n]/s',$classDocComment,$baseAuth);

        # 处理权限(依赖容器)
        if(isset($baseAuth[1])) {$baseAuth = explode(':',$baseAuth[1]);}else{$baseAuth = [];}

        # 项目全局 各依赖包的权限空间注册
        if (isset($baseAuthGroup[1]) && !empty($baseAuthGroup[1])){
            $baseAuthGroup = explode(',',$baseAuthGroup[1]);
            # 判断是否是多个
            foreach ($baseAuthGroup as $uthValue){
                $baseAuthGroupRes = explode(':',$uthValue);
                if (count($baseAuthGroupRes) ===2){
                    list($key, $name) = $baseAuthGroupRes;
                    $this->baseAuthGroup[$key] = $name;
                }
            }
            # 继续切割
        }

        # 通过反射 获取方法上的 注解 \ReflectionMethod::IS_PUBLIC + \ReflectionMethod::IS_PROTECTED + \ReflectionMethod::IS_PRIVATE
        $methods = $reflection->getMethods(\ReflectionMethod::IS_PUBLIC);
        #循环处理控制器中每一个注解块（如果其中没有@router关键字就抛弃注解块）
        foreach ($methods as $method){
            # 获取方法注解块
            $v = $method->getDocComment();
            if (!$v){continue;}
            # 获取方法名
            $methodName = $method->getName();
            # 获取方法参数信息
            $parameters = $method->getParameters();
            # 删除上一个注解块留下来的数据
            # $PathNote简单路径路由用来快速匹配   $routerStr路由 $matchStr 匹配使用的 正则表达式  $PathParam路径参数（路由参数如user/:id  id就是路由参数）
            # $routeParamData 路由参数（url上的或者post等）  $routeReturnData 返回参数   $function控制器方法  $Author 方法创建人  方法创建时间 $Created 方法创建时间 $routeParam 切割请求参数
            unset($PathNote,$routerStr,$matchStr,$ParamObject,$PathParam,$routeParamData,$routeReturnData,$function,$Author,$Created);
            /********************获取详路由细方法************/
            # 从注解块匹配路由数据
            preg_match('/@router(.*?)[\n\r]/s',$v,$routerData);
            # 判断注解块是否为控制器方法（可访问的方法：是否设置路由r@outer）
            if(empty($routerData)){continue;}

            # 获取方法参数
            foreach ($parameters as $funk=>$funv){
                $functionParam[$funk] = $funv->name;
            }
            $function['name'] = $methodName;
            $function['Param'] = $functionParam??[];
            preg_match_all('/[^ ]+[A-Z-a-z_0-9.:\/\]\[]+/s',$routerData[1],$routerData);

            if(!isset($routerData[0][1])){continue;}# 跳过不规范的路由

            $routerData = $routerData[0];
            $routerData[0] = strtoupper($routerData[0]);
            # 判断请求类型
            if(!in_array($routerData[0],self::REQUEST_TYPE)){throw new \Exception('不规范的请求类型'.$classNamespace.'->'.$routerData[0]);}
            # 判断是否是独立路由
            if(strpos($routerData[1],'/') === 0){
                # 独立路由
                $routerStr = '/'.ltrim($routerData[1],'/');
            }else{
                # 需要拼接路由
                $basePath = rtrim($basePath,'/').'/';
                $routerStr = '/'.ltrim($basePath.$routerData[1],'/');
            }
            # 设置附件路由配置
            if(isset($routerData[2])){
                $routerAdded = $routerData;
                unset($routerAdded[0]);
                unset($routerAdded[1]);
                foreach($routerAdded as $routerAddedValue ){
                    $routerAddedExp = explode(':',$routerAddedValue);
                    if(!in_array($routerAddedExp[0],self::REYURN_ADD_ITION)){throw new \Exception('不规范的路由附加配置'.$classNamespace.'->'.$routerStr.'->'.$routerAddedValue);}
                    $routerAddedExplode[$routerAddedExp[0]] = $routerAddedExp[1];
                }
                $routerAdded = $routerAddedExplode;
            }

            /**********判断拼接路由  定义完整的错误提示路径********/
            # $classNamespace 完整的命名空间  $routerData[0] 请求方法  $routerStr 完整的请求路由
            $baseErrorNamespace = $classNamespace.'-'.$routerData[0].'-'.$routerStr;
            # 把常规路由与Restful路由分开
            preg_match('/\[/s',$routerData[1],$routerType);
            if(empty($routerType)){$routerType = 'Rule';}else{
                $routerType = 'Path';
                # 获取简单路径路由用来快速匹配提升被请求时的匹配效率
                preg_match('/(.*?):/s',$routerStr,$PathNote);
                if (!isset($PathNote[1])){throw new \Exception($routerStr.' 路径路由参数前必须使用冒号 : ');}
                $PathNote = $PathNote[1];
                # 准备正则表达式
                $routerStrReplace = preg_replace('/\:[A-Za-z\[]+\]/','([^/]*?)',$routerStr);
                $matchStr = '/^'.preg_replace('/\//','\/',$routerStrReplace).'[\/]{0,1}$/';
                #  获取：path参数
                preg_match_all('/:[a-zA-Z_\[\]]+/s',$routerData[1],$PathParamData);
                unset($PathParam);
                if(isset($PathParamData[0][0])){
                    $PathParamData = $PathParamData[0];
                    # 循环检查 路径参数
                    foreach($PathParamData as $Pk=>$Pv){
                        # 获取参数 name
                        preg_match('/:(.*?)\[/s',$Pv,$PvName);
                        if(!isset($PvName[1])){ throw new \Exception('不规范的PATH请求参数定义：'.$baseErrorNamespace.' 参数定义格式为  :英文字母参数名[参数类型定义] ');}
                        #获取约束
                        preg_match('/\[(.*?)\]/s',$Pv,$PvRes);
                        if(!isset($PvRes[1])){ throw new \Exception('不规范的PATH请求参数数据约束类型：'.$baseErrorNamespace.' 参数定义格式为  :英文字母参数名[参数类型定义] ');}
                        if (isset(\BaseConstraint::DATA[$PvRes[1]]) && \BaseConstraint::DATA[$PvRes[1]]['type'] !=='regexp'){
                            throw new \Exception('PATH路由不支持：'.$routerData[0].'->'.$routerData[1].'  ['.$PvRes[1].']参数数据约束类型');
                        }
                        if (!isset(\BaseConstraint::DATA[$PvRes[1]])  && !in_array($PvRes[1],self::REQUEST_PATH_PARAM_DATA_TYPE) ){
                            throw new \Exception('未定义的参数数据约束类型：'.$routerData[0].'->'.$routerData[1].'  ['.$PvRes[1].']');
                        }
                        $PathParam[$PvName[1]] = $PvRes[1];
                    }
                }
            }
            # 检查路由冲突 noteRouter
            if($routerType == 'Rule'){
                if(isset($this->noteRouter[$routerData[0]][$routerType][$routerStr] )){
                    throw new \Exception("路由冲突:[".$baseErrorNamespace.']<=>['.$this->noteRouter[$routerData[0]][$routerType][$routerStr]['Namespace'].'-'.$routerType.'-'.$routerStr.']');
                }
            }else{
                if(isset($this->noteRouter[$routerData[0]][$routerType][$matchStr] )){
                    throw new \Exception("路由冲突:[".$baseErrorNamespace.']<=>['.$this->noteRouter[$routerData[0]][$routerType][$matchStr]['Namespace'].'-'.$routerType.'-'.$this->noteRouter[$routerData[0]][$routerType][$matchStr]['Router'].']');
                }
            }
            # 检测路由详细信息 1 请求方法 2 路由路径 3 路由参数
            if(count($routerData)>=2){
                # 切割详细信息
                preg_match('/@resourceType[\s]+(.*?)[\r\n]/s',$v,$resourceType);# 路由注意类型 如果默认是API资源  microservice为微服务应用API资源
                if (isset($resourceType[1]) && !in_array($resourceType[1],self::RESOURCE_TYPE)){ throw new \Exception('resourceType 类型错误['.$baseErrorNamespace.']');}

                preg_match('/@explain[\s]+(.*?)[\r\n]+@/s',$v,$routeExplain);//路由解释说明（备注）多行/@explain[\s]+(.*?)[*][\s]@/s
                preg_match('/@title[\s]+(.*?)@/s',$v,$routeTitle);//获取路由名称
                preg_match('/@param[\s]+(.*?)@/s',$v,$routeParam);//请求参数
                preg_match('/@return[\s]+(.*?)@/s',$v,$routeReturn);//获取返回参数
                preg_match('/@author[\s]+(.*?)[\s\n]{1,8}[*]{1}[\s\n]{1,8}@{0,1}/s',$v,$Author);//方法创建人
                preg_match('/@created[\s]+(.*?)[\s\n]{1,8}[*]{1}[\s\n]{1,8}@{0,1}/s',$v,$Created);//方法创建时间

                preg_match('/@authGroup[\s]+(.*?)[\r\n]+/s',$v,$routeAuthGroup);//路由的权限分组
                preg_match('/@authExtend[\s]+(.*?)[\r\n]/s',$v,$routeAuthExtend);//权限扩展信息
                preg_match('/@baseAuth[\s]+(.*?)[\r\n]/s',$v,$routeBaseAuth);//路由上定义的权限控制器
                /*** ***********切割请求参数[url 参数  post等参数 不包括路由参数] return***************/
                $routeParam = $routeParam[1]??[];
                # 获取依赖注入的 对象 容器下主要是为了适配IDE   目前只支持Request对象（严格区分大小写）
                if($routeParam != []){
                    preg_match('/(.*?)[ ]{0,10}[\r\n]/s',$routeParam,$routeParamObject);//请求参数
                    $routeParamObject = $routeParamObject[1]??'';
                    if(empty($routeParamObject)){ throw new \Exception('设置了@param但是没有传入对象信息['.$baseErrorNamespace.']');}
                    # 判断对象信息 默认常规array $Request [xml] 使用[] 可直接定义xml或者json
                    preg_match('/\[(.*?)]/s',$routeParamObject,$routeParamObjectType);
                    # 判断是否有命名空间
                    if($routeParamObject != '$Request'){
                        # 有命名空间
                        preg_match('/(.*?)[ ]+[\$][A-Za-z]{1,30}[ ]{0,1}/s',$routeParamObject,$routeParamObjectPath);//请求对象命名空间
                        preg_match('/[\$][A-Za-z]{1,30}/s',$routeParamObject,$routeParamObject);
                        # 请求对象
                        $routeParamObject = $routeParamObject[0]??'';
                        if($routeParamObject != '$Request'){ throw new \Exception('目前只支持Request对象（严格区分大小写）:'.$routeParamObject);}
                    }
                    /** 开始切割获取请求参数  **/
                    if(isset($routeParam[1])){
                        /**  以*     \r  为标准每行切割 */
                        preg_match_all('/\*(.*?)[\r\n]/s',$routeParam,$routeParamData);//获取返回参数
                        if(isset($routeParamData[1]) && !empty($routeParamData[1]) && $routeParamData[1][0] !==''){
                            /** 获取详细参数  */
                            $routeParamData = $this->setReturn($routeParamData[1],$baseErrorNamespace);
                            //throw new \Exception('$Request 必须规定参数'.$baseErrorNamespace);
                        }
                    }
                }
                /*** ***********切割返回信息 return ***************/
                if(isset($routeReturn[1])){
                    /**  array [objectList]  获取return array [object] 数据  （返回数据类型array为数组：json html：直接输出html页面） */
                    preg_match('/[\s]{0,5}(.*?)[ ]{0,4}[\r\n]/s',$routeReturn[1],$routeReturnType);//获取返回参数
                    #  判断返回数据类型有html xml 默认json（array）
                    if(isset($routeReturnType[1])){
                        $routeReturnType = $routeReturnType[1];
                        $routeReturnExplain = $routeReturnType;
                        preg_match('/\[(.*?)\]/s',$routeReturnType,$routeReturnType);//获取返回参数
                        if(!isset($routeReturnType[1])) {throw new \Exception('返回类型[必须填写]:'.$routeParamObject);}
                        if (!isset($this->app->Response()->HeaderType[$routeReturnType[1]]) && $routeReturnType[1] !=='cli'){throw new \Exception($baseErrorNamespace.' ：未定义的返回类型:'.$routeReturnType[1]);}
                        $routeReturnType = $routeReturnType[1];
                    }else{$routeReturnType = $this->app->__ROUTE__['return']??'json';}
                    # 以*      \r  为标准每行切割
                    preg_match_all('/\*(.*?)[\r\n]/s',$routeReturn[1],$routeReturnData);//获取返回参数
                    if(isset($routeReturnData[1]) && isset($routeReturnData[1][0]) && $routeReturnData[1][0] !==''){ $routeReturnData = $this->setReturn($routeReturnData[1],$baseErrorNamespace);}else{$routeReturnData = [];}
                }
                /************** 权限  ***************/
                # 准备权限信息  使用控制器的命名空间+方法 MD5
                $tag = md5($classNamespace.$function['name']);//路由标识（控制器方法级别）
                # 控制器级别增加分类
                $AuthClassify = [
                    'title'=>$title[1]??'',
                    'field'=>$classNamespace,
                    'id'=>$classNamespace,
                    'children'=>[]
                ];
                # 获取路由标题（同时也是当前接口权限的标题）
                if(isset($routeTitle[1])){preg_match('/(.*?)[\n\r]/s',$routeTitle[1],$routeTitle);}
                # 处理权限控制器
                if(isset($routeBaseAuth[1]))
                {
                    $routeBaseAuth = explode(':',$routeBaseAuth[1]);
                }else{
                    $routeBaseAuth = [];
                }
                $routeBaseAuth[1] = $routeBaseAuth[1]??'';
                # 切割路由级别权限和扩展权限
                $this->authDispose($routeAuthGroup,$routeAuthExtend);
                # 如果控制器定义为public 或者路由定义我public 就不进行权限定义
                if (count($routeBaseAuth) ===2){
                    if ($routeBaseAuth[1] !=='public'){
                        # 过滤没有注册的基础权限  拼接合法的权限进入权限数据集合
                        $detectionAuthGroup = $this->detectionAuthGroup($routeAuthGroup,$routeAuthExtend,$tag,$routeTitle[1]??'',$routeExplain[1]??'',$routeReturnData??[],$AuthClassify);
                    }
                }else if (count($baseAuth) ===2){
                    if ($baseAuth[1] !=='public'){
                        # 过滤没有注册的基础权限  拼接合法的权限进入权限数据集合
                        $detectionAuthGroup = $this->detectionAuthGroup($routeAuthGroup,$routeAuthExtend,$tag,$routeTitle[1]??'',$routeExplain[1]??'',$routeReturnData??[],$AuthClassify);
                    }
                }
                # 过滤没有注册的基础权限  拼接合法的权限进入权限数据集合
//                    $detectionAuthGroup = $this->detectionAuthGroup($routeAuthGroup,$routeAuthExtend,$tag,$routeTitle[1]??'',$routeExplain[1]??'',$routeReturnData??[],$AuthClassify);
                # 判断是否有错误
                if(!isset($detectionAuthGroup[0]) && isset($detectionAuthGroup)){
                    throw new \Exception($detectionAuthGroup[1].' '.'  ['.$baseErrorNamespace.']');
                }
                if(!$this->detectionAuthExtend($routeAuthExtend)){
                    throw new \Exception('AuthExtend illegality  ['.$baseErrorNamespace.']');
                }
                /** 准备路由数据   */
                $noteRouter = [
                    #'ParamObject'=>$routeParamObject??'',//请求对象
                    #'paramObjectPath'=>$routeParamObjectPath[1]??'',//请求对象命名空间路径
                    'resourceType' => $resourceType[1] ?? 'api',
                    'tag' => $tag,//tag路由标识
                    'Namespace' => $classNamespace,//路由请求的控制器
                    'Router' => $routerStr,//路由
                    'Param' => $routeParamData ?? '',//路由参数（url上的或者post等）
                    'Return' => $routeReturnData ?? [],//返回参数
                    'ReturnType' => $routeReturnType,//返回类型
                    'function' => $function,//控制器方法
                    'baseAuth' => $baseAuth ?? [],//权限控制器
                    'auth' => $routeBaseAuth ?? [],//路由上定义的权限控制器
                ];
                # 优化内存使用效率

                #请求类型json  array xml
                if (isset($routeParamObjectType[1])){$noteRouter['paramObjectType'] = $routeParamObjectType[1];}
                #权限扩展信息
                if (!empty($routeAuthExtend)){$noteRouter['routeAuthExtend'] = $routeAuthExtend;}
                #路由附加参数
                if (isset($routerAdded) && !empty($routerAdded)){$noteRouter['RouterAdded'] = $routerAdded;}
                #路由的权限分组
                if (isset($routeAuthGroup) && !empty($routeAuthGroup)){$noteRouter['authGroup'] = $routeAuthGroup;}
                #控制器路由的权限分组
                if (empty($this->baseAuthGroup)){$noteRouter['baseAuthGroup'] = $this->baseAuthGroup;}
                #简单路径路由用来快速匹配  、匹配使用的 正则表达式
                if ($routerType ==='Path'){
                    $noteRouter['PathNote'] = $PathNote; #简单路径路由用来快速匹配
                    $noteRouter['MatchStr'] = $matchStr??''; #匹配使用的 正则表达式
                    $noteRouter['PathParam'] = $PathParam??[]; #路径参数（路由参数如user/:id  id就是路由参数）
                }
                if($routerType == 'Rule'){
                    $this->noteRouter[$routerData[0]][$routerType][$routerStr] = $noteRouter;# 传统路由
                }else{
                    $this->noteRouter[$routerData[0]][$routerType][$matchStr] = $noteRouter;#路径路由
                }
                /** 准备文档数据【请求方法#路由路径】=【请求参数，请求返回数据，控制器方法】 */
                $routerDocumentData[$routerData[0].'#'.$routerStr] =[
                    'resourceType' => $resourceType[1] ?? 'api',
                    'requestType' => $routerData[0],//请求类型  get  post等等
                    'routerType' => $routerType,//路由类型
                    'matchStr' => $matchStr ?? '',//请求参数
                    'routerStr' => $routerStr,//路由
                    'returnExplain' => $routeReturnExplain ?? [],//返回说明
                    'Author' => $Author[1] ?? '',//方法创建人
                    'Created' => $Created[1] ?? '',//方法创建时间
                    'param' => $routeParam[1] ?? '',//请求参数
                    'return' => $routeReturnData ?? [],//返回参数
                    'function' => $function,//控制器方法
                    'explain' => $routeExplain[1] ?? '',//路由解释说明（备注）
                    'title' => $routeTitle[1] ?? '',//获取路由名称
                    'RouterAdded' => $routerAdded ?? [],//路由附加参数
                    'ParamObject' => $routeParamObject ?? '',//请求对象
                    'paramObjectPath' => $routeParamObjectPath[1] ?? '',//请求对象命名空间路径
                    'paramObjectType' => $routeParamObjectType[1] ?? '',//请求类型json  array xml
                    'PathParam' => $PathParam ?? [],//路径参数（路由参数如user/:id  id就是路由参数）
                    'Param' => $routeParamData ?? '',//路由参数（url上的或者post等）
                    'Return' => $routeReturnData ?? [],//返回参数
                    'ReturnType' => $routeReturnType,//返回类型
                    'function' => $function,//控制器方法
                ];
            }
        }
        /**拼接数据（文档数据）*/
        $this->noteBlock[$classNamespace] = [
            'title' => $controllerTitle,
            'class' => $class[1],
            'User' => $User,
            'basePath' => $basePath,
            'authGroup' => $authGroup[1] ?? [],
            'baseAuthGroup' => $this->baseAuthGroup,//路由的权限分组
            'baseAuth' => ($baseAuth[0] ?? '') . ':' . ($baseAuth[1] ?? ''),
            'route' => $routerDocumentData ?? [],
        ];
    }

    /**
     * @Author pizepei
     * @Created 2019/4/21 16:09
     *
     * @title  权限相关处理
     * @explain 一般是方法功能说明、逻辑说明、注意事项等。
     */
    protected function authDispose(&$routeAuthGroup,&$routeAuthExtend)
    {
        //* @authGroup [admin.del:user.del]删除账号操作
        //* @authExtend UserExtend:list 删除账号操作
        /**
         * 切割权限
         */
        if(isset($routeAuthGroup[1])){
            $routeAuthGroup = $routeAuthGroup[1];
            $this->processingBatch($routeAuthGroup,'.',':');
            /**
             * 检测权限分组规范
             */
            //authority
        }
        if(isset($routeAuthExtend[1])){
            $routeAuthExtend= $routeAuthExtend[1];
            $this->processingBatch($routeAuthExtend,'.',':');
        }
    }

    /**
     * 权限模型数据
     * @var array
     */
    protected $Permissions  = [];
    /**
     * @Author pizepei
     * @Created 2019/4/21 17:19
     * @param $routeAuthGroup
     * @return array
     * @title  检测权限分组并且拼接权限信息
     */
    protected function detectionAuthGroup($routeAuthGroup,$routeAuthExtend,$tag,$title,$routeExplain,$field,$AuthClassify)
    {

        if(empty($routeAuthGroup)){return [true];}

        foreach($routeAuthGroup  as $key=>$value)
        {
            if (count($value) !==1){return [false,'路由权限组格式错误'];}
            if (!isset(\BaseAuthGroup::DATA[$value[0]])){return [false,'不存在：'.$value[0],''];}
            # 处理符合数据：中处理返回的第一层数据 同时不支持objectList raw 格式
            if (!empty($field)){
                # 如果返回的是data 就使用data下层
                if (isset($field[$this->app->__INIT__['returnJsonData']])){
                    # 如果是raw 或者 objectList 就设置我[]
                    if (in_array($field[$this->app->__INIT__['returnJsonData']]['restrain'],['objectList','raw'])){
                        $field = [];
                    }else{$field = $field[$this->app->__INIT__['returnJsonData']];}
                }
                # 判断返回数据是否正常
                if (in_array($field['restrain'],['objectList','raw'])){
                    $field = [];
                }else{
                    $fieldArr = [];
                    foreach ($field['substratum']??[] as $substratumKey=>$substratumValue){
                        $fieldArr[] = [
                            'id'=>$value[0].'|'.$AuthClassify['id'].'|'.$tag.'|'.$substratumKey,#  定义的基础权限分类  控制器  方法gat   field名
                            'title'=>$substratumValue['explain'],
                            'field'=>$substratumKey,
                        ];
                    }
                    $field = $fieldArr;
                }
            }
            /**
             * 合并
             *      [模块资源]=>[del=>[路由=>''，唯一路由标识md5（命名空间+类名称+方法名称）]]
             * @authGroup admin.del:删除账号操作,user.del:删除账号操作,user.add:添加账号操作
             * @authExtend UserExtend.list:删除账号操作
             */
            foreach($routeAuthGroup as $value)
            {
                # 提前吧信息写入
                if (!isset($this->Permissions[$value[0]][$AuthClassify['id']]))
                {
                    $this->Permissions[$value[0]][$AuthClassify['id']] = $AuthClassify;
                }
                $this->Permissions[$value[0]][$AuthClassify['id']]['children'][] = [
                    #  定义的基础权限分类  控制器  方法gat   field名
                    'id'=>$value[0].'|'.$AuthClassify['id'].'|'.$tag,
                    'explain'=>$routeExplain,
                    'title'=>$title,
                    'extend'=>$routeAuthExtend,
                    'field'=>$value[0].'|'.$AuthClassify['id'].'|'.$tag,
                    'children'=>$field,
                ];
            }
            return [true];
        }
    }
    /**
     * @Author pizepei
     * @Created 2019/4/21 17:19
     * @param $routeAuthExtend
     * @title  权限格式判断
     * @return array
     */
    protected function detectionAuthExtend($routeAuthExtend)
    {
        if(empty($routeAuthExtend)){return true;}

        foreach($routeAuthExtend as $value)
        {
            if(count($value) !=2){return false;}
            if(count($value[1]) !=2){return false;}
            return [true];
        }
    }
    /**
     * @Author pizepei
     * @Created 2019/4/21 16:12
     * @param $data
     * @param $main
     * @param $lesser
     * @title  批处理固定格式数据
     * @explain 一般是方法功能说明、逻辑说明、注意事项等。
     */
    public function processingBatch( &$data, $main, $lesser)
    {
        $data = explode(',',$data);
        foreach($data as &$value){
            $value = explode($main,$value);
            foreach($value as $key=> &$valueLesser){
                $valueLesser = (count(explode($lesser,$valueLesser)) == 1)?$valueLesser:explode($lesser,$valueLesser);
            }
        }
    }
    /**
     * 切割组织返回参数
     * @param $data
     * @param $baseErrorNamespace
     * @return array|null
     * @throws \Exception
     */
    protected function setReturn($data,$baseErrorNamespace)
    {
        if(!isset($data[0])){return null;}
        /**获取第一个并且以第一个为参考**/
        preg_match('/^[ ]+/s',$data[0],$blank);//获取路由名称
        $baseBlank = $blank[0]??'';
        return $this->setReturnRecursive(strlen($baseBlank),0,$data,$baseErrorNamespace);
    }
    /**
     * 获取同级别参数详情的递归函数
     * 进行参数数据非层，进行参数约束合法性判断
     * @param $length
     * @param $i
     * @param $data
     * @param $baseErrorNamespace
     * @return array
     * @throws \Exception
     */
    protected function setReturnRecursive($length,$i,$data,$baseErrorNamespace)
    {
        /**
         * 第一个  key  + 空格长度
         *      进入递归
         *      1如果是下级别【上级别key】=【下级别key】
         */
        $count = count($data);//总循环数
        for ($x =$i;$x<=$count-1;$x++ ){
            # 获取当前级别详细$length
            preg_match('/^[ ]{'.$length.'}[A-Za-z_]/s',$data[$x],$blankJudge);
            if(empty($blankJudge)){
                /**判断 是上一层或者下一层  下下层*/
                preg_match('/[ ]+/s',$data[$x],$blankLength);//获取空格长度
                $baseBlank = $blankLength[0]??'';
                $baseBlank = strlen($baseBlank);
                if($baseBlank<$length){return $arr;}
                /**当前空格长度 $baseBlank   参考空格长度 $length   $tagBaseBlank = 上一次循环的$baseBlank长度*/
                /**判断第一次下层 进入*/
                if(isset($tagBaseBlank)){
                    /** 非第一次下层 进入 上一次循环的$baseBlank长度  <  当前空格长度*/
                    if($tagBaseBlank < $baseBlank){
                        //下下层
                        //$arr[$field]['substratum'] = $this->setReturnRecursive($baseBlank,$x,$data);
                    }else if($tagBaseBlank == $baseBlank){
                        /**同级别  判断下一个 $x 是否是下级别*/
                    }else if($tagBaseBlank > $baseBlank){/**上一层*/unset($tagBaseBlank);}
                }else{
                    /**第一次下层 进入*/
                    $tagBaseBlank = $baseBlank;
                    $arr[$field]['substratum'] = $this->setReturnRecursive($baseBlank,$x,$data,$baseErrorNamespace);
                }
            }else{
                unset($tagBaseBlank);
                /**同一层*/
                preg_match('/[ ]+(.*?)[ ]{1,5}[\[]{1}/s',$data[$x],$field);//字段
                /**这里可以考虑加入敏感关键字过滤*/
                if(!isset($field[1])){throw new \Exception($baseErrorNamespace.' @return 字段名称不正确:'.$data[$x]);}
                $field = $field[1];
                preg_match('/\[(.*?)\]{1}/s',$data[$x],$fieldRestrain);//约束
                if(!isset($fieldRestrain[1])){throw new \Exception($baseErrorNamespace.' @return 字段约束不正确:'.$data[$x]);}
                preg_match('/[\]][ ]+(.*?)$/s',$data[$x],$fieldExplain);//explain说明
                preg_match('/\s{0,}(.*?)\s{0,}$/s',$fieldRestrain[1],$test);//explain说明
                # 通过空格切割 数限制
                $restrain = array_filter(explode(' ',$fieldRestrain[1]),function ($var){if ($var!==''){return $var;}});
                /**过滤非法的数据数约束类型 **/
                foreach ($restrain as &$restrainv){
                    if (!in_array($restrainv,self::RETURN_FORMAT) && !isset(\BaseConstraint::DATA[$restrainv]) && !in_array($restrainv,self::REQUEST_PARAM_DATA_TYPE)){
                        # 非直接定义好的参数，使用：进行切割获取参数名称
                        $restrainvArray= explode(':',$restrainv);
                        if (count($restrainvArray)<=1){
                            throw new \Exception($baseErrorNamespace.' @return 字段约束不正确:'.$data[$x].' -> '.$restrainv);/**切割后格式依然错误***/
                        }else{
                            # 切割完成，判断参数是否在服务内
                            if (!isset(\BaseConstraint::DATA[$restrainvArray[0]])){throw new \Exception($baseErrorNamespace.' @return 字段约束不正确:'.$data[$x].' -> '.$restrainv.' -> '.$restrainvArray[0]);}
                        }
                        $restrainv = $restrainvArray  ;
                    }
                }
                $arr[$field] = [
                    'restrain'=>$restrain,
                    'explain'=>$fieldExplain[1]??''
                ];
            }
        }
        return $arr;
    }
    /**
     * 获取所有文件目录地址
     * @param $dir
     * @param $fileData
     */
    public function getFilePathData($dir,&$fileData)
    {
        # 打开应用目录获取所有文件路径
        if (is_dir($dir)){
            if ($dh = opendir($dir)){
                while (($file = readdir($dh)) !== false){
                    if($file != '.' && $file != '..'){
                        # 判断是否是目录
                        if(is_dir($dir.DIRECTORY_SEPARATOR.$file)){
                            $this->getFilePathData($dir.DIRECTORY_SEPARATOR.$file,$fileData);
                        }else{
                             # 判断是否是php文件
                            if(strrchr($file,'.php') == '.php'){
                                $fileData[] = $dir.DIRECTORY_SEPARATOR.$file;
                            }
                        }
                    }
                }
                closedir($dh);
            }
        }
    }
    /**
     * 启动请求转移（实例化控制器）
     * @return mixed
     * @throws \Exception
     */
    public function begin()
    {
        # 处理路由 ->路由匹配路由->实例化控制器
        return $this->noteRoute();
    }
}