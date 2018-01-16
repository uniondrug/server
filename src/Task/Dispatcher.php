<?php
/**
 * Dispatcher.php
 *
 */

namespace UniondrugServer\Task;

use UniondrugServer\Task;

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
     */
    public function dispatch($handler, $data)
    {
        $task = new Task($handler, $data);
        $taskId = swoole()->task($task);
        $workerId = swoole()->worker_id;
        if (false === $taskId) {
            logger("framework")->error("[Worker $workerId] Dispatch task failed. Handler: $handler");
        } else {
            logger("framework")->debug("[Worker $workerId] task $taskId send, handle: $handler");
        }
    }
}
