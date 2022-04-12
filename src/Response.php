<?php
/**
 * Created by PhpStorm.
 * User: pizepei
 * Date: 2019/11/02
 * Time: 16:30
 * @title 框架统一响应输出类
 */
declare(strict_types=1);

namespace normphp\staging;

use normphp\model\db\Db;
use normphp\model\redis\Redis;
use normphpCore\terminalInfo\TerminalInfo;

/**
 * 为了框架的安全性和便捷性 在生产环境框架内业务代码（包括异常处理）不允许直接echo 或者var_dump  所有的响应信息通用使用本类处理
 * 在业务代码中可以直接使用本类结束业务流程直接输出响应(控制器结束)
 * Class Response
 * @package normphp\staging
 */
class Response
{
    /**
     * app容器
     * @var App | null
     */
    private $app = null;

    /**
     * 提示
     * @var string| null
     */
    private $message = null;
    /**
     * 记录
     * @var array
     */
    private $record = [];

    /**
     * 是否是错误或者异常
     * @var bool
     */
    private $error = true;

    private $Exception = false;
    /**
     * 缓冲区信息
     * @var string
     */
    private $ResponseData = false;
    /**
     * Content-Type
     * http://tool.oschina.net/commons/
     * @var array
     */
    private $HeaderType = [
        'txt'=>['Content-Type'=>'text/plain'],
        'string'=>['Content-Type'=>'text/plain'],
        'text'=>['Content-Type'=>'text/plain'],
        'png'=>['Content-Type'=>'image/png'],
        'jpg'=>['Content-Type'=>'image/jpg'],
        'html'=>['Content-Type'=>'text/html; charset=UTF-8'],
        'json'=>['Content-Type'=>'application/json;charset=UTF-8'],
        'gif'=>['Content-Type'=>'image/gif'],
        'xml'=>['Content-Type'=>'text/xml'],
        'css'=>['Content-Type'=>'text/css'],
        'js'=>['Content-Type'=>'application/javascript'],
        'xls'=>['Content-Type'=>'application/vnd.ms-excel'],
        'xlsx'=>['Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'],
    ];
    /**
     * Response constructor.
     * @param App $app
     */
    public function __construct(App $app)
    {
        $this->app = $app;
        # 合并 $HeaderType
        $this->HeaderType = array_merge($this->HeaderType,$this->app->__INIT__['HeaderType']??[]);
    }

    public function __get($name)
    {
        // TODO: Implement __get() method.
        if (isset($this->$name)){return $this->$name;}
        return null;

    }
    /**
     * @Author 皮泽培
     * @Created 2019/11/2 16:02
     * @param string $message
     * @return array [json] 定义输出返回数据
     * @title  设置message提示
     * @explain 重复实现会被覆盖
     * @throws \Exception
     */
    public function message(string $message)
    {
        $this->message = $message;
    }
    /**
     * @Author 皮泽培
     * @Created 2019/11/2 17:00
     * @param $data
     * @title  运行记录 一般是对业务进行说明 方便理解业务处理异常
     * @explain 记录可以是array 或者string 会被按照先后顺序记录
     * @throws \Exception
     */
    public function record($data){
        $this->record[] = $data;
    }

    # 所有的输出都在output_ob_start方法中
    # 使用 succeed 和error 方法输出的为业务提示输出

    # 1、修改在使用 error 方法时 返回数据为debug 字段  同时所有输出都统一添加字段  statusCode  succeed 100  异常、错误和error 为 200


    # 有控制器错误输出  有异常处理  有控制器正常输出   有资源输出

    # 可以先把需要响应的数据放入当前对象
    # 当前对象有统一的数据规格
    # 在生产时,不再直接拿异常数据显示 选择优先收集的信息

    # 业务错误 或者处理结束

    # 系统异常  系统异常使用Exception


    /**
     * @Author pizepei
     * @Created 2019/2/15 23:14
     * @Author pizepei
     * @Created 2019/2/15 23:02
     * @param     $data
     * @param string $msg 状态说明
     * @param string $code 状态码
     * @param int $count
     * @return void
     * @throws \Exception
     * @title  控制器成功返回
     */
    public function succeed($data,$msg='', $code='', $count=0)
    {
        # 判断是否是微服务资源路由，是就写入日志
        if ($this->app->Route()->resourceType === 'microservice') $this->app->Authority->setMsAppsResponseLog($data);
        $result =  [
            $this->app->__INIT__['SuccessReturnJsonMsg']['name']=>$msg==''?$this->app->__INIT__['SuccessReturnJsonMsg']['value']:$msg,
            $this->app->__INIT__['SuccessReturnJsonCode']['name']=>$code==''?$this->app->__INIT__['SuccessReturnJsonCode']['value']:$code,
            $this->app->__INIT__['returnJsonData']=>$data,
        ];
        if($count>0){
            $result[$this->app->__INIT__['ReturnJsonCount']] = $count;
        }else{
            $result[$this->app->__INIT__['ReturnJsonCount']] = is_array($data)?count($data):0;
        }
        # statusCode 成功 200  错误失败  100   主要用来统一请求需要状态 是框架固定的 代表是succeed 还是 error或者异常
        $result['statusCode'] = 200;
        $this->error = false;
        $this->output($result);
    }

