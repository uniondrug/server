<?php
/**
 * Dispatcher.php
 *
 */

namespace Uniondrug\Server\Task;

use Uniondrug\Packet\Json;

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
     * @throws \Uniondrug\Packet\Exceptions\PacketException
     */
    public function dispatch($handler, $data = [])
    {
        if (!isset(swoole()->worker_pid) || swoole()->taskworker) {
            app()->getLogger("framework")->error("Dispatch task failed. Task must be dispatched from worker.");
            throw new \RuntimeException("Dispatch task failed. Task must be dispatched from worker.");
        }
        if (!is_a($handler, TaskHandler::class, true)) {
            app()->getLogger("framework")->error("Dispatch task failed. Handler: $handler is not a TaskHandler.");
            throw new \RuntimeException("Dispatch task failed. Handler: $handler is not a TaskHandler.");
        }
        $task = Json::encode([
            'handler' => $handler,
            'data'    => $data,
        ]);
        $taskId = swoole()->task($task);
        $workerId = swoole()->worker_id;
        if (false === $taskId) {
            app()->getLogger("framework")->error("[Worker $workerId] Dispatch task failed. Handler: $handler.");
        } else {
            app()->getLogger("framework")->debug("[Worker $workerId] task $taskId send, handle: $handler.");
        }
    }

    /**
     * 发送到ID=0的Worker，由Worker再deliver到Task进程。
     *
     * @param       $handler
     * @param array $data
     *
     * @return bool
     * @throws \Uniondrug\Packet\Exceptions\PacketException
     */
    public function dispatchByProcess($handler, $data = [])
    {
        $message = Json::encode([
            'handler' => $handler,
            'data'    => $data,
        ]);

        return swoole()->sendMessage($message, 0);
    }
}
