<?php

use FastD\Http\JsonResponse;
use FastD\Http\Response;
use FastD\Packet\Swoole;
use UniondrugServer\Application;
use UniondrugServer\Servitization\Client\Client;
use Psr\Http\Message\ServerRequestInterface;

/**
 * 服务应用
 *
 * @return Application
 */
function app()
{
    return Application::$app;
}

/**
 * 版本号
 *
 * @return string
 */
function version()
{
    return Application::VERSION;
}

/**
 * Phalcon容器
 *
 * @return \Pails\Container
 */
function container()
{
    return app()->get('container');
}

/**
 * Phalcon路由器
 *
 * @return \Phalcon\Mvc\Router
 */
function router()
{
    return container()->get('router');
}

/**
 * 配置文件读取
 *
 * @return \UniondrugServer\Wrapper\Config
 */
function config()
{
    return app()->get('config');
}

/**
 * 请求
 *
 * @return ServerRequestInterface
 */
function request()
{
    return app()->get('request');
}

/**
 * 响应
 *
 * @return Response
 */
function response()
{
    return app()->get('response');
}

/**
 * @return \Exception
 */
function exception()
{
    return app()->get('exception');
}

/**
 * @param array $content
 * @param int   $statusCode
 *
 * @return Response
 */
function binary(array $content, $statusCode = Response::HTTP_OK)
{
    return new Response(Swoole::encode($content), $statusCode);
}

/**
 * @param array $content
 * @param int   $statusCode
 *
 * @return Response
 */
function json(array $content = [], $statusCode = Response::HTTP_OK)
{
    return new JsonResponse($content, $statusCode);
}

/**
 * @param $statusCode
 * @param $message
 *
 * @throws Exception
 */
function abort($statusCode, $message = null)
{
    throw new Exception((is_null($message) ? Response::$statusTexts[$statusCode] : $message), $statusCode);
}

/**
 * @return \Phalcon\Logger\Adapter
 */
function logger($name = null)
{
    if ($name) {
        return container()->get('logger', [$name]);
    }

    return container()->get('logger');
}

/**
 * @param null $uri
 * @param bool $async
 * @param bool $keep
 *
 * @return Client
 */
function client($uri = null, $async = false, $keep = false)
{
    if (null !== $uri) {
        return new Client($uri, $async, $keep);
    }

    return app()->get('client');
}

/**
 * @return \FastD\Swoole\Server
 */
function server()
{
    return app()->get('server');
}

/**
 * @return swoole_server
 */
function swoole()
{
    return server()->getSwoole();
}
