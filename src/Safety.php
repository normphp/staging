<?php
declare(strict_types=1);


namespace normphp\staging;

/**
 * 严格的安全控制类
 * Class Safety
 * @package normphp\staging
 */
class Safety
{
    /**
     * 敏感函数列表
     */
    const FUNCTION_LIST =[
        'eval',// 字符串当作代码来执行
        'assert',//调试函数，检查第一个断言是否为FALSE。（把传入的字符串作为php代码执行）
        'preg_replace',//（preg_replace(/“xxxx”/e)) 执行正则表达式，实现搜索和替换功能。/e修正符使preg_replace()将其中的replacement参数当作PHP代码
        'create_function',// 创建一个匿名函数，并返回都独一无二的函数名。
        'assert',//该函数会检查一个指定断言。断言是一个逻辑学词汇，主要用于程序员来进行假设判断。断言只有两种类型，字符串型或者布尔型，当断言为false时返回字符串表达式。如果断言是字符串那么会当做php代码执行。
        'call_user_func',//把第一个参数作为回调函数调用，后面的参数作为回调函数的参数。
        'call_user_func_array',//还有call_user_func_array()与之类似，只是传入参数为数组。
        'str_replace',//字符串替换
        'escapeshellcmd',//对字符串中可能会欺骗 shell 命令执行任意命令的字符进行转义。 此函数保证用户输入的数据在传送到 exec() 或 system() 函数，或者 执行操作符 之前进行转义。
    ];

    /**
     * 敏感函数-》命令执行
     */
    const EXEC_CLI_LIST = [
        'exec',//exec() 执行一个外部程序
        'passthru',//passthru() 执行外部程序并显示原始输出
        'shell_exec',//shell_exec()通过shell环境执行命令，并且将完整的输出以字符串的方式返回
        'system',//system() 执行外部程序，并且显示输出
        'popen',//popen() 通过popen()的参数传递一条命令，并且对popen()所打开的文件进行执行
        'eval',//
    ];
    const CONFIG_LIST = [
        'regist_globle',//regist_globle=on（未初始化的变量）,当on的时候，传递的值会被直接注册为全局变量直接使用。而off时，我们需要到特定的数组得到他，php>4.2.0 default 关闭。
        'ini_set',//数字
    ];
    /**
     * 文件相关的函数
     */
    const FILE_LSI = [
        'require','include','require_once','include_once',//文件包含漏洞（包含任意文件）
        'file_get_contents','file_put_contents','fopen','readfile','copy','rename','rmdir','highlight_file','file','move_upload_file','readfile','fwrite','fread','fclose','proc_open',
    ];
    /**
     *数据库相关的函数
     */
    const SQL_LIST = [
        'select','from','mysql_connect','mysql_query',//数据库操作（sql注入漏洞）sql命令
        'print','print_r','echo','sprintf','die','var_dump','var_export',//数据库显示（xss漏洞）函数
    ];
    /**
     * @var App|null
     */
    protected $app = null;
    /**
     * Response constructor.
     * @param App $app
     */
    public function __construct(App $app)
    {
        $this->app = $app;
    }
    public function __get($name)
    {
        // TODO: Implement __get() method.
        if (isset($this->$name)){return $this->$name;}
        return null;

    }
    /**
     * 快捷过滤
     * @param $value
     */
    public function default(&$data)
    {
        $array = [];
        if (is_array($data)){
            foreach ($data as $key =>&$value)
            {
                # 暂时保存
                $_value = $value;$_key = $key;
                # 删除
                unset($data[$key]);
                # 过滤key
                $_key = $this->defaultSafety($_key);
                # 重新写入
                $array[$_key] = $_value;
                # 判断是否是array
                if (is_array($_value)){
                    $this->default( $array[$_key]);
                }else{
                    $array[$_key] = $this->defaultSafety($_value);
                }
            }
            $data = $array;
        }else{
            $data = $this->defaultSafety($data);
        }
        return $this;
    }

    /**
     * 快捷过滤方法
     * @param $data
     * @return string|string[]|null
     */
    public function defaultSafety($data)
    {
        if (is_bool($data) || is_int($data) || is_float($data)){return $data;}
        $data = $this->filtrationFunction($data);
//        $data = $this->fliter_script($data);
//        $data = $this->fliter_html($data);
        return $data;
    }

