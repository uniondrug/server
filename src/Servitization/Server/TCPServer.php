<?php
/**
 * @author    jan huang <bboyjanhuang@gmail.com>
 * @copyright 2016
 *
 * @see       https://www.github.com/janhuang
 * @see       https://fastdlabs.com
 */

namespace Uniondrug\Server\Servitization\Server;

use swoole_server;
use Uniondrug\Packet\Json;
use Uniondrug\Server\Servitization\CreateRequestTrait;
use Uniondrug\Server\Servitization\OnTaskTrait;
use Uniondrug\Server\Servitization\OnWorkerStartTrait;
use Uniondrug\Swoole\Server\TCP;

/**
 * Class TCPServer.
 */
class TCPServer extends TCP
{
    use OnWorkerStartTrait;

    use OnTaskTrait;

    use CreateRequestTrait;

    /**
     * @param swoole_server $server
     * @param               $fd
     * @param               $data
     * @param               $from_id
     *
     * @return int|mixed
     *
     * @throws \Uniondrug\Packet\Exceptions\PacketException
     */
    public function doWork(swoole_server $server, $fd, $data, $from_id)
    {
        $data = trim($data);

        if ('ping' === $data) {
            $server->send($fd, 'pong');
            return 0;
        }

        if ('quit' === $data) {
            $server->close($fd);

            return 0;
        }

        $request = null;
        try {
            // 1.Build request object and call app to handle it.
            $connectionInfo = $server->connection_info($fd);
            $request = $this->createRequest($data, $fd, $connectionInfo);
            $response = app()->handleRequest($request);

            // 2.Build response data with headers and body
            foreach ($response->getHeaders() as $key => $header) {
                $responseData['headers'][$key] = $response->getHeaderLine($key);
            }
            $responseData['body'] = (string) $response->getBody();

            // 3.Retrieve socket fd
            if (null !== $response->getFileDescriptor()) {
                $fd = $response->getFileDescriptor();
            }
            if (false === $server->connection_info($fd)) {
                console()->error("[TCPServer] Error: Client has gone away.");

                return -1;
            }

            // 4.Send data to client
            $server->send($fd, Json::encode($responseData));

            // 5.Cleanup session data
            app()->shutdown($request, $response);

        } catch (\Exception $e) {
            // 1. Clean up global vars.
            if ($request !== null) {
                app()->shutdown($request, null);
            }
            console()->error("[TCPServer] Error: " . $e->getMessage());

            // 2. Build error messages
            $res = call_user_func(app()->getConfig()->path('exception.response'), $e);
            $responseData = [
                'headers' => [],
                'body' => Json::encode($res),
            ];

            // 3. Send to client
            $server->send($fd, Json::encode($responseData));
        }

        return 0;
    }
}
