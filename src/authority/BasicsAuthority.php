<?php
/**
 * 基础权限类
 */
declare(strict_types=1);

namespace normphp\staging\authority;


use normphp\basics\service\account\BasicsAccountService;
use normphp\model\redis\Redis;
use normphp\staging\App;
class BasicsAuthority
{
    /**
     * jwt模式
     * @var string
     */
    protected $pattern = '';
    /**
     * 当前权限控制信息（扩展）
     * @var array
     */
    protected $authExtend=[];
    /**
     * 解密的jwt Payload信息
     * @var array
     */
    protected $Payload = [];
    /**
     * @var App|null
     */
    protected $app = null;
    /**
     * 是否是公共权限
     * @var bool
     */
    protected $isPublic = false;
    /**
     * 路由定义的权限方法
     * @var string
     */
    protected $methods = '';
    /**
     * 用户数据
     * @var null
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
     * Authority constructor.
     * @param $pattern 模式  比如 jwt模式
     * @param App $app
     */
    public function __construct(App $app,$pattern)
    {
        $this->app = $app;
        # 定义模式
        $this->pattern = $pattern;
    }
    /**
     * 获取 property
     * @param $propertyName
     * @return |null
     */
    public function __get($propertyName)
    {
        if(isset($this->$propertyName)){
            return $this->$propertyName;
        }
        return null;
    }
    /**
     * 统一启动方法
     * @param $methods 方法 ...
     * @return array
     */
    public function start(...$param)
    {
        # 判断是否有对应的方法
        if (!isset($param[0])){throw new \Exception('methods  Must be the');}
        $methods = $param[0];
        $this->methods = $methods;
        # 判断是否是public
        if($param[0] === 'public'){
            return $this->isPublic = true;
        }
        return $this->$methods(...$param);
    }
    /**
     * @Author 皮泽培
     * @Created 2019/10/22 17:49
     * @return bool 是否登录
     * @title  路由标题
     * @explain 路由功能说明
     * @throws \Exception
     */
    public function is_login():bool
    {


    }
    /**
     * @Author 皮泽培
     * @Created 2019/10/22 17:49
     * @title  默认的public方法
     * @explain 路由功能说明
     * @throws \Exception
     */
    public function public()
    {

    }
    /**
     * @Author 皮泽培
     * @Created 2019/10/22 17:49
     * @return bool 是否登录
     * @title  获取当前用户会话信息
     * @explain 路由功能说明
     * @throws \Exception
     */
    public function getUserInfo():array
    {
        return $this->app->Authority->UserInfo;
    }

    /**
     * @Author 皮泽培
     * @Created 2019/11/19 16:34
     * @title  判断是否是超级管理员
     * @explain 非必要情况下不建议使用
     * @return bool
     * @throws \Exception
     */
    public function isSuperAdmin()
    {
        return false;
    }

}