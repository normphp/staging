<?php
/**
 * Created by PhpStorm.
 * User: pizepei
 * Date: 2019/1/15
 * Time: 16:24
 * @title 权限控制基础类 
 */
declare(strict_types=1);

namespace normphp\staging\authority;

use normphp\container\Container;

class  Authority extends Container
{
    /**
     * 容器标识
     */
    const CONTAINER_NAME = 'Authority';
    /**
     * 容器绑定标识
     * @var array
     */
    protected $bind = [

    ];

}