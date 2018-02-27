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
  -t, --path     Web root relative path. Default: /path/to/current/workspace
  -e, --env      Environment. Default: development
  -h, --help     Show this help

Available commands:
  start   Start the server
  stop    Stop the server
  reload  Reload the server
  status  Show the server status [default]
```

## 配置

`server.php` 中配置的是Swoole服务器的运行参数，详细参数请参考`swoole`文档：

```php
return [
    'default'    => [
        'host'      => 'http://0.0.0.0:9527',
        'class'     => \Uniondrug\Server\Servitization\Server\HTTPServer::class,
        'options'   => [
            'pid_file'        => __DIR__ . '/../tmp/pid/server.pid',
            'worker_num'      => 1,
            'task_worker_num' => 1,
        ],
        'processes' => [
            // 开发环境，自动监控文件改动，改动后自动Reload服务
            \Uniondrug\Server\Processes\ReloadProcess::class,
        ],
        'listeners' => [
            [
                'class' => \Uniondrug\Server\Servitization\Server\ManagerServer::class,
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

### 数据库

Swoole的Worker会保持数据库的长连接，如果长时间服务器没有请求发生，可能会出现数据库服务器断开连接的情况。

可以通过在数据库的配置文件，增加一个参数，启动定时器来让worker进程与数据库服务器保持心跳。配置文件：`database.php`，配置参数 `interval`。参数为0时不启动定时器，大于0时，作为心跳间隔，单位：秒。

```php
return [
    'default'    => [
        'adapter'    => 'mysql',
        'debug'      => true,
        'useSlave'   => false,
        'interval'   => 0,
        'connection' => [
            'host'     => '127.0.0.1',
            'port'     => 3306,
            'username' => 'root',
            'password' => '',
            'dbname'   => 'dbname',
            'charset'  => 'utf8',
        ],
    ],
];
```

### 任务分发

### 进程管理

### 进程间通信


