<?php
/**
 * Created by PhpStorm.
 * User: pizepei
 * Date: 2018/8/2
 * Time: 17:48
 * @title 控制器基类
 */
declare(strict_types=1);

namespace normphp\staging;

class Controller
{
    protected static $__FILE__ = __FILE__;
    /**
     * 基础控制器信息
     */
    const CONTROLLER_INFO = [
        'User'=>'system',
        'title'=>'',//控制器标题
        'namespace'=>'',//门面控制器命名空间：同时也是文件路径
        'baseControl'=>'',//基础控制器路径
        'baseAuth'=>'',//基础权限继承（加命名空间的类名称）
        'authGroup'=>'',//[user:用户相关,admin:管理员相关] 权限组列表
        'basePath'=>'',//基础路由
        'baseParam'=>'',//依赖注入对象
    ];
    /**
     * 应用容器
     * @var App|null
     */
    protected $app = null;
    /**
     * 权限路由的实例
     * @var \normphp\staging\authority\Authority |null
     */
    protected $Authority = null;
    /**
     * 权限控制器 start()方法返回数据
     * @var null
     */
    protected $authResult = null;
    /**
     * 用户数据
     * @var |null
     */
    protected $UserInfo = null;
    /**
     * 当前用户的ACCESS_TOKEN
     * @var string
     */
    protected $ACCESS_TOKEN = '';
    /**
     * 当前用户jwt签名部分
     * @var string
     */
    protected $ACCESS_SIGNATURE = '';
    /**
     * Controller constructor.
     * @param App $app
     */
    public function __construct(App &$app)
    {
        $this->app = $app;
        $Route = $this->app->Route();
        # 判断是否有设置权限资源器
        #  baseAuth[0] 是权限资源类   baseAuth[1] 类的方法
        if(isset($Route->baseAuth[0]) && isset($Route->baseAuth[1]) && $Route->baseAuth[1] !='public')
        {
            $className = $Route->baseAuth[0];
            # 从Authority子容器中实例化一个权限资源对象   实例化时传入的参数有等 思考确定
            $this->Authority = $this->app->Authority()->$className($this->app,'common');

            $app->Authority = $this->Authority;
            # 实例化对象后 调用start方法启动权限处理
            $this->authResult = $this->Authority->start($Route->baseAuth[1],[],[]);
            # 权限控资源对象中获取必要的数据到控制器中
                # 思考：是否一些时间不需要放到控制器中？
                # 思考：是否直接访问权限资源对象就可以？
            $this->authExtend = $app->Authority->authExtend;
            $this->UserInfo = $app->Authority->UserInfo;
            $this->Payload = $app->Authority->Payload;
            $this->ACCESS_TOKEN= $app->Authority->ACCESS_TOKEN;
            $this->ACCESS_SIGNATURE= $app->Authority->ACCESS_SIGNATURE;
        }
    }
    /**
     * 获取Extend
     * @param string $key
     * @return mixed
     */
    public function getAuthExtend(string $key ='')
    {
        return $this->jurisdictionExtend[$key]??null;
    }
    /**
     * @Author 皮泽培
     * @Created 2019/11/13 16:29
     * @return string
     * @title  获取基础控制器路径
     * @throws \Exception
     */
    public static function getBasicsPath():string
    {
        return static::$__FILE__ ?? '';
    }
    /**
     * @Author 皮泽培
     * @Created 2019/10/22 10:53
     * @param string $name 文件名称
     * @param array $data  需要替换的数据
     * @param string $path  文件路径
     * @param string $type  文件扩展名
     * @param bool $safe    加载文件路径时是否使用安全模式
     * @return string
     * @title  视图
     * @explain 视图加载
     * @throws \Exception
     */
    public function view(string $name = '', array$data = [], string $path='', string $type='html', bool $safe=true):string
    {
        $path = $path==''?
            $this->app->__TEMPLATE__.str_replace('\\',DIRECTORY_SEPARATOR,ltrim($this->app->Route()->controller, $this->app->__APP__.'\\')).DIRECTORY_SEPARATOR:
            ($safe?$this->app->__TEMPLATE__:'').$path.DIRECTORY_SEPARATOR;
        $name = $name==''?
            $this->app->Route()->method:
            $name;
        $file = file_get_contents($path.$name.'.'.$type);
        if(!empty($data))
        {
            foreach($data as $key=>$value)
                if(!is_array($value)){
                    $file = str_replace("'{{{$key}}}'",$value,$file);
                    $file = str_replace("{{{$key}}}",$value,$file);
                }
        }
        return $file;
    }
    /**
     * @Author pizepei
     * @Created 2019/2/15 23:14
     * @Author pizepei
     * @Created 2019/2/15 23:02
     * @param     $data
     * @param     $msg 状态说明
     * @param     $code 状态码
     * @param int $count sss
     * @return array
     * @title  控制器成功返回
     */
    public function succeed($data, string $msg='',$code='',$count=0)
    {
        $this->app->Response()->succeed($data,$msg,$code,$count);
    }
    /**
     * @Author pizepei
     * @Created 2019/2/15 23:09
     * @param $msg  错误说明
     * @param $code  错误代码
     * @param $data 错误详细信息
     * @return array
     * @title  控制器错误返回
     */
    public function error($msg='',$code='',$data=[])
    {
        return $this->app->Response()->error($msg,$code,$data);
    }
    /**
     * @Author 皮泽培
     * @Created 2019/12/4 13:02
     * @param string $url
     * @param int $code
     * @title  重定向
     * @explain 重定向
     * @throws \Exception
     */
    public function redirect(string $url,int $code=302)
    {
        header('content-type:text/html;charset=uft-8');
        header('Location: '.$url);
        $this->error('重定向');
    }
}