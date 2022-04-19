<?php
/**
 * Created by PhpStorm.
 * User: pizepei
 * Date: 2018/8/3
 * Time: 9:37
 * @title 请求类
 */
declare(strict_types=1);

namespace normphp\staging;


class Request
{
    /**
     * 数据类型转换（在获取参数是进行过滤数据类型转换）
     *  根据路由数据
     *      处理返回参数
     *          区分php://input  post  get
     *      请求来的参数
     * 根据要求返回不同的http请求
     * 根据要求返回不同的数据类型
     */

    /**
     * 当前对象
     * @var null
     */
    private static $object = null;
    /**
     * 请求id  （uuid）
     * @var null
     */
    protected  $RequestId = null;
    /**
     * 应用容器
     * @var App|null
     */
    protected $app = null;

    /**
     * Request constructor.
     * @param App $app
     * @throws \Exception
     */
    public function __construct(App $app)
    {
        $this->app = $app;
        $this->app->Safety()->default($_GET);
        $this->GET = $_GET;
        if ($this->app->Helper()->is_empty($_SERVER['PATH_INFO'])){
            unset($this->GET['s']);
        }
        $this->app->Safety()->default($_POST);
//        $this->COOKIE = $_COOKIE;
        $this->POST = $_POST;
        $this->PATH = [];
        $this->RAW = [];
        $this->SERVER = $_SERVER;
        /**
         * 生成请求id
         */
        $this->RequestId = $this->app->Helper()->getUuid(true,45,$app->__UUID_IDENTIFIER__);
        /**
         * 释放内存
         */
        /**
         * 判断模式exploit调试模式不释放$_POST、$_GET内存
         */
        if($app->__EXPLOIT__){
            $_POST = null;
            $_GET = null;
            //$_COOKIE = null;
            //$this->FILES = $_FILES;
        }
    }


    /**
     * 路由对象
     * @var null
     */
    protected $Route = null;

    /**
     * 初始化路由对象
     */
    protected function initRoute(){
        if($this->Route == null){
            $this->Route = $this->app->Route();
        }
    }

    /**
     * @param $vname
     * @return
     */
    public function __get($vname){
        return $this->$vname;
    }
    /**
     * 获取 PATH 变量
     * @param $name
     * @return mixed|null
     */
    public function path($name ='')
    {
        if($name ===''){
            return $this->PATH;
        }
        return $this->PATH[$name]??null;
    }
    protected $inputType = ['post','get','raw','xml'];
    protected $status = [
        'GET'=>false,
        'POST'=>false,
        'PATH'=>false,
        'RAW'=>false,
        'XML'=>false,
    ];

    /**
     * 获取post
     * @param string $name
     * @return null
     * @throws \Exception
     */
    public function post($name='')
    {
        return $this->input($name,'post');
    }

