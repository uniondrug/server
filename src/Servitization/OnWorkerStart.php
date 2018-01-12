<?php
/**
 * @author    jan huang <bboyjanhuang@gmail.com>
 * @copyright 2016
 *
 * @see       https://www.github.com/janhuang
 * @see       https://fastdlabs.com
 */

namespace UniondrugServer\Servitization;

use swoole_server;

/**
 * Trait OnWorkerStart.
 */
trait OnWorkerStart
{
    /**
     * @param swoole_server $server
     * @param int           $worker_id
     */
    public function onWorkerStart(swoole_server $server, $worker_id)
    {
        parent::onWorkerStart($server, $worker_id);

        // 挂起定时器，让数据库保持连接
        $interval = config()->get('database.interval', 0);
        if ($interval) {
            $server->tick($interval * 1000, function ($id, $params = []) {
                $pid = getmypid();
                foreach (['db', 'dbSlave'] as $dbServiceName) {
                    if (container()->has($dbServiceName)) {
                        $tryTimes = 0;
                        $maxRetry = config()->get('database.max_retry', 3);
                        while ($tryTimes < $maxRetry) {
                            try {
                                @container()->getShared($dbServiceName)->query("select 1");
                            } catch (\Exception $e) {
                                logger('database')->alert("[$pid] [$dbServiceName] connection lost ({$e->getMessage()})");
                                if (preg_match("/(errno=32 Broken pipe)|(MySQL server has gone away)/i", $e->getMessage())) {
                                    $tryTimes++;
                                    @container()->getShared($dbServiceName)->close();
                                    @container()->getShared($dbServiceName)->connect();
                                    logger('database')->alert("[$pid] [$dbServiceName] try to reconnect[$tryTimes]");
                                    continue;
                                } else {
                                    logger('database')->error("[$pid] [$dbServiceName] try to reconnect failed");
                                    process_kill($pid);
                                }
                            }
                            break;
                        }
                    }
                }
            });
        }
    }
}
