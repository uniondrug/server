<?php

use FastD\Http\JsonResponse;
use FastD\Http\Response;
use UniondrugServer\Application;

/**
 * 服务应用
 *
 * @return Application
 */
function app()
{
    return Application::getDefault();
}

/**
 * @return \FastD\Swoole\Server
 */
function server()
{
    return app()->getShared('server');
}

/**
 * @return swoole_server
 */
function swoole()
{
    return server()->getSwoole();
}