    /**
     * 获取raw
     * @param string $name
     * @return null
     * @throws \Exception
     */
    public function raw($name='')
    {
        return $this->input($name,'raw');
    }
    /**
     * 获取非路径参数外的参数数据
     * @param string $name  ['get','key']  或者字符串key
     * @param string $type  获取的请求数据类型
     * @return null
     * @throws \Exception
     */
    public function input($name = '',$type='get')
    {
        /**
         * 判断参数
         */
        if(is_array($name) && count($name)==2){
            $type = $name[0];
            $name = $name[1];
        }
        if(!in_array($type,$this->inputType)){
            error('错误的类型：'.$type);
        }
        $TypeS = strtoupper($type);
        /**
         * 判断是否已经进行了数据处理
         */
        if(!$this->status[$TypeS]){
            /**
             * 没有进行处理
             */
            if($TypeS == 'RAW'){
                $this->getRaw();
            }
            if($TypeS == 'XML'){
                $this->XML = $this->xmlToArray(file_get_contents("php://input",true));
            }
            if(isset($this->app->Route()->atRouteData['Param']['raw']['restrain'][0])){
                if($this->app->Route()->atRouteData['Param']['raw']['restrain'][0] !== 'raw'){
                    $this->paramFiltration($this->$TypeS,$type);
                }
            }else{
                $this->paramFiltration($this->$TypeS,$type);
            }

            /**
             * 处理完成修改状态
             */
            $this->status[$TypeS] = true;
        }

        /**
         * 判断是否是获取全部
         */
        if($name == ''){
            return $this->$TypeS;
        }

        return $this->$TypeS[$name]??null;
    }
    /**
     * 获取php://input数据
     */
    protected function getRaw()
    {
        /**
         * 判断是否定义数据类型
         */
        if(isset($this->app->Route()->atRouteData['Param']['raw']['restrain'][0]) && $_SERVER['HTTP_CONTENT_TYPE'] !== 'application/xml' && $_SERVER['HTTP_CONTENT_TYPE'] !== 'text/xml'){

            if($this->app->Route()->atRouteData['Param']['raw']['restrain'][0] == 'xml'){
                $this->RAW = $this->xmlToArray(file_get_contents("php://input",true));

            }else if($this->app->Route()->atRouteData['Param']['raw']['restrain'][0] == 'json'){
                $this->RAW = json_decode(file_get_contents("php://input",true),true);
            }else if($this->app->Route()->atRouteData['Param']['raw']['restrain'][0] == 'urlencoded'){
                /**
                 * application/x-www-form-urlencoded方式是Jquery的Ajax请求默认方式
                 * 在请求发送过程中会对数据进行序列化处理，以键值对形式？key1=value1&key2=value2的方式发送到服务器
                 */
                parse_str(file_get_contents("php://input"),$this->RAW);
            }else{
                $this->RAW = json_decode(file_get_contents("php://input",true),true);
            }
        }
        if (empty($this->RAW) && isset($_SERVER['HTTP_CONTENT_TYPE']) ){
            # json
            if($_SERVER['HTTP_CONTENT_TYPE'] == 'application/json'){
                $this->RAW = json_decode(file_get_contents("php://input",true),true);
            }
            # xml
            if($_SERVER['HTTP_CONTENT_TYPE'] == 'application/xml' || $_SERVER['HTTP_CONTENT_TYPE'] == 'text/xml'){
                $this->RAW = $this->xmlToArray(file_get_contents("php://input",true));
            }
            /**
             * application/x-www-form-urlencoded方式是Jquery的Ajax请求默认方式
             * 在请求发送过程中会对数据进行序列化处理，以键值对形式？key1=value1&key2=value2的方式发送到服务器
             */
            if($_SERVER['HTTP_CONTENT_TYPE'] == 'text/plain'|| $_SERVER['HTTP_CONTENT_TYPE'] == 'application/x-www-form-urlencoded' || $_SERVER['HTTP_CONTENT_TYPE'] == 'application/x-www-form-urlencoded; charset=UTF-8'){
                $this->RAW = json_decode(file_get_contents("php://input",true),true);
                if(!$this->RAW){
                    parse_str(file_get_contents("php://input"),$this->RAW);
                }
            }
        }

    }
    /**
     * 请求参数过滤
     */
    protected function paramFiltration(&$data,$type)
    {
        if ($type =='xml'){$type = 'raw';}
        if(!isset($this->app->Route()->atRouteData['Param'])){
            $data = null;
            return false;
        }
        if(!isset($this->app->Route()->atRouteData['Param'][$type])){
            $data = null;
            return null;
        }
        $Param = $this->app->Route()->atRouteData['Param'][$type];
        /**
         * 获取数据格式
         */
        $format = $Param['restrain'][0];
        if ($format === 'xml' ){$format = 'object';}
        $noteData = &$Param['substratum'];
        $this->dataFiltrationRecursive($data,$noteData,$format);

    }