    /**
     * @Author pizepei
     * @Created 2019/2/15 23:09
     * @param string $msg 错误说明
     * @param string $code 错误代码
     * @param array $data 错误详细信息
     * @return void
     * @throws \Exception
     * @title  控制器错误返回
     */
    public function error($msg='',$code='',$data=[])
    {
        if ($this->app->Route()->resourceType === 'microservice'){
            $this->app->Authority->setMsAppsResponseLog($data);
        }
        $result =  [
            $this->app->__INIT__['ErrorReturnJsonMsg']['name']=>$msg==''?$this->app->__INIT__['ErrorReturnJsonMsg']['value']:$msg,
            $this->app->__INIT__['ErrorReturnJsonCode']['name']=>$code==''?$this->app->__INIT__['ErrorReturnJsonCode']['value']:$code,
            $this->app->__INIT__['returnJsonData']=>$data,
        ];
        # statusCode 成功 200  错误失败  100   主要用来统一请求需要状态 是框架固定的 代表是succeed 还是 error 或者异常
        $result['statusCode'] = 100;
        $this->error = true;
        $this->output($result);
    }

    /**
     * @Author 皮泽培
     * @Created 2019/11/4 11:01
     * @param $data
     * @title  构造返回数据（内置方法会根据环境加入系统环境信息）
     * @throws \Exception
     */
    public function output($data)
    {
        # 获取调试debug数据
        $debug = $this->app->Route()->atRouteData['RouterAdded']['debug']??'default';

        //http://tool.oschina.net/commons/
        switch ($this->app->Route()->ReturnType) {
            case 'json':
                $result = $this->returnJson($data,$debug);
                break;
            case 'xml':
                $result = $data;
                break;
            case 'html':
                $result = $this->returnHtml($data,$debug);
                break;
            case 'gif':
                $result = $data;
                break;
            case 'js':
                $result = $data;
                break;
            case 'txt':
                $result = $data;
                break;
            case 'text':
                $result = $data;
                break;

            default:
                $result = $data;
        }

        $result = is_array($result)?Helper()->json_encode($result):$result;

        $this->ResponseData = $result??'';


        # 使用异常结束当前业务
        throw new \Exception();
    }

    /**
     * @Author pizepei
     * @Created 2019/6/12 21:54
     * @param $data
     * @param $debug
     * @throws \Exception
     * @title  返回json
     */
    protected function returnJson($data,$debug)
    {

        # 判断是否有数据
        if ($data !==null || $data !=='' ||$data !==[])
        {   # 有数据  对数据进行处理

            # 判断是否是array
            if (is_array($data)){
                # 是array
                if(isset($data[$this->app->__INIT__['returnJsonData']]) && isset($data[$this->app->__INIT__['SuccessReturnJsonCode']['name']]) && isset($this->app->__INIT__['SuccessReturnJsonMsg']['name']))
                {

                    # 正常使用方法返回的格式化数据  在过滤后 强制把数据写入returnJsonData中
                    # 如果不是error 100 方法的异常   就进行数据过滤
                    if ($data['statusCode'] !== 100){  $this->app->Request()->returnParamFiltration($data[$this->app->__INIT__['returnJsonData']]); }
                }else{
                    # 控制器直接return的数据
                    # 如果不是error 100 方法的异常   就进行数据过滤
                    if (!isset($data['statusCode'])){ $this->app->Request()->returnParamFiltration($data);}
                    if (isset($data['statusCode']) && $data['statusCode'] !== 100){ $this->app->Request()->returnParamFiltration($data);}
                }
            }else{
                # 不是array 是控制器return的是字符串 （使用异常或者succeed方法等的$data都是array   但是进入此方法的都是控制器路由定义为返回json的资源)
                $data = [
                    $this->app->__INIT__['returnJsonData'] =>$data,
                ];
            }
        }
        # 判断是否是开发模式 不是 判断是否路由单独开启 调试模式    如果是开发模式 路由单独关闭debug 也会关闭调试模式
        if (($this->app->__EXPLOIT__ || $debug ==='true' ) && $debug !=='false' ){
            $data['SYSTEMSTATUS'] = $this->getSystemStatus();
        }
        $xhprofData = xhprof_disable();
        $data['SYSTEMSTATUS']['XHPROF'] = $xhprofData;
        return Helper()->json_encode($data);
    }

