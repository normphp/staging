<?php
declare(strict_types=1);
namespace normphp\staging\scripts;
use Composer\Script\Event;
use normphp\helper\Helper;

/**
 * Composer 操作的触发事件脚本
 * Class ToolScripts
 */
class ToolScripts
{
    /**
     * 操作系统
     * @var string
     */
    public static $__OS__ = 'linux';

    /**
     * 是否使用 normphp/normphp-helper-tool
     * @var bool
     */
    public static $isNormphpHelperTool =false;
    /**
     * (getcwd()).DIRECTORY_SEPARATOR
     * @var string
     */
    public static $PDW = DIRECTORY_SEPARATOR;
    /**
     * Helper
     * @var Helper
     */
    public static $Helper = Helper::class;

    /**
     * 初始化
     * @param Event $event
     */
    public static function init(Event $event)
    {
        self::$PDW =  (getcwd()).DIRECTORY_SEPARATOR;
        self::$Helper = new Helper();
        if (DIRECTORY_SEPARATOR ==='\\'){ self::$__OS__ = 'widnows';}
        echo "------------------------------------".PHP_EOL;
        echo "PWD:      ".self::$PDW.PHP_EOL;
        echo "__OS__:   ".self::$__OS__.PHP_EOL;
        echo "-----------------------------------".PHP_EOL;
    }
    /**
     *
     * 判断是否使用 normphp/normphp-helper-tool
     * @param Event $event
     */
    public static function isRequires(Event $event,$name)
    {
        if(isset($event->getComposer()->getPackage()->getRequires()[$name])){
            return true;
        }
        return false;
    }
    /**
     * composer update
     * @param Event $event
     */
    public static function postUpdateCmd(Event $event)
    {
        self::init($event);
        # 判断并创建 public 文件夹
        self::createPublic($event);
        # 判断并创建 public 文件
        self::createContainer($event);
    }
    /**
     * composer install
     * @param Event $event
     */
    public static function postInstallCmd(Event $event)
    {
        self::init($event);
        # 判断并创建 public 文件夹
        self::createPublic($event);
        # 判断并创建 public 文件
        self::createContainer($event);
    }
    /**
     * composer run-script post-install-cmd
     * public 文件夹 模板信息
     * key：为文件名称  must：包命名空间（在什么依赖存在时创建） title：文件标题 OS：在什么系统环境下创建widnows|linux
     */
    const PUBLIC_FILE_TPL =[
        '404.php'=>['must'=>'public','title'=>'404','OS'=>'all'],
        'index.php'=>['must'=>'public','title'=>'web index','OS'=>'all'],
        'index_cli.php'=>['must'=>'public','title'=>'cli index','OS'=>'all'],
        'normphp'=>['must'=>'normphp/normphp-helper-tool','title'=>'cli index','OS'=>'widnows'],
        'normphp.bat'=>['must'=>'normphp/normphp-helper-tool','title'=>'cmd cli index','OS'=>'widnows'],
    ];
    /**
     * 判断并创建 public 文件夹内容
     * @param Event $event
     */
    public static function createPublic(Event $event)
    {
        $time = date('Y-m-d H:i:s');
        $dataTpl = [
            'Author' => 'normphp staging',
            'Date' => $time,
            'ModifiedBy' => 'normphp staging',
            'ModifiedTime' => $time,
        ];
        foreach (self::PUBLIC_FILE_TPL as $key=>$value){
            # 判断需要的依赖包是否存在 存在就继续判断写入  判断依赖的环境
            if (($value['must'] === 'public' || self::isRequires($event,$value['must'])) && (self::$__OS__ === $value['OS'] || $value['OS'] === 'all' ) ){
                # 如果没有定义模板，就使用key做模板
                $value['tpl'] = $value['tpl']??$key;
                $data['Title'] = $value['title'];
                if ($key==='normphp.bat'){
                    $data['phpPath'] = dirname(getcwd(),1).DIRECTORY_SEPARATOR.'php'.DIRECTORY_SEPARATOR.'8.0'.DIRECTORY_SEPARATOR.'x86'.DIRECTORY_SEPARATOR.'php';
                }
                $data = array_merge($dataTpl,$data);
                $tpl = file_get_contents(__DIR__.DIRECTORY_SEPARATOR.'template'.DIRECTORY_SEPARATOR.'public'.DIRECTORY_SEPARATOR.$value['tpl']);
                self::$Helper->str()->str_replace($data,$tpl);
                self::$Helper->file()->createDir(self::$PDW.'public'.DIRECTORY_SEPARATOR);
                # 准备数据，准备模板、写入
                file_put_contents(self::$PDW.'public'.DIRECTORY_SEPARATOR.$key,$tpl);
            }
        }
    }

