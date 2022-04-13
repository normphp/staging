<?php
/**
 * @Title: {{Title}}
 * @Author: {{Author}}
 * @Date:  2020-12-12
 * @Last Modified by: {{ModifiedBy}}
 * @Last Modified time: {{ModifiedTime}}
 */

require(__DIR__.'/../vendor/autoload.php');
(new normphp\staging\App(documentRoot:  __DIR__, appPath:'app', pattern: 'ORIGINAL', appConfigPath:'', deployPath:'', renPattern:'CLI', argv:$argv))->start();