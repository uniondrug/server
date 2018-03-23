<?php
/**
 * Dispatcher.php
 *
 */

namespace Uniondrug\Server\Task;

use FastD\Packet\Json;

/**
 * Class Dispatcher
 *
 */
class Dispatcher
{
    /**
     * Send a task.
     *
     * @param string $handler Handler class name
     * @param mixed  $data    Raw data
     *
     * @throws \FastD\Packet\Exceptions\PacketException
     */
    public function dispatch($handler, $data = [])
    {
        if (!is_a($handler, TaskHandler::class, true)) {
            app()->getLogger("framework")->error("Dispatch task failed. Handler: $handler is not a TaskHandler");
            throw new \RuntimeException("Dispatch task failed. Handler: $handler is not a TaskHandler");
        }
        $task = Json::encode(['handler' => $handler, 'data' => $data]);
        $taskId = swoole()->task($task);
        $workerId = swoole()->worker_id;
        if (false === $taskId) {
            app()->getLogger("framework")->error("[Worker $workerId] Dispatch task failed. Handler: $handler");
        } else {
            app()->getLogger("framework")->debug("[Worker $workerId] task $taskId send, handle: $handler");
        }
    }
}