    /**
     * @Author pizepei
     * @Created 2019/6/12 21:55
     * @param $data
     * @return mixed
     * @title  返回html
     */
    protected function returnHtml($data,$debug)
    {
        # 如果依然是一个数组 就序列化为json
        if (is_array($data)){
            $this->setHeader('json');
            return $this->returnJson($data,$debug);
        }
        return $data;
    }

    /**
     * @Author pizepei
     * @Created 2019/6/12 21:55
     * @param $data
     * @return mixed
     * @title  返回字符串
     * @explain 一般是方法功能说明、逻辑说明、注意事项等。
     */
    protected function returnString($data)
    {
        return $data;
    }

    public function output_ob_start($data='')
    {

        if ($this->app->__PATTERN__ == 'WEB'){

            # 判断是否是开发调试模式   开发调试模式下 不清除项目内的echo 等输出信息
            if(!$this->app->__EXPLOIT__){
                # 非开发调试模式  屏蔽所有之前的输出缓存
                if (ob_get_contents()) ob_end_clean();
            }
            ob_start(array($this, 'ob_start'));
            echo $data===''?$this->ResponseData:$data;
            ob_end_flush();
            exit();
        }else{

            echo $data===''?$this->ResponseData:$data;
        }

    }
    public function ob_start($string){
        return  $string;
    }

    /**
     * @Author 皮泽培
     * @Created 2019/11/2 17:28
     * @param $header
     * @title  设置响应头部
     */
    public function setHeader($header)
    {
        if($this->app->__PATTERN__ === 'WEB'){
            if (!is_array($header)){$header = $this->HeaderType[$header];}
            foreach ($header as $k=>$v){ @header("{$k}: {$v}");}
        }
    }


    /**
     * @Author 皮泽培
     * @Created 2019/8/14 16:42
     * @return array
     * @title  获取系统状态
     * @throws \Exception
     */
    protected function getSystemStatus():array
    {
        $data['requestId'] = $this->app->__REQUEST_ID__;
        if (in_array('controller',$this->app->__INIT__['SYSTEMSTATUS'])){
            $data['controller'] = $this->app->Route()->controller;#路由控制器
        }
        if (in_array('function_method',$this->app->__INIT__['SYSTEMSTATUS'])){
            $data['function_method'] = $this->app->Route()->method;#控制器方法
        }
        if (in_array('request_method',$this->app->__INIT__['SYSTEMSTATUS'])){
            $data['request_method'] =$_SERVER['REQUEST_METHOD'];#请求方法 get post
        }
        if (in_array('request_url',$this->app->__INIT__['SYSTEMSTATUS'])){
            $data['request_url'] = $_SERVER['REQUEST_URI'];#完整路由（去除域名的url地址）
        }
        if (in_array('route',$this->app->__INIT__['SYSTEMSTATUS'])){
            $data['route'] = $this->app->Route()->atRoute;#解释路由
        }
        if (in_array('sql',$this->app->__INIT__['SYSTEMSTATUS'])){
            if (class_exists('Db')){$data['sql'] = isset(Db::$DBTABASE['sqlLog'])?Db::$DBTABASE['sqlLog']:'';} #历史slq
        }
        if (in_array('clientInfo',$this->app->__INIT__['SYSTEMSTATUS'])){ # clientInfo 客户端信息
            if ($this->app->__INIT__['clientInfo'] && $this->app->__PATTERN__ !== 'CLI'){
                terminalInfo::$redis = Redis::init();
                $data['clientInfo'] = terminalInfo::agentInfoCache(true);
            }else{
                $data['clientInfo'] = $this->app->__CLIENT_IP__;
            }
        }
        if (in_array('system',$this->app->__INIT__['SYSTEMSTATUS'])){ # 系统运行状态
            $data ['OS'] = php_uname('s').' '.php_uname('r');
            $data ['PHP_VERSION'] = PHP_VERSION;#系统版本
        }

        if (in_array('memory', $this->app->__INIT__['SYSTEMSTATUS']) ){ # 系统运行状态  内存
            $data ['memory_began'] =  sprintf("%.3f",$this->app->memory_began);#进入框架开始时的内存
            $data ['memory_staging'] =  sprintf("%.3f",$this->app->memory_staging);#框架使用的基本内存（进入控制器前的内存）
            $data ['memory_over'] = sprintf("%.3f",memory_get_usage()/1024);#框架结束时的大致内存
            $data ['memoryp_peak'] = sprintf("%.3f",memory_get_peak_usage()/1024);#峰值内存
            $data ['memoryp_usage'] =  sprintf("%.3f",memory_get_peak_usage(true)/1024);#系统分配的内存
        }
        $data ['perform_time'] = round(microtime(true)-($_SERVER['REQUEST_TIME_FLOAT']),4);#执行耗时(S)
        return $data;
    }

}