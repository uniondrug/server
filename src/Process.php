<?php
/**
 * Process.php
 *
 */

namespace Uniondrug\Server;

use swoole_process;
use Uniondrug\Framework\Services\ServiceTrait;
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
     * @inheritdoc
     */
    public function handle(swoole_process $swoole_process)
    {
        // use sigkill to close process, to keep mysql connection in parents process
        register_shutdown_function(function () {
            swoole_process::kill(getmypid(), SIGKILL);
        });

        $this->resetConnections();

        $this->keepHeartbeat();
    }

    /**
     * 丢弃已经创建的ConnectionInstance。让DI自动重新连接。
     */
    public function resetConnections()
    {
        // Fork一个子进程的时候，父子进程全部重置连接，包括mysql和redis
        foreach (['db', 'dbSlave'] as $serviceName) {
            if (app()->hasSharedInstance($serviceName)) {
                try {
                    app()->getShared($serviceName)->close();
                } catch (\Exception $e) {
                }
            }
            app()->removeSharedInstance($serviceName);
        }
    }

    /**
     * 测试数据库链接，并尝试重连。3次失败后退出进程。
     *
     * @param int   $id
     * @param array $params
     */
    public function testConnections($id = 0, $params = [])
    {
        foreach (['db', 'dbSlave'] as $serviceName) {
            if (app()->hasSharedInstance($serviceName)) {
                $tryTimes = 0;
                $maxRetry = app()->getConfig()->path('database.max_retry', 3);
                while ($tryTimes < $maxRetry) {
                    try {
                        @app()->getShared($serviceName)->query("select 1");
                    } catch (\Exception $e) {
                        app()->getLogger('database')->alert("[{$this->process->pid}] [$serviceName] connection lost ({$e->getMessage()})");
                        if (preg_match("/(errno=32 Broken pipe)|(MySQL server has gone away)/i", $e->getMessage())) {
                            $tryTimes++;
                            app()->removeSharedInstance($serviceName);
                            app()->getLogger('database')->alert("[{$this->process->pid}] [$serviceName] try to reconnect[$tryTimes]");
                            continue;
                        } else {
                            app()->getLogger('database')->error("[{$this->process->pid}] [$serviceName] try to reconnect failed");
                            process_kill($this->process->pid);
                        }
                    }
                    break;
                }
            }
        }
    }

    /**
     * 数据库进程内心跳
     */
    public function keepHeartbeat()
    {
        // 挂起定时器，让数据库保持连接
        $interval = app()->getConfig()->path('database.interval', 0);
        if ($interval) {
            swoole()->tick($interval * 1000, [$this, 'testConnections']);
        }
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
