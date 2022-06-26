<?php
/**
 * Created by PhpStorm.
 * User: pizepei
 * Date: 2019/4/16
 * Time: 17:25
 * @title 异常处理类配合set_exception_handler
 */
declare(strict_types=1);

namespace normphp\staging;

use Monolog\Logger;
use Monolog\Handler\StreamHandler;

class MyException
{
    /**
     * 异常对象
     * @var null
     */
    //private $exception = null;
    /**
     * 应用容器
     * @var App|null
     */
    protected $app = null;
    /**
     * json_encode 函数配置
     * @var int
     */
    protected $json_encode = 320;

    /**
     * 无权限权限状态码
     */
    const JURISDICTION_CODE = 40003;
    /**
     * 没有登录状态码
     */
    const NOT_LOGGOD_IN_CODE = 10001;

    /**
     * 错误区间s
     */
    const CODE_SECTION = [
        'SYSTEM_CODE'=>[
            10000,49999//系统 错误代码  10000-49999
        ],
        'USE_CODE'=>[
            50000,99999//应用 错误代码  50000-99999
        ]
    ];

    const HINT_MSG = [
        '1',
        '请稍后再试！',
        '请联系客服！',
        '去联系管理员！',
        '请截图联系客服！',
        '系统异常，请稍后再试！',
        '网络繁忙，请稍后再试！',
        '活动火爆，请稍后再试！',
        '异常操作，请稍后再试！'
    ];

    /**
     * 系统框架10000-49999
     *
     * 友善提示【错误代码】
     */
    const SYSTEM_CODE =[
        //代码  友善提示，联系方式[开发负责人]，开发提示
        10000=>[5,'错误说明','功能模块','联系方式[开发负责人]'],
        10001=>[5,'项目部署时获取远处配置中心配置时构建加密时出现错误','项目部署获取配置','联系方式[pizepei]'],
        10002=>[5,'项目部署时获取远处配置中心配置时构建签名时出现错误','项目部署获取配置','联系方式[pizepei]'],
        10003=>[5,'SAAS配置路径必须','SAAS配置','联系方式[pizepei]'],
        10004=>[5,'SAAS配置路径必须','请求配置中心成功就行body失败','联系方式[pizepei]'],
        10005=>[5,'初始化配置失败：请求配置中心失败','SAAS配置','联系方式[pizepei]'],
        10006=>[5,'初始化配置失败：非法请求,服务不存在不存在','SAAS配置','联系方式[pizepei]'],
        10007=>[5,'初始化配置失败：非法的请求源','SAAS配置','联系方式[pizepei]'],
        10008=>[5,'初始化配置失败：签名验证失败','SAAS配置','联系方式[pizepei]'],
        10009=>[5,'初始化配置失败：解密错误','SAAS配置','联系方式[pizepei]'],
        10010=>[5,'初始化配置失败：appid or domain 不匹配','SAAS配置','联系方式[pizepei]'],
        10011=>[5,'初始化配置失败：构造配置时 signature','SAAS配置','联系方式[pizepei]'],
        10012=>[5,'初始化配置失败：数据过期','SAAS配置','联系方式[pizepei]'],
        10013=>[5,'初始化配置失败：得到构造配置时 signature','SAAS配置','联系方式[pizepei]'],
    ];

    /**
     * 系统异常是的默认错误
     * @var string
     */
    private $errorReturnJsonMsgName ='msg';
    private $errorReturnJsonCodeName = 'code';
    private $errorReturnJsonDataName = 'data';
    private $errorReturnJsonCodeValue = 100;
    private $errorReturnJsonMsgValue= 'error';
    /**
     * @var string
     */
    private $path;

