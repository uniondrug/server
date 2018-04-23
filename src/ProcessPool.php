<?php
/**
 * ProcessPool.php
 *
 */

namespace Uniondrug\Server;

use Uniondrug\Framework\Services\ServiceTrait;
use Uniondrug\Swoole\ProcessPool as SwooleProcessPool;

/**
 * Class WorkerProcess
 *
 * @package Uniondrug\Crontab\Processes
 */
class ProcessPool extends SwooleProcessPool
{
    use ServiceTrait;

    /**
     * 通过魔术方法调用服务
     *
     * @param $name
     *
     * @return mixed
     */
    public function __get($name)
    {
        if (app()->has($name)) {
            $service = app()->getShared($name);
            $this->$name = $service;

            return $service;
        }

        if ($name == 'di') {
            return app();
        }

        throw new \RuntimeException('Access to undefined property ' . $name);
    }
}
