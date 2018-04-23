<?php
/**
 * @author    jan huang <bboyjanhuang@gmail.com>
 * @copyright 2016
 *
 * @see       https://www.github.com/janhuang
 * @see       https://fastdlabs.com
 */

namespace Uniondrug\Server\Servitization;

use swoole_server;
use Uniondrug\Server\Utils\Connections;

/**
 * Trait OnWorkerStart.
 */
trait OnWorkerStartTrait
{
    /**
     * @param swoole_server $server
     * @param int           $worker_id
     */
    public function onWorkerStart(swoole_server $server, $worker_id)
    {
        // Process Rename
        $workerType = $server->taskworker ? 'TaskWorker' : 'Worker';
        process_rename(app()->getName() . ' [' . $workerType . ' #' . $worker_id . ']');

        // Call parent
        parent::onWorkerStart($server, $worker_id);

        // 丢弃所有带过来的连接
        Connections::dropConnections();

        // 注册心跳定时器
        Connections::keepConnections();
    }
}
