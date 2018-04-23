<?php
/**
 * OnTaskTrait.php
 *
 */

namespace Uniondrug\Server\Servitization;

use swoole_server;
use Uniondrug\Packet\Json;
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
            $task = Json::decode($data, true);
            if ($task && isset($task['handler']) && is_a($task['handler'], TaskHandler::class, true)) {
                return app()->getShared($task['handler'])->handle($task['data']);
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

    /**
     * @inheritdoc
     *
     * 始终有ID=0的worker收到PipeMessage投递的任务，然后通过task()方法投递给taskworker执行
     */
    public function doPipeMessage(swoole_server $server, int $src_worker_id, $message)
    {
        $taskId = $server->task($message);
        $workerId = swoole()->worker_id;
        if (false === $taskId) {
            app()->getLogger("framework")->error("[Worker $workerId] Dispatch task failed. message=$message");
        } else {
            app()->getLogger("framework")->debug("[Worker $workerId] task $taskId send.");
        }
    }
}
