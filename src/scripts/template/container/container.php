<?php
/**
 * @Date: 2021-02-16
 * @Last Modified by: {{ModifiedBy}}
 * @Last Modified time: {{ModifiedTime}}
 * @title APP 子容器定义（只是为了适配IDE）
 * @methodstatic static 类   方法   [可选不填写就可非static方法] File[返回数据类型 可以是类 或者其他的比如self当前来]  test(string $question) [函数详情]
 */
namespace container\app;

/**
 * Class {{class}}
 * @package container{{method}}
 */
class {{class}} {{extends}}
{
    /**
     * 容器名称
     */
    const CONTAINER_NAME = '{{CONTAINER_NAME}}';

    # key 为标识  value 为类信息（请包括完整的命名空间）
    const bind = [{{bind}}
    ];
}