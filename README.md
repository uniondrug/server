# UniondrugServer 基于Swoole的PHP应用服务器

基于Phalcon的uniondrug/framework项目中，使用本应用服务器可以提高性能。

## 安装

```
$ composer requre uniondrug/server
$ cp vendor/uniondrug/server/server.php.example config/server.php
$ cp vendor/uniondrug/server/exception.php.example config/exception.php 
```

## 使用

```
$ php server --help
   __  __      _             ____                  
  / / / /___  (_)___  ____  / __ \_______  ______ _
 / / / / __ \/ / __ \/ __ \/ / / / ___/ / / / __ `/
/ /_/ / / / / / /_/ / / / / /_/ / /  / /_/ / /_/ / 
\____/_/ /_/_/\____/_/ /_/_____/_/   \__,_/\__, /  
                                          /____/   Server 1.0.0
Usage:
 server [command] [option]

Options:
  -d, --daemon   Run server as daemon, Do not ask any interactive question
  -t, --path     Web root relative path. Default: /Users/nishurong/PhpstormProjects/php-workspace/uniondrug/cn.uniondrug.dingding
  -e, --env      Environment. Default: development
  -h, --help     Show this help

Available commands:
  start   Start the server
  stop    Stop the server
  reload  Reload the server
  status  Show the server status [default]
```

## 配置

`server.php` 中配置的是Swoole服务器的运行参数，详细参数请参考swoole文档：

```php
return [
    'default'    => [
        'host'      => 'http://0.0.0.0:9527',
        'class'     => \UniondrugServer\Servitization\Server\HTTPServer::class,
        'options'   => [
            'pid_file'        => __DIR__ . '/../tmp/pid/server.pid',
            'worker_num'      => 1,
            'task_worker_num' => 1,
        ],
        'processes' => [

        ],
        'listeners' => [
            [
                'class' => \UniondrugServer\Servitization\Server\ManagerServer::class,
                'host'  => 'tcp://0.0.0.0:9530',
            ],
        ],
    ],
    'production' => [
        'options' => [
            'worker_num' => 5,
        ],
    ],
];
```

`exception.php`中配置的是异常日志和输出格式：

```php
return [
    'default' => [
        'response' => function (Exception $e) {
            return [
                'error'    => $e->getMessage(),
                'errno'    => '-1',
                'dataType' => 'ERROR',
                'code'     => $e->getCode(),
                'file'     => $e->getFile(),
                'line'     => $e->getLine(),
                'trace'    => explode("\n", $e->getTraceAsString()),
            ];
        },
        'log'      => function (Exception $e) {
            return [
                'msg'   => $e->getMessage(),
                'code'  => $e->getCode(),
                'file'  => $e->getFile(),
                'line'  => $e->getLine(),
                'trace' => explode("\n", $e->getTraceAsString()),
            ];
        },
    ],
];
```