    /**
     * 过滤函数
     * @param $value
     * @param array $behavior 模式变量 SQL_LIST|FILE_LSI|CONFIG_LIST ....
     * @return string|string[]|null
     */
    public function filtrationFunction($value,array $behavior=[])
    {
        if ( $behavior==[] || $behavior==='all'){
            $behavior = array_merge(self::FUNCTION_LIST,self::CONFIG_LIST,self::EXEC_CLI_LIST,self::FILE_LSI,self::SQL_LIST);
        }
        $count = 1000;
        $value = preg_replace("/".implode('|',$behavior)."/i","[Invalid string]",$value);
        return $value;
    }


    /**
     * 安全过滤类-过滤javascript,css,iframes,object等不安全参数 过滤级别高
     *  Controller中使用方法：$this->controller->fliter_script($value)
     * @param  string $value 需要过滤的值
     * @return string
     */
    function fliter_script($value) {
        $value = preg_replace("/(javascript:)?on(click|load|key|mouse|error|abort|move|unload|change|dblclick|move|reset|resize|submit)/i","&111n\\2",$value);
        $value = preg_replace("/(.*?)<\/script>/si","",$value);
        $value = preg_replace("/(.*?)<\/iframe>/si","",$value);
        //$value = preg_replace ("//iesU", '', $value);
        return $value;
    }

    /**
     * 安全过滤类-过滤HTML标签
     *  Controller中使用方法：$this->controller->fliter_html($value)
     * @param  string $value 需要过滤的值
     * @return string
     */
    function fliter_html($value) {
        if (function_exists('htmlspecialchars')) return htmlspecialchars($value);
        return str_replace(array("&", '"', "'", "<", ">"), array("&", "\"", "|'|", "<<", ">>"), $value);
    }

    /**
     * 安全过滤类-对进入的数据加下划线 防止SQL注入
     *  Controller中使用方法：$this->controller->fliter_sql($value)
     * @param  string $value 需要过滤的值
     * @return string
     */
    function fliter_sql($value) {
        $sql = array("select", 'insert', "update", "delete", "\'", "\/\*",
            "\.\.\/", "\.\/", "union", "into", "load_file", "outfile");
        $sql_re = array("","","","","","","","","","","","");
        return str_replace($sql, $sql_re, $value);
    }

    /**
     * 安全过滤类-通用数据过滤
     *  Controller中使用方法：$this->controller->fliter_escape($value)
     * @param string $value 需要过滤的变量
     * @return string|array
     */
    function fliter_escape($value) {
        if (is_array($value)) {
            foreach ($value as $k => $v) {
                $value[$k] = self::fliter_str($v);
            }
        } else {
            $value = self::fliter_str($value);
        }
        return $value;
    }

    /**
     * 安全过滤类-字符串过滤 过滤特殊有危害字符
     *  Controller中使用方法：$this->controller->fliter_str($value)
     * @param  string $value 需要过滤的值
     * @return string
     */
    function fliter_str($value) {
        $badstr = array("\0", "%00", "\r", '&', ' ', '"', "'", "<", ">", "   ", "%3C", "%3E");
        $newstr = array('', '', '', '&', ' ', '"', "'", "<", ">", "   ", "<", ">");
        $value  = str_replace($badstr, $newstr, $value);
        $value  = preg_replace('/&((#(\d{3,5}|x[a-fA-F0-9]{4}));)/', '&\\1', $value);
        return $value;
    }

    /**
     * 私有路劲安全转化
     *  Controller中使用方法：$this->controller->filter_dir($fileName)
     * @param string $fileName
     * @return string
     */
    function filter_dir($fileName) {
        $tmpname = strtolower($fileName);
        $temp = array(':/',"\0", "..");
        if (str_replace($temp, '', $tmpname) !== $tmpname) {
            return false;
        }
        return $fileName;
    }

    /**
     * 过滤目录
     *  Controller中使用方法：$this->controller->filter_path($path)
     * @param string $path
     * @return array
     */
    public function filter_path($path) {
        $path = str_replace(array("'",'#','=','`','$','%','&',';'), '', $path);
        return rtrim(preg_replace('/(\/){2,}|(\\\){1,}/', '/', $path), '/');
    }

    /**
     * 过滤PHP标签
     *  Controller中使用方法：$this->controller->filter_phptag($string)
     * @param string $string
     * @return string
     */
    public function filter_phptag($string) {
        return str_replace(array(''), array('<?', '?>'), $string);
    }
    /**
     * 安全过滤类-返回函数
     *  Controller中使用方法：$this->controller->str_out($value)
     * @param  string $value 需要过滤的值
     * @return string
     */
    public function str_out($value) {
        $badstr = array("<", ">", "%3C", "%3E");
        $newstr = array("<", ">", "<", ">");
        $value  = str_replace($newstr, $badstr, $value);
        return stripslashes($value); //下划线
    }




}