    /**
     * MyException constructor.
     * @param App $app
     * @param string $path
     * @param string $exception
     * @param array $info
     */
    public function __construct(App $app,string $path,$exception=null,array$info=[]) {
        $this->app  =$app;
        $this->path = $path;
        $this->info = $info;
        #判断是否已经加载配置文件
        if (class_exists('\Config')){
            $this->json_encode = \Config::UNIVERSAL['init']['json_encode'];
            $this->errorReturnJsonMsgName = $this->app->__INIT__['ErrorReturnJsonMsg']['name']??$this->errorReturnJsonMsgName;
            $this->errorReturnJsonMsgValue= $this->app->__INIT__['ErrorReturnJsonMsg']['value']??$this->errorReturnJsonMsgValue;

            $this->errorReturnJsonCodeName = $this->app->__INIT__['ErrorReturnJsonCode']['name']??$this->ErrorReturnJsonCodeName;
            $this->errorReturnJsonCodeValue = $this->app->__INIT__['ErrorReturnJsonCode']['value']??$this->errorReturnJsonCodeValue;

            $this->errorReturnJsonDataName = $this->app->__INIT__['returnJsonData']??$this->errorReturnJsonDataName;
        }
        $this->exception = $exception;
        if($exception){
            $this->PDO_exception_handler($exception);
        }

        @set_exception_handler(array($this, 'exception_handler'));
        @set_error_handler(array($this, 'error_handler'));
        //throw new Exception('DOH!!');error_get_last
    }

    /**
     * 错误处理
     * @param $errno 错误编号
     * @param $errstr 错误消息
     * @param $errfile 错误所在的文件
     * @param $errline 错误所在的行
     */
    public function error_handler($errno, $errstr, $errfile, $errline)
    {
        if($this->app->__PATTERN__ === 'WEB'){
            header("Content-Type:application/json;charset=UTF-8");
        }
        # 错误代码方便查日志
        $str_rand = $this->app->Helper()->str()->str_rand(20);

        if(!$this->app->has('Route')){
            $route = [
                'controller'=>'system',
                'router'=>$_SERVER['PATH_INFO']??'',
            ];
        }
        # 系统错误统一 50000
        $result = [
            $this->errorReturnJsonCodeName =>50000,
        ];
        #判断是否是开发模式
        if($this->app->__EXPLOIT__){
            # 开发模式
            $result[$this->errorReturnJsonMsgName] = $errstr.'['.$errno.']';
            $result[$this->errorReturnJsonDataName] = [
                'route'=>$route??[
                    'controller'=>$this->app->Route()->controller.'->'.$this->app->Route()->method,
                    'router'=>$this->app->Route()->atRoute,
                ],
                'File'=>str_replace(getcwd(), "", $errfile).'['.$errline.']',
            ];
        }else{
            /**
             * 生产模式
             */
            $result[$this->errorReturnJsonMsgName] = '系统繁忙['.$str_rand.']';
        }
        $result['error'] = $str_rand;
        $result['statusCode'] = 100;
        $this->createLog($result);
        $this->app->Response($this->app)->output_ob_start(json_encode($result,$this->json_encode));
    }

    /**
     * 异常类
     * @var null
     */
    private $exception = null;
    /**
     * 错误代码（用来排查异常处理，error和succeed方法为业务逻辑，不自动生成排查代码，一般在生产环境时异常都统一调试系统错误这个时候会有错误比较短的代码提供显示方便用户反馈）
     * @var string
     */
    private $errorCode = '';
    /**
     * 常规
     * @param $exception
     */
    public function exception_handler($exception) {

        $this->exception = $exception;
        if ($this->app->Response($this->app)->ResponseData !==false){
            $this->app->Response()->output_ob_start();
        }else{
            $this->errorCode = $this->app->Helper()->str()->str_rand(15);
            if($this->app->__PATTERN__ === 'WEB') {
                header("Content-Type:application/json;charset=UTF-8");
            }
            # 判断是否是开发模式
            if($this->app->__EXPLOIT__){
                # 开发模式
                $this->app->Response()->output_ob_start($this->exploit($exception));
            }else{
                # 生产模式
                $this->app->Response()->output_ob_start($this->production($exception));
            }
        }
    }
    /**
     * PDO
     * @param $exception
     */
    public function PDO_exception_handler($exception) {

        if($this->app->__PATTERN__ === 'WEB') {
            header("Content-Type:application/json;charset=UTF-8");
        }
        $this->exception = $exception;
        $this->errorCode = $this->app->Helper()->str()->str_rand(15);
        # 判断是否是开发模式
        if($this->app->__EXPLOIT__){
            # 开发模式
            $this->app->Response()->output_ob_start($this->exploit($exception));
        }else{
            # 生产模式
            $this->app->Response()->output_ob_start($this->production($exception));
        }
    }

