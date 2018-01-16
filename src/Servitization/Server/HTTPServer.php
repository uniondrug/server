<?php
/**
 * @author    jan huang <bboyjanhuang@gmail.com>
 * @copyright 2016
 *
 * @see       https://www.github.com/janhuang
 * @see       https://fastdlabs.com
 */

namespace UniondrugServer\Servitization\Server;

use FastD\Http\Response;
use FastD\Http\SwooleServerRequest;
use FastD\Swoole\Server\HTTP;
use Psr\Http\Message\ServerRequestInterface;
use swoole_http_request;
use swoole_http_response;
use swoole_server;
use UniondrugServer\Servitization\OnWorkerStart;
use UniondrugServer\Task;

/**
 * Class HTTPServer.
 */
class HTTPServer extends HTTP
{
    use OnWorkerStart;

    /**
     * @param swoole_http_request  $swooleRequet
     * @param swoole_http_response $swooleResponse
     *
     * @return int
     */
    public function onRequest(swoole_http_request $swooleRequet, swoole_http_response $swooleResponse)
    {
        $request = SwooleServerRequest::createServerRequestFromSwoole($swooleRequet);

        $response = $this->doRequest($request);

        // Set Server header
        $response->withHeader('Server', config()->get('server.vendor', 'UDS'));

        foreach ($response->getHeaders() as $key => $header) {
            $swooleResponse->header($key, $response->getHeaderLine($key));
        }
        foreach ($response->getCookieParams() as $key => $cookieParam) {
            $swooleResponse->cookie($key, $cookieParam);
        }

        $swooleResponse->status($response->getStatusCode());
        $swooleResponse->end((string) $response->getBody());
        app()->shutdown($request, $response);

        return 0;
    }

    /**
     * @param ServerRequestInterface $serverRequest
     *
     * @return Response
     */
    public function doRequest(ServerRequestInterface $serverRequest)
    {
        return app()->handleRequest($serverRequest);
    }

    /**
     * @inheritdoc
     */
    public function doTask(swoole_server $server, $task, $taskId, $workerId)
    {
        $TaskWorkerId = $server->worker_id;

        logger("framework")->debug("[TaskWorker $TaskWorkerId] [FromWorkerId: $workerId, TaskId: $taskId] With data: " . serialize($task));

        if (is_object($task) && $task instanceof Task) {
            try {
                return $task->handle();
            } catch (\Exception $e) {
                logger("framework")->error("[TaskWorker $TaskWorkerId] [FromWorkerId: $workerId, TaskId: $taskId] Handle task failed. Error: " . $e->getMessage());

                return false;
            }
        } else {
            logger("framework")->error("[TaskWorker $TaskWorkerId] [FromWorkerId: $workerId, TaskId: $taskId] Data is not a valid  Task object");

            return false;
        }
    }

    /**
     * @inheritdoc
     */
    public function doFinish(swoole_server $server, $data, $taskId)
    {
        $workerId = $server->worker_id;

        logger("framework")->debug("[Worker $workerId] task $taskId finished, with data: " . serialize($data));
    }
}