    /**
     * 需要处理的容器模板
     */
    const Container =[
        'AppContainer'      =>      ['CONTAINER_NAME'=>'App','extends'=>'\normphp\staging\App','explain'=>'@methodstatic static 类   方法   [可选不填写就可非static方法] File[返回数据类型 可以是类 或者其他的比如self当前来]  test(string $question) [函数详情]'],
        'HelperContainer'   =>      ['CONTAINER_NAME'=>'Helper','extends'=>'','explain'=>'helper扩展类：该类不会实例化主要是为了方便绑定容器和适配ide，适配ide必须添加对应的@method 或者 @property'],
        'AuthorityContainer'   =>      ['CONTAINER_NAME'=>'Authority','extends'=>'','explain'=>'权限资源验证容器集合'],
    ];

    /**
     * 创建Container适配器
     * @param Event $event
     * @throws \Exception
     */
    public static function createContainer(Event $event)
    {
        $time = date('Y-m-d H:i:s');
        # 搜索包中存在的定义容器的目录
        self::$Helper->getFilePathData(self::$PDW.'vendor',$pathData,'.json','container.json');
        $array = [];
        foreach ($pathData as $key=>$value){
            $packageInfo = json_decode($value['packageInfo'],true);
            if (empty($packageInfo)){
            }else{
                foreach ($packageInfo['bind'] as $bindKey=>$bindValue){
                    if (empty($bindValue)){
                        $array[$bindKey][] =  [];
                    }
                    foreach ($bindValue as $k=>$v){
                        if ($k ==='HelperContainer'){
                            var_dump($bindValue);
                        }
                        $v['author'] = $packageInfo['author'];
                        $v['title'] = $packageInfo['title'];
                        $v['packageExplain'] = $packageInfo['packageExplain'];
                        $array[$bindKey][$v['name']] =  $v;
                    }
                }
            }
        }
        # 集合拼接成AppContainer.php文件，写入定义的文件夹
        foreach ($array as $key=>$value)
        {
            $methodStr = '';
            $bindStr = '';
            $data = [
                'class'=>$key,
                'extends'=>'',
                'CONTAINER_NAME'=>'',
                'method'=>'',
                'bind'=>'',
                'ModifiedBy' => 'normphp staging',
                'ModifiedTime' => $time,
            ];
            foreach ($value as $k=>$v){
                if (!empty($v)){
                    # 拼接 method   * @method  HelperContainer                 Helper() 应用层次的Helper容器
                    $methodStr  .= PHP_EOL.' * @method  '.$v['class'].'                 '.$v['method'].' '.$v['explain'];
                    # 拼接 bind  '            Helper'=>HelperContainer::class,//应用层次的Helper容器
                    $bindStr    .= PHP_EOL."        '{$v['name']}'    =>  {$v['class']}::class, //{$v['explain']}";
                }
            }
            $data['method'] = $methodStr;
            $data['bind'] = $bindStr;
            $tpl = file_get_contents(__DIR__.DIRECTORY_SEPARATOR.'template'.DIRECTORY_SEPARATOR.'container'.DIRECTORY_SEPARATOR.'container.php');
            if (self::Container[$key]){
                if (!empty(self::Container[$key]['extends'])){$data['extends'] = 'extends '.self::Container[$key]['extends'];}
                if (!empty(self::Container[$key]['CONTAINER_NAME'])){$data['CONTAINER_NAME'] = self::Container[$key]['CONTAINER_NAME'];}
            }else{

            }
            /**替换模板**/
            self::$Helper->str()->str_replace($data,$tpl);
            /**创建目录**/
            self::$Helper->file()->createDir(self::$PDW.'container'.DIRECTORY_SEPARATOR.'app'.DIRECTORY_SEPARATOR);
            # 准备数据，准备模板、写入
            file_put_contents(self::$PDW.'container'.DIRECTORY_SEPARATOR.'app'.DIRECTORY_SEPARATOR.$key.'.php',$tpl);
        }
    }
}