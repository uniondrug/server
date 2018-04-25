<?php
/**
 * Connections.php
 *
 */

namespace Uniondrug\Server\Utils;

class Connections
{
    private static $serviceNames = ['db', 'dbSlave'];

    /**
     * 清除所有已经建立的连接实例
     */
    public static function dropConnections()
    {
        // Mysql
        foreach (static::$serviceNames as $serviceName) {
            if (app()->hasSharedInstance($serviceName)) {
                app()->getShared($serviceName)->close();
                app()->removeSharedInstance($serviceName);
            }
        }

        // Redis
        if (app()->hasSharedInstance('redis')) {
            app()->getShared('redis')->close();
            app()->removeSharedInstance('redis');
        }
    }

    /**
     * 心跳测试，断开了重新连接
     */
    public static function testConnections()
    {
        $pid = getmypid();
        foreach (static::$serviceNames as $serviceName) {
            if (app()->hasSharedInstance($serviceName)) {
                $tryTimes = 0;
                $maxRetry = app()->getConfig()->path('database.max_retry', 3);
                while ($tryTimes < $maxRetry) {
                    try {
                        app()->getShared($serviceName)->query("select 1");
                    } catch (\Exception $e) {
                        app()->getLogger('database')->alert("[$pid] [$serviceName] connection lost ({$e->getMessage()})");
                        if (preg_match("/(errno=32 Broken pipe)|(MySQL server has gone away)/i", $e->getMessage())) {
                            $tryTimes++;
                            app()->getShared($serviceName)->close();
                            app()->removeSharedInstance($serviceName);
                            app()->getLogger('database')->alert("[$pid] [$serviceName] try to reconnect[$tryTimes]");
                            continue;
                        } else {
                            app()->getLogger('database')->error("[$pid] [$serviceName] try to reconnect failed");
                            process_kill($pid);
                        }
                    }
                    break;
                }
            }
        }
    }

    /**
     * 注册一个定时器，定时测试。
     */
    public static function keepConnections()
    {
        // 挂起定时器，让数据库保持连接
        $interval = (int) app()->getConfig()->path('database.interval', 0);
        if ($interval) {
            swoole()->tick($interval * 1000, [static::class, 'testConnections']);
        }
    }
}
