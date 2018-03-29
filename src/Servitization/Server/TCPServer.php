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
        if ('quit' === $data) {
            $server->close($fd);

            return 0;
        }

        try {
            $connectionInfo = $server->connection_info($fd);
            $request = $this->createRequest($data, $connectionInfo);
            $response = app()->handleRequest($request);
            if (null !== $response->getFileDescriptor()) {
                $fd = $response->getFileDescriptor();
            }
            if (false === $server->connection_info($fd)) {
                app()->getLogger('framework')->error("TCPServer Error: Client has gone away.");

                return -1;
            }
            $server->send($fd, (string) $response->getBody());
            app()->shutdown($request, $response);
        } catch (\Exception $e) {
            app()->getLogger('framework')->error("TCPServer Error: " . $e->getMessage());

            $res = call_user_func(app()->getConfig()->path('exception.response'), $e);
            $server->send($fd, Json::encode($res));
        }

        return 0;
    }
}