    /**
     * 处理数据
     * @param $data 数据
     * @param $noteData 路由信息
     * @param $type  开始类型  object   objectList  list raw  影响到数据的循环$data
     */
    protected function dataFiltrationRecursive(&$data,$noteData,$type)
    {
        /***对请求参数进行过滤（删除不在注解中的参数key）*/
        if($this->app->__INIT__['requestParam']){$this->unsetParam($data,$noteData,$type);}
        if (empty($noteData)){$data = []; return [];}
        foreach ($noteData as $field=>$info){
            /**$field字段   $info字段信息 **/
            if ($type ==='object'){
                /** 循环处理限制 **/
                $this->restrainField($field,$data,$info);
            }elseif ($type ==='objectList'){
                /** 数组 处理需要先从数据循环开始 ***/
                /** 先判断是否是array 不是进行处理***/
                if(!is_array($data)){
                    if (empty($data)){$data='';}
                    $data = Helper()->json_decode($data,true);
                    if ($data===null){$data = [];}
                }
                /**开始循环处理数据**/
                foreach ($data as &$dataValue){
                    /** 处理子数据 **/
                    if (array_key_exists($field,$dataValue)){
                        /***数据存在***/
                        $this->restrainField($field,$dataValue,$info);
                    }
                }
            }elseif ($type ==='raw'){
            }
        }
    }
    /***
     * 处理数据类型的子方法
     * @param $field 当前字段
     * @param $data  字段数据
     * @param $info  字段路由限制信息
     */
    protected function restrainField($field,&$data,$info)
    {
        /** 循环处理限制 **/
        foreach ($info['restrain'] as $restrainVlue){
            if (in_array($restrainVlue,$this->app->Route()::RETURN_FORMAT) && $restrainVlue !=='raw'){
                /**结点 层**/
                $this->dataFiltrationRecursive($data[$field],$info['substratum'],$restrainVlue);
            }else{
                /**数据 层**/
                $this->restrain($field,$data[$field],$restrainVlue,$info['explain']);
            }
        }
    }
    /***
     * @param $field 字段key
     * @param $data 需要处理的数据
     * @param $dataType 需要处理的目标数据类型（包括自定义的数据类型）
     * @param $explain  字段标题说明
     */
    protected function restrain(string $field,&$data,$dataType,$explain){
        /***部分自定义类型带有参数，带参数的为array数据类型**/
        $restrainKey = is_array($dataType)?$dataType[0]:$dataType;
        /**常规数据类型 'int','string','bool','float','array','null']**/
        if (in_array($restrainKey,$this->app->Route()::REQUEST_PATH_PARAM_DATA_TYPE)){
            if (is_array($data)){$data = json_encode($data);}
            settype($data,$restrainKey);
        }else if(isset(\BaseConstraint::DATA[$restrainKey])){
            /***自定义 数据格式类型 处理**/
            $method = \BaseConstraint::DATA[$restrainKey]['type'] === 'class'?$restrainKey:'regexp';
            /**使用自定义类方法 处理数据**/
            (\BaseConstraint::DATA[$restrainKey]['namespace'])::$method(
                \BaseConstraint::DATA[$restrainKey],
                $explain,
                $field,
                $data,
                $dataType
            );
        }
    }

    /**
     * @param        $data
     * @param string $type
     * @return null
     * @throws \Exception
     * @Author 皮泽培
     * @Created 2019/6/12 17:42
     * @title  过滤return参数
     * @explain 过滤控制器return参数
     */
    public function returnParamFiltration(&$data,$type='')
    {
        if (!isset($this->app->Route()->Return['data'])){return null;}
        if ($type ==''  && $this->app->Route()->Return['data']['restrain'][0] =='raw') {return $data;}
        //开始
        $this->dataFiltrationRecursive($data,$this->app->Route()->Return['data']['substratum'],$type==''?$this->app->Route()->Return['data']['restrain'][0]:$type);
        return $data;
    }

    /**
     * @Author: pizepei
     * @Created: 2018/10/12 23:08
     * @param        $data
     * @param        $noteData
     * @param string $type
     * @throws \Exception
     * @title  对请求参数进行过滤（删除不在注解中的参数key） 测是否有参数约束
     */
    protected  function unsetParam(&$data,$noteData,$type='object')
    {
        /**
         * 对请求参数进行过滤（删除不在注解中的参数key）
         */
        if(isset($data) && is_array($data)){
            foreach($data as $pk=>&$pv){
                if($type == 'object'){
                    //if(!isset($noteData[$pk])){
                    //    if(!array_key_exists($pk,$noteData)){ unset($data[$pk]);}
                    //}
                    if(isset($noteData[$pk])){
                        if($noteData[$pk]['restrain'][0] != 'raw'){
                            if(!array_key_exists($pk,$noteData)){
                                unset($data[$pk]);
                            }else{
                                if (!isset($noteData[$pk]['substratum']) && ($noteData[$pk]['restrain'][0] =='object' || $noteData[$pk]['restrain'][0] =='objectList'))
                                {error('参数: '.$pk.' ['.$noteData[$pk]['restrain'][0].']不能没有下级或可使用[raw]忽略参数限制');}
                            }
                        }
                    }else{unset($data[$pk]);/***删除不在注解中的参数key **/}
                }else if($type == 'objectList'){
                    if(!is_array($pv)){
                        $pv = json_decode($pv);
                        if (!is_array($pv)){error('非法的数据结构:'.$pk.'的上级应该是['.$type.']索数组格式,通常为请求数据格式错误孩子控制器返回数据格式错误');}
                    }
                    if (!is_int($pk)){unset($data[$pk]);}//删除分索引数组的非法数据
                    foreach($pv as $kk =>&$vv){
                        if(is_array($vv)){
                            $type = 'objectList';
                            if (!isset($noteData[$kk])){unset($pv[$kk]);continue;}
                            if($noteData[$kk]['restrain'][0] == 'object'){
                                $type = 'object';
                                $this->unsetParam($vv,$noteData[$kk]['substratum'],$type);
                            }else if($noteData[$kk]['restrain'][0] != 'raw'){
                                $this->unsetParam($vv,$noteData[$kk]['substratum'],$type);
                            }
                        }else{
                            if(!array_key_exists($kk,$noteData)){ unset($data[$pk][$kk]);}
                        }
                    }
                }
            }
        }
    }

    /**
     * 重定向请求
     * @param $url
     */
    public function Redirect($url)
    {
        header("Location: {$url}",true,301);
    }
    /**
     * 将xml转为array
     * @param  string 	$xml xml字符串或者xml文件名
     * @param  bool 	$isfile 传入的是否是xml文件名
     * @return array    转换得到的数组
     */
     public function xmlToArray($xml,$isfile=false){
         //禁止引用外部xml实体
        libxml_disable_entity_loader(true);
        if($isfile){
            if(!file_exists($xml)) return false;
            $xmlstr = file_get_contents($xml);
        }else{
            $xmlstr = $xml;
        }
        $result= json_decode(json_encode(simplexml_load_string($xmlstr, 'SimpleXMLElement', LIBXML_NOCDATA)), true);
        return $result;
    }

    /**
     * 数组转xml字符
     * @param        $data
     * @param string $name
     * @return bool|string
     * @throws \Exception
     */
    function arrayToXml($data,$name='xml'){
        if(!is_array($data) || count($data) <= 0){
            return false;
        }
        static $i =1;
        $i++;
        if($name == 'xml'){
            $xml = '<?xml version="1.0" encoding="UTF-8" ?><'.$name.'>';
        }else{
            $xml = '<'.$name.'>';
        }
        foreach ($data as $key=>$val){
            if (is_numeric($val)){
                $xml.="<".$key.">".$val."</".$key.">";
            }else{
                if(is_array($val)){
                    foreach($val as $k=>$v){
                        # 安全策略
                        if($i > $this->app->__INIT__['requestParamTier']){
                            error('请求数据超过限制:xml层级超过限制');
                        }
                        # 策略数据
                        if (is_numeric($k) && is_array($v)){
                            $xml .= static::arrayToXmls($v,$key);
                        }else if (is_array($v)){
                            $xml .= static::arrayToXmls($val,$key);
                        }else if (is_numeric($v)){
                        }else{
                        }
                    }
                }else{
                    $xml.="<".$key.">".$val."</".$key.">";
//                    $xml.="<".$key."><![CDATA[".$val."]]></".$key.">";
                }
            }
        }
         $xml.= "</".$name.">";
        return $xml;
    }

    /**
     * 设置产生url
     * @param $route    路由地址
     * @param $data     需要传递的参数
     * @return string
     */
    public function setUrl($route,$data =[])
    {
        # 判断是否有参数
        $para = '';
        if(!empty($data)){
            # 拼接
            foreach ($data as $k=>$v){
                $para .= $k.'='.$v.'&';
            }
            $para = rtrim($para, "&");
            $route = $route.'?'.$para;
        }
        if(isset($_SERVER['HTTPS'])){
            $http = $_SERVER['HTTPS'] == 'on'?'https://':'http://';
        }else{
            $http = 'http://';
        }
        return $http.$_SERVER['HTTP_HOST'].$route;
    }

}