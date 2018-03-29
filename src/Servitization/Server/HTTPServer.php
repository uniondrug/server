<?php
/**
 * @author    jan huang <bboyjanhuang@gmail.com>
 * @copyright 2016
 *
 * @see       https://www.github.com/janhuang
 * @see       https://fastdlabs.com
 */

namespace Uniondrug\Server\Servitization\Server;

use Psr\Http\Message\ServerRequestInterface;
use swoole_http_request;
use swoole_http_response;
use Uniondrug\Http\Response;
use Uniondrug\Http\SwooleServerRequest;
use Uniondrug\Server\Servitization\OnTaskTrait;
use Uniondrug\Server\Servitization\OnWorkerStartTrait;
use Uniondrug\Server\Utils\Request;
use Uniondrug\Swoole\Server\HTTP;

/**
 * Class HTTPServer.
 */
class HTTPServer extends HTTP
{
    use OnWorkerStartTrait;

    use OnTaskTrait;

    /**
     * @param swoole_http_request  $swooleRequet
     * @param swoole_http_response $swooleResponse
     *
     * @return int
     */
    public function onRequest(swoole_http_request $swooleRequet, swoole_http_response $swooleResponse)
    {
        // Request工具
        $requestUtil = new Request($swooleRequet);

        // Log Elements
        $timeStart = $requestUtil->getRequestTime();
        $remoteAddr = $requestUtil->getClientAddress() ?: '-';
        $remoteUser = $requestUtil->getBasicAuthUser() ?: '-';
        $httpHost = $requestUtil->getHttpHost() ?: '-';
        $userAgent = $requestUtil->getUserAgent() ?: '-';
        $userReferer = $requestUtil->getHTTPReferer() ?: '-';
        $userRequest = $requestUtil->getUserRequest();

        // Do Request
        $request = SwooleServerRequest::createServerRequestFromSwoole($swooleRequet);
        $response = $this->doRequest($request);

        // Log Elements
        $bodySent = strlen((string) $response->getBody());
        $statusCode = $response->getStatusCode();

        // Set Server header
        $response->withHeader('Server', app()->getConfig()->path('server.vendor', 'UDS'));
        foreach ($response->getHeaders() as $key => $header) {
            $swooleResponse->header($key, $response->getHeaderLine($key));
        }
        foreach ($response->getCookieParams() as $key => $cookieParam) {
            $swooleResponse->cookie($key, $cookieParam);
        }
        $swooleResponse->status($response->getStatusCode());
        $swooleResponse->end((string) $response->getBody());

        // Log Elements
        $timeDone = microtime(1);
        $timeUsed = $timeDone - $timeStart;

        // Access Log
        app()->getLogger('access')->info(sprintf("%s %s \"%s\" %s %s \"%s\" \"%s\" %s %s",
            $remoteAddr, $remoteUser, $userRequest, $statusCode, $bodySent, $userReferer, $userAgent, $httpHost, $timeUsed));

        // Cleanup
        unset($requestUtil);
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
}
