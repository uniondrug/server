<?php

use Uniondrug\Server\Application;

if (!function_exists('app')) {
    /**
     * 服务应用
     *
     * @return Application
     */
    function app()
    {
        return Application::getDefault();
    }
}

if (!function_exists('server')) {
    /**
     * @return \FastD\Swoole\Server
     */
    function server()
    {
        return app()->getShared('server');
    }
}

if (!function_exists('swoole')) {
    /**
     * @return swoole_server
     */
    function swoole()
    {
        return server()->getSwoole();
    }
}

if (!function_exists('console')) {
    /**
     * @return \Uniondrug\Server\Console
     */
    function console()
    {
        return app()->getShared(\Uniondrug\Server\Console::class);
    }
}