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
use Uniondrug\Swoole\Server\UDP;

/**
 * Class UDPServer.
 */
class UDPServer extends UDP
{
    use OnWorkerStartTrait;

    use OnTaskTrait;

    use CreateRequestTrait;

    /**
     * @param swoole_server $server
     * @param               $data
     * @param               $clientInfo
     *
     * @return int|mixed
     *
     * @throws \Uniondrug\Packet\Exceptions\PacketException
     */
    public function doPacket(swoole_server $server, $data, $clientInfo)
    {
        try {
            $connectionInfo = [
                'remote_ip'   => $clientInfo['address'],
                'remote_port' => $clientInfo['port'],
            ];
            $request = $this->createRequest($data, -1, $connectionInfo);
            $response = app()->handleRequest($request);

            $server->sendto($clientInfo['address'], $clientInfo['port'], (string) $response->getBody());

            app()->shutdown($request, $response);
        } catch (\Exception $e) {
            console()->error("TCPServer Error: " . $e->getMessage());

            $res = call_user_func(app()->getConfig()->path('exception.response'), $e);
            $server->sendto($clientInfo['address'], $clientInfo['port'], Json::encode($res));
        }

        return 0;
    }
}
