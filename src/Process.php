<?php
/**
 * Process.php
 *
 */

namespace Uniondrug\Server;

use swoole_process;
use Uniondrug\Framework\Services\ServiceTrait;
use Uniondrug\Server\Utils\Connections;
use Uniondrug\Swoole\Process as SwooleProcess;

class Process extends SwooleProcess
{
    use ServiceTrait;

    /**
     * @var array
     */
    protected $options = [];

    /**
     * @param array $options
     *
     * @return $this
     */
    public function configure(array $options = [])
    {
        $this->options = $options;

        return $this;
    }

    /**
     * @param       $key
     * @param mixed $defaultValue
     *
     * @return mixed
     */
    public function getOption($key, $defaultValue = null)
    {
        if (isset($this->options[$key])) {
            return $this->options[$key];
        }

        return $defaultValue;
    }

    /**
     * @param swoole_process $worker
     *
     * @return void
     */
    public function runProcess(swoole_process $worker)
    {
        // use sigkill to close process, to keep mysql connection in parents process
        register_shutdown_function(function () {
            swoole_process::kill(getmypid(), SIGKILL);
        });

        // 断掉所有从父进程带来的连接
        Connections::dropConnections();

        // 注册定时器测试
        Connections::keepConnections();

        // 调用处理进程
        parent::runProcess($worker);
    }

    /**
     * 检查父进程状态，如果已经退出，或者不再活跃，子进程自己退出
     */
    public function checkParent()
    {
        if (function_exists('posix_getppid')) {
            $parentPid = posix_getppid();
            if ($parentPid == 1) {
                return false;
            } else {
                if (!swoole_process::kill($parentPid, 0)) {
                    return false;
                }
            }
        }

        return true;
    }

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
