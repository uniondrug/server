<?php
/**
 * OnTaskTrait.php
 *
 */

namespace Uniondrug\Server\Servitization;

use swoole_server;
use Uniondrug\Server\Task\TaskHandler;

trait OnTaskTrait
{
    /**
     * @inheritdoc
     */
    public function doTask(swoole_server $server, $data, $taskId, $workerId)
    {
        $TaskWorkerId = $server->worker_id;

        app()->getLogger("framework")->debug("[TaskWorker $TaskWorkerId] [FromWorkerId: $workerId, TaskId: $taskId] With data: " . $data);
        try {
            $task = json_decode($data);
            if ($task && isset($task->handler) && is_a($task->handler, TaskHandler::class, true)) {
                return app()->getShared($task->handler)->handle($task->data);
            } else {
                app()->getLogger("framework")->error("[TaskWorker $TaskWorkerId] [FromWorkerId: $workerId, TaskId: $taskId] Data is not a valid Task object");

                return false;
            }
        } catch (\Exception $e) {
            app()->getLogger("framework")->error("[TaskWorker $TaskWorkerId] [FromWorkerId: $workerId, TaskId: $taskId] Handle task failed. Error: " . $e->getMessage());

            return false;
        }
    }

    /**
     * @inheritdoc
     */
    public function doFinish(swoole_server $server, $data, $taskId)
    {
        $workerId = $server->worker_id;

        app()->getLogger("framework")->debug("[Worker $workerId] task $taskId finished, with data: " . serialize($data));
    }
}
