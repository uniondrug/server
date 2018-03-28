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
use swoole_websocket_frame;
use Uniondrug\Http\ServerRequest;
use Uniondrug\Packet\Json;
use Uniondrug\Server\Servitization\OnTaskTrait;
use Uniondrug\Server\Servitization\OnWorkerStartTrait;
use Uniondrug\Swoole\Server\WebSocket;

/**
 * Class WebSocketServer.
 */
class WebSocketServer extends WebSocket
{
    use OnWorkerStartTrait;

    use OnTaskTrait;

    /**
     * @param swoole_server          $server
     * @param swoole_websocket_frame $frame
     *
     * @return int|mixed
     *
     * @throws \Uniondrug\Packet\Exceptions\PacketException
     */
    public function doMessage(swoole_server $server, swoole_websocket_frame $frame)
    {
        if ($request = $this->buildRequest($server, $frame)) {
            $response = app()->handleRequest($request);
            $fd = null !== ($fd = $response->getFileDescriptor()) ? $fd : $frame->fd;
            if (false === $server->connection_info($fd)) {
                return -1;
            }
            $server->push($fd, (string) $response->getBody());

            app()->shutdown($request, $response);
        }

        try {
            $connectionInfo = $server->connection_info($frame->fd);
            $request = $this->createRequest($frame->data, $connectionInfo);
            $response = app()->handleRequest($request);
            $fd = null !== ($fd = $response->getFileDescriptor()) ? $fd : $frame->fd;
            if (false === $server->connection_info($fd)) {
                return -1;
            }
            $server->push($fd, (string) $response->getBody());

            app()->shutdown($request, $response);
        } catch (\Exception $e) {
            app()->getLogger('framework')->error("TCPServer Error: " . $e->getMessage());

            $res = call_user_func(app()->getConfig()->path('exception.response'), $e);
            $server->push($frame->fd, Json::encode($res));
        }

        return 0;
    }
}
