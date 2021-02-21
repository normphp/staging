<?php


namespace normphp\staging\controller;

use normphp\basics\model\account\AccountModel;
use normphp\helper\Helper;
use normphp\model\db\TableAlterLogModel;
use normphp\staging\Request;

/**
 * 本地初始化控制器（可配置是否开启）
 * Class BasicsInitialize
 * @package normphp\staging\controller
 */
class BasicsInitialize
{
    /**
     * 基础控制器信息
     */
    const CONTROLLER_INFO = [
        'User'=>'pizepei',
        'title'=>'本地初始化控制器',//控制器标题
        'baseAuth'=>'UserAuth:isLogin',//基础权限继承（加命名空间的类名称）可配置是否开启
        'namespace'=>'',//门面控制器命名空间
        'basePath'=>'local/initialize/',//基础路由
    ];
    /**
     * @param \normphp\staging\Request $Request
     *      get [object] 参数
     *           user [string required] 操作人
     * @return array [html]
     * @title  命令行cli模式初始化项目
     * @explainphp index_cli.php --route /deploy/initDeploy   --data user=pizepei   --domain oauth.heil.top
     * @baseAuth DeployAuth:public
     * @router get spidersWeb
     * @throws \Exception
     */
    public function spidersWeb(Request $Request)
    {
        $this->succeed($res['body']);
    }

    /**
     * @param \normphp\staging\Request $Request
     *      get [object] 参数
     *           user [string required] 操作人
     * @return array [json]
     * @title  命令行cli模式初始化项目
     * @explainphp index_cli.php --route /deploy/initDeploy   --data user=pizepei   --domain oauth.heil.top
     * @baseAuth DeployAuth:public
     * @router cli initDeploy
     * @throws \Exception
     */
    public function cliInitDeploy(Request $Request)
    {
        # 控制器初始化
        LocalDeployServic::cliInitDeploy($this->app,$Request->input());
    }

    /**
     * @param \normphp\staging\Request $Request
     *      path [object] 路径参数
     *           domain [string] 域名
     * @return array [json]
     * @title  同步所有model的结构
     * @explain 建议生产发布新版本时执行，注意：如果账号表不存在会创建初始化的超级管理员账号
     * @baseAuth DeployAuth:public
     * @router cli cliDbInitStructure
     * @throws \Exception
     */
    public function cliDbInitStructure(Request $Request)
    {
        ignore_user_abort();
        set_time_limit(500);
        # 命令行没事 saas
        $model = TableAlterLogModel::table();
        # 同步表结构
        $res = $model->initStructure('',true,'namespaceModelPath.json',['centre'=>\Deploy::PROJECT_ID === \Deploy::CENTRE_ID?true:false,'deploy'=>\Deploy::DEPLOY]);
        # 判断是否有账号信息 没有创建超级管理员
        $accountData = AccountModel::table()->fetch();
        return $res;
    }

    /**
     * @Author pizepei
     * @Created 2019/6/12 22:39
     * @param \normphp\staging\Request $Request
     * @title  删除本地配置接口
     * @explain 当接口被触发时会删除本地所有Config配置，配置会在项目下次被请求时自动请求接口生成
     * @router delete Config
     */
    public function deleteConfig(Request $Request)
    {

    }
    /**
     * @Author pizepei
     * @Created 2019/6/12 22:43
     * @param \normphp\staging\Request $Request
     *      raw [object] 路径
     *          path [string] 需要删除的runtime目录下的目录为空时删除runtime目录
     * @title  删除本地runtime目录下的目录
     * @explain 删除runtime目录下的目录或者runtime目录本身。配置会在项目下次被请求时自动请求接口生成runtime
     *
     * @return array [json]
     * @throws \Exception
     * @router delete runtime
     */
    public function deleteCache(Request $Request)
    {
        $path = $Request->raw('path');
        /**
         * 判断是否有方法的目录
         * 如 ../   ./
         */
        if(strpos($path,'..'.DIRECTORY_SEPARATOR) === 0 || strpos($path,'..'.DIRECTORY_SEPARATOR) > 0 ){
            return $this->error('非法目录');
        }
        if(strpos($path,'.'.DIRECTORY_SEPARATOR) === 0 || strpos($path,'.'.DIRECTORY_SEPARATOR) > 0 ){
            return $this->error('非法目录');
        }
        if($path ==='runtime')
        {
            $path = '..'.DIRECTORY_SEPARATOR.'runtime'.DIRECTORY_SEPARATOR;
        }else{
            $path = '..'.DIRECTORY_SEPARATOR.'runtime'.DIRECTORY_SEPARATOR.$path.DIRECTORY_SEPARATOR;
        }
        Helper::file()->deldir($path);
    }
    
}