<?php


namespace normphp\staging\controller;

/**
 * Class Constraint
 * @package normphp\staging\controller
 */
class Constraint extends \normphp\staging\Constraint
{
    /**
     * 枚举类型
     * @param array $constraint 数据
     * @param string $explain  说明
     * @param string $field
     * @param $data     数据
     * @param $restrain 参考
     * @param bool $exception 是否异常
     * @throws \Exception
     */
    public static function enum(array $constraint,string $explain,string $field, &$data,$restrain,bool $exception=true)
    {
        /***由于不确定是先转换数据类型 函数判断数据格式，为保证数据一致性，先转换成定义的数据类型再进行数据格式判断 **/
        if ($constraint['dataType'] !== ''){settype($data,$constraint['dataType']);}
        $restrainRes = explode(',',$restrain[1]);
        $msg = Helper()->str()->str_replace([$restrain[1],$data],$constraint['msg']['zh-cn']);
        $msg = $explain.' ['.$field.'] '.$constraint['msg']['zh-cn'];
        if (!in_array($data,$restrainRes)){
            if ($exception){error($msg);}else{return $msg;}
        }
    }
    /**
     * 货币格式
     * @param array $constraint 数据
     * @param string $explain  说明
     * @param string $field
     * @param $data     数据
     * @param $restrain 参考
     * @param bool $exception 是否异常
     * @throws \Exception
     */
    public static function moneyFormat(array $constraint,string $explain,string $field,string &$data,$restrain,bool $exception=true)
    {
        /***由于不确定是先转换数据类型 函数判断数据格式，为保证数据一致性，先转换成定义的数据类型再进行数据格式判断 **/
        if ($constraint['dataType'] !== ''){settype($data,$constraint['dataType']);}
    }
    /**
     * 验证是否是空
     * @param array $constraint 数据
     * @param string $explain  说明
     * @param string $field
     * @param $data     数据
     * @param $restrain 参考
     * @param bool $exception 是否异常
     * @throws \Exception
     */
    public static function required(array $constraint,string $explain,string $field,string &$data,$restrain,bool $exception=true)
    {
        /***由于不确定是先转换数据类型 函数判断数据格式，为保证数据一致性，先转换成定义的数据类型再进行数据格式判断 **/
        if ($constraint['dataType'] !== ''){settype($data,$constraint['dataType']);}
        $msg = $explain.'['.$field.'] '.$constraint['msg']['zh-cn'];
        if (empty($data) || $data ===[] || $data==='' || $data ==='  '){
            if ($exception){error($msg);}else{return $msg;}
        }
    }

    /**
     * 正则表达式 方式验证数据
     * @param array $constraint 数据
     * @param string $explain  说明
     * @param string $field
     * @param $data     数据
     * @param $restrain 参考
     * @param bool $exception 是否异常
     * @throws \Exception
     */
    public static function regexp($constraint,$explain,$field,&$data,$restrain,bool $exception=true)
    {
        /***由于不确定是先转换数据类型 函数判断数据格式，为保证数据一致性，先转换成定义的数据类型再进行数据格式判断 **/
        if ($constraint['dataType'] !== ''){settype($data,$constraint['dataType']);}
        preg_match($constraint['value'],$data,$result);
        if(empty($result) || $result ==null){
            $msg = $explain.'['.$field.'] '.$constraint['msg']['zh-cn'];
            if ($exception){error($msg);}else{return $msg;}
        }
        if ($constraint['dataType'] !== ''){settype($data,$constraint['dataType']);}
    }
}