<?php
/**
 * Created by PhpStorm.
 * User: pizepei
 * Date: 2019/2/14
 * Time: 16:09
 */

namespace normphp\staging\service;

/**
 * 文档
 * Class DocumentService
 * @package normphp\staging\service
 */
class DocumentService
{
    /**
     * 获取文档只的请求参数（文档格式）
     * @param $info
     * @return array
     */
    public  function getParamInit($info)
    {
        if(!empty($info)){
            $i = $i??0;
            foreach($info as $key=>$value){
                $infoData[$i]['field'] = $key;
                $infoData[$i]['explain'] = $value['explain'];//字段说明
                $infoData[$i]['type'] = $value['restrain'][0];//字段说明
                $infoData[$i]['restrain'] = implode(' | ',$value['restrain']);//约束
                if(isset($value['substratum'])){
                    $str = '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;';
                    $i++;
                    $this->recursiveParam($value['substratum'],$infoData,$i,$str);
                    $str = '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;';
                }
                $i++;
            }
        }

        return $infoData??[];
    }

    /**
     * @param $info
     * @param $infoData
     * @param $i
     * @param $str
     */
    public function recursiveParam($info,&$infoData,&$i,&$str)
    {
        $not = '&not;';
        if(!empty($info)){
            foreach($info as $key=>$value){
                $infoData[$i]['field'] = $str.$not.$key;
                $infoData[$i]['explain'] = $value['explain'];//字段说明
                $infoData[$i]['type'] = $value['restrain'][0];//字段说明
                $infoData[$i]['restrain'] = implode(' | ',$value['restrain']);//约束
                if(isset($value['substratum'])){
                    $rawStr = $str;
                    $str = $str.$str;
                    $i++;
                    $this->recursiveParam($value['substratum'],$infoData,$i,$str);
                    $str = $rawStr;
                }
                $i++;
            }
        }
    }



}