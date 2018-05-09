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
     * @return int
     * @throws \Uniondrug\Packet\Exceptions\PacketException
     */
    public function dispatch($handler, $data = [])
    {
        if (!isset(swoole()->worker_pid) || swoole()->taskworker) {
            console()->error("[Task] dispatch: Dispatch task failed. Task must be dispatched from worker.");
            throw new \RuntimeException("Dispatch task failed. Task must be dispatched from worker.");
        }

        if (!is_a($handler, TaskHandler::class, true)) {
            console()->error("[Task] dispatch: Dispatch task failed. Handler: $handler is not a TaskHandler.");
            throw new \RuntimeException("Dispatch task failed. Handler: $handler is not a TaskHandler.");
        }

        $task = Json::encode([
            'handler' => $handler,
            'data'    => $data,
        ]);

        $taskId = swoole()->task($task);
        return $taskId;
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

        $res = swoole()->sendMessage($message, 0);
        return $res;
    }
}