    /**
     * 生产环境下
     * @param $exception
     * @return false|string
     */
    private function production($exception)
    {
        return json_encode($this->setCodeCipher(),$this->json_encode);
    }

    /**
     * 开发调试环境下
     * @param $exception
     * @return false|string
     */
    private function exploit($exception)
    {
        # 错误信息里面如果有无法识别的信息 会什么都没有$this->exception->getMessage()
        return json_encode($this->resultData($this->exception->getMessage(),$this->exception->getCode(),$this->exploitData()),$this->json_encode);
    }

    /**
     * @param       $msg
     * @param       $code
     * @param array $data
     * @return array
     */
    private  function resultData($msg,$code,$data=[])
    {
        $result =  [
            $this->errorReturnJsonMsgName   =>$msg,
            $this->errorReturnJsonCodeName =>$code==0?$this->errorReturnJsonCodeValue:$this->exception->getCode(),
            $this->errorReturnJsonDataName              =>$this->app->Response()->ResponseData,
            'statusCode'                => 100,# statusCode 成功 200  错误失败  100   主要用来统一请求需要状态 是框架固定的 代表是succeed 还是 error或者异常
            'errorCode'                 =>$this->errorCode,
            'debug'                     =>json_encode($data,$this->json_encode)?$data:'', # 异常调试
        ];

        return $result;
    }


