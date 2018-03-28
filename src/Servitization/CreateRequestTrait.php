<?php
/**
 * CreateRequestTrait.php
 *
 */

namespace Uniondrug\Server\Servitization;

use Uniondrug\Http\ServerRequest;
use Uniondrug\Packet\Json;

trait CreateRequestTrait
{
    /**
     * @param       $requestData
     * @param array $connection
     *
     * @return \Uniondrug\Http\ServerRequest
     * @throws \Uniondrug\Packet\Exceptions\PacketException
     */
    public function createRequest($requestData, $connection = [])
    {
        if (empty($requestData)) {
            throw new \RuntimeException('Request data error: empty request.');
        }

        $request = Json::decode($requestData);

        if (!isset($request->method) || !isset($request->path)) {
            throw new \RuntimeException('Request data error: method or path not set.');
        }

        if (!in_array(strtoupper($request->method), ['GET', 'POST', 'PUT', 'DELETE', 'PATCH'])) {
            throw new \RuntimeException('Request data error: invalid method');
        }

        $parts = parse_url($request->path);
        if (!isset($parts['path']) || empty($parts['path'])) {
            throw new \RuntimeException('Request data error: invalid path.');
        }

        // _SERVER
        $host = 'localhost';
        $serverParams = [
            'REQUEST_METHOD'       => $request->method,
            'REQUEST_URI'          => $parts['path'],
            'PATH_INFO'            => $parts['path'],
            'REQUEST_TIME'         => time(),
            'REQUEST_TIME_FLOAT'   => microtime(1),
            'GATEWAY_INTERFACE'    => 'Swoole/' . SWOOLE_VERSION,

            // Server
            'SERVER_PROTOCOL'      => 'HTTP/1.1',
            'REQUEST_SCHEMA'       => 'http',
            'SERVER_NAME'          => $host,
            'SERVER_ADDR'          => isset($connection['server_addr']) ? $connection['server_addr'] : gethostbyname('localhost'),
            'SERVER_PORT'          => isset($connection['server_port']) ? $connection['server_port'] : 0,
            'REMOTE_ADDR'          => $connection['remote_ip'],
            'REMOTE_PORT'          => $connection['remote_port'],
            'QUERY_STRING'         => isset($parts['query']) ? $parts['query'] : '',

            // Headers
            'HTTP_HOST'            => $host,
            'HTTP_USER_AGENT'      => 'SocketClient',
            'HTTP_ACCEPT'          => '*/*',
            'HTTP_ACCEPT_LANGUAGE' => '',
            'HTTP_ACCEPT_ENCODING' => '',
            'HTTP_CONNECTION'      => '',
            'HTTP_CACHE_CONTROL'   => '',
        ];

        // Client FD
        if (isset($connection['server_fd'])) {
            $serverParams['SERVER_FD'] = $connection['server_fd'];
        }

        // Body
        $bodyContent = '';
        if (isset($request->body) && !empty($request->body)) {
            $bodyContent = Json::encode((array) $request->body);

            $serverParams['CONTENT_LENGTH'] = strlen($bodyContent);
            $serverParams['HTTP_CONTENT_LENGTH'] = strlen($bodyContent);
            $serverParams['CONTENT_TYPE'] = 'application/json';
            $serverParams['HTTP_CONTENT_TYPE'] = 'application/json';
        }

        // Headers
        $headers = [];
        foreach ($serverParams as $k => $v) {
            if (0 === strpos($k, 'HTTP_')) {
                $headers[str_replace('HTTP_', '', $k)] = $v;
            }
        }
        if (isset($request->headers) && !empty($request->headers)) {
            foreach ((array) $request->headers as $k => $v) {
                $headers[$k] = $v;
            }
        }

        // Create Request
        $serverRequest = new ServerRequest(
            $request->method,
            $request->path,
            $headers,
            null,
            $serverParams
        );
        unset($headers);

        if ($bodyContent) {
            $serverRequest->getBody()->write($bodyContent);
        }

        return $serverRequest;
    }
}
