<?php
/**
 * Dispatcher.php
 *
 */

namespace Uniondrug\Server\Task;

use Phalcon\Di\Injectable;
use Uniondrug\Server\Task;

/**
 * Class Dispatcher
 *
 */
class Dispatcher extends Injectable
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
            $this->getDI()->getLogger("framework")->error("[Worker $workerId] Dispatch task failed. Handler: $handler");
        } else {
            $this->getDI()->getLogger("framework")->debug("[Worker $workerId] task $taskId send, handle: $handler");
        }
    }
}