    /**
     * 开发时的异常处理（data）
     * @return array
     */
    private function exploitData()
    {
        # 判断路由是否初始化
        if(!$this->app->has('Route')){
            $route = [
                'controller'=>'system',
                'router'=>$_SERVER['PATH_INFO']??'',
            ];
        }
        return [
            'route'=>isset($route)?$route:[
                'controller'=>($this->app->Route($this->app)->controller??'').'->'.($this->app->Route()->method??''),
                'router'=>$this->app->Route()->atRoute,
            ],
            'sql'=>isset($GLOBALS['DATABASE']['sqlLog'])?$GLOBALS['DATABASE']['sqlLog']:'',
            'File'=>str_replace(getcwd(), "", $this->exception->getFile()).'['.$this->exception->getLine().']',
            'Trace'=>$this->getTrace(20),
        ];
    }
    /**
     * @Author pizepei
     * @Created 2019/4/16 23:01
     * @param int $tier
     * @return array
     * @title  获取痕迹方法 默认6级
     * @explain
     */
    private function getTrace(int $tier=3)
    {
        $array =[];
        foreach($this->exception->getTrace() as $key=>$value)
        {
            if($key>($tier-1)){
                break;
            }
            if($key != 0){
                unset($value['args']);
            }
            $array[] = $value;
        }
        return $array;
    }
    /**
     * 创建错误代码
     * 用以生产
     * @param array $error_code
     * @return array
     */
    protected function setCodeCipher()
    {

        # 判断是否是权限问题和登录问题(前端需要统一判断)
        if(self::NOT_LOGGOD_IN_CODE??100 == $this->exception->getCode()   || self::JURISDICTION_CODE??100  == $this->exception->getCode() ){
            $result =  [
                $this->errorReturnJsonMsgName  =>$this->exception->getMessage(),
                $this->errorReturnJsonCodeName =>$this->exception->getCode(),
                'errorCode'                                         =>$this->errorCode,
                'statusCode'                                        => 100,
            ];
        }

        # 这里暂时没有发现是做什么的
        if($this->info){
            if(isset($this->info[$this->exception->getCode()])){
                $result =  [
                    $this->errorReturnJsonMsgName  =>$this->info[$this->exception->getCode()][0].'['.$this->errorCode.']',
                    $this->errorReturnJsonCodeName =>$this->exception->getCode()==0?$this->errorReturnJsonCodeValue:$this->exception->getCode(),
                    'errorCode'                                         =>$this->errorCode,
                    'statusCode'                                        => 100,
                ];
            }else{
                $result =  [
                    $this->errorReturnJsonMsgName      =>'系统繁忙['.$this->errorCode.']',
                    $this->errorReturnJsonCodeName    =>$this->exception->getCode()==0?($this->errorReturnJsonCodeValue):$this->exception->getCode(),
                ];
            }
            $result['errorCode']    = $this->errorCode;
            $result['statusCode']   = 100;

            return $result;
        }
        # 确定错误代码：在生产模式下

        # 异常错误默认code为0
        if($this->exception->getCode() === 0)
        {
            $result =  [
                $this->errorReturnJsonMsgName  =>'系统繁忙['.$this->errorCode.']',
                $this->errorReturnJsonCodeName =>$this->errorReturnJsonCodeValue,
                'errorCode'                                         => $this->errorCode,
                'statusCode'                                        => 100,
            ];
        }
        # 判断错误代码错误区间（在抛异常时定义了code时 检测是系统级别的区间还是开发者定义的错误代码区间）
        foreach(self::CODE_SECTION as $key=>$value)
        {
            # 判断范围
            if($value[0] < $this->exception->getCode() &&  $this->exception->getCode()<$value[1])
            {
                if($key === 'SYSTEM_CODE')
                {

                    if(isset(self::SYSTEM_CODE[$this->exception->getCode()]))
                    {
                        $result =  [
                            $this->errorReturnJsonMsgName=>
                                is_int(self::SYSTEM_CODE[$this->exception->getCode()][0])?
                                    self::HINT_MSG[self::SYSTEM_CODE[$this->exception->getCode()][0]]:
                                    self::SYSTEM_CODE[$this->exception->getCode()][0].'['.$this->errorCode.']',
                            $this->errorReturnJsonCodeName =>$this->exception->getCode(),
                            'errorCode'     =>$this->errorCode,
                            'statusCode'    => 100,
                        ];
                    }

                }else if($key === 'USE_CODE')
                {
                    if(isset(\ErrorOrLog::USE_CODE[$this->exception->getCode()]))
                    {
                        $result =  [
                            $this->errorReturnJsonMsgName=>
                                is_int(\ErrorOrLog::USE_CODE[$this->exception->getCode()][0])?
                                    \ErrorOrLog::HINT_MSG[\ErrorOrLog::USE_CODE[$this->exception->getCode()][0]].'['.$this->errorCode.']':
                                    \ErrorOrLog::USE_CODE[$this->exception->getCode()][0].'['.$this->errorCode.']',
                            $this->errorReturnJsonCodeName=>$this->exception->getCode(),
                            'errorCode'         =>$this->errorCode,
                            'statusCode'        => 100,
                        ];
                    }
                }
            }
        }
        # 写入错误日志
        $this->createLog($result);
        return $result;
    }

    /**
     * 创建日志
     * @param        $result
     * @param string $Logger
     * @throws \Exception
     */
    private function createLog($result,string $Logger='')
    {
        $data = $this->app->Helper()->json_encode(['createDate'=>date('Y-m-d H:i:s'),'REQUEST_TIME_FLOAT'=>[$_SERVER['REQUEST_TIME_FLOAT']],'DATA'=>$result]).PHP_EOL;
        /**判断是否有db类**/
        if (class_exists('Db') && $this->app->__ERROR_LOG_SAVE__==='db'){
            /** 使用 数据库保存 **/
        }else{
            /** 使用文件保存 **/
            $dir = $this->app->DOCUMENT_ROOT.'runtime'.DIRECTORY_SEPARATOR.'errorlog'.DIRECTORY_SEPARATOR;
            $this->app->Helper()->file()->createDir($dir);
            file_put_contents($dir.date('y_m_d_H_i').'_error.txt',$data,FILE_APPEND | LOCK_EX);
        }
    }

}