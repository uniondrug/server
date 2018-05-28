<?php
/**
 * OnTaskTrait.php
 *
 */

namespace Uniondrug\Server\Servitization;

use swoole_server;
use Uniondrug\Packet\Json;
use Uniondrug\Server\Task\TaskHandler;
use Uniondrug\Server\Utils\Connections;

trait OnTaskTrait
{
    /**
     * @inheritdoc
     */
    public function doTask(swoole_server $server, $data, $taskId, $workerId)
    {
        console()->debug("[Task] doTask: fromWorkerId=%d, taskId=%d, data=%s", $workerId, $taskId, $data);

        // 测试数据库连接
        Connections::testConnections();

        try {
            $task = Json::decode($data, true);
            if ($task && isset($task['handler']) && is_a($task['handler'], TaskHandler::class, true)) {
                return app()->getShared($task['handler'])->handle($task['data']);
            } else {
                console()->error("[Task] doTask: fromWorkerId=%d, taskId=%d, Data is not a valid Task object", $workerId, $taskId);

                return false;
            }
        } catch (\Exception $e) {
            console()->error("[Task] doTask: fromWorkerId=%d, taskId=%d, error=%s",$workerId, $taskId, $e->getMessage());

            return false;
        }
    }

    /**
     * @inheritdoc
     */
    public function doFinish(swoole_server $server, $data, $taskId)
    {
        console()->debug("[Task] doFinish: taskId=%d, result=%s", $taskId, serialize($data));
    }

    /**
     * @inheritdoc
     *
     * 始终有ID=0的worker收到PipeMessage投递的任务，然后通过task()方法投递给taskworker执行
     */
    public function doPipeMessage(swoole_server $server, int $src_worker_id, $message)
    {
        $taskId = $server->task($message);
        if (false === true) {
            console()->error("[Task] doPipeMessage: data=%s, dispatch task failed", $message);
        } else {
            console()->debug("[Task] doPipeMessage: data=%s, dispatched, taskId=%d", $message, $taskId);
        }
    }
}
