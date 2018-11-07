<?php
/**
 * @author    jan huang <bboyjanhuang@gmail.com>
 * @copyright 2016
 * @see       https://www.github.com/janhuang
 * @see       https://fastdlabs.com
 */
namespace Uniondrug\Server;

use swoole_server;
use Symfony\Component\Console\Input\InputInterface;
use Uniondrug\Server\Servitization\Server\HTTPServer;

/**
 * Class App.
 */
class Server
{
    /**
     * @var Application
     */
    protected $application;
    /**
     * @var \Uniondrug\Swoole\Server
     */
    protected $server;
    /**
     * @var InputInterface
     */
    protected $input;

    /**
     * Server constructor.
     * @param Application $application
     */
    public function __construct(Application $application, InputInterface $input)
    {
        $this->application = $application;
        $this->input = $input;
        /**
         * 支持IP参数
         * --ipaddr 192.168.10.117
         * --port 8080
         */
        $host = $application->getConfig()->path('server.host');
        // 1. 指定端口号
        $port = $input->getOption('port');
        if ($port) {
            $host = preg_replace("/:\d+/", ":{$port}", $host);
        }
        // 2. 指定IP地址
        $ipaddr = $input->getOption('ipaddr');
        if ($ipaddr) {
            $host = preg_replace("/\d+\.\d+\.\d+\.\d+/", "{$ipaddr}", $host);
        }
        // 3. 创建实例
        $server = $application->getConfig()->path('server.class', HTTPServer::class);
        $this->server = $server::createServer($application->getName(), $host, $application->getConfig()->path('server.options')->toArray(), console());
        $application->setShared('server', $this->server);
        $this->initListeners();
        $this->initProcesses();
    }

    /**
     * @return swoole_server
     */
    public function getSwoole()
    {
        return $this->server->getSwoole();
    }

    /**
     * @return \Swoole\Server
     */
    public function bootstrap()
    {
        return $this->server->bootstrap();
    }

    /**
     * @return $this
     */
    public function initListeners()
    {
        $ipaddr = $this->input->getOption('ipaddr');
        $listeners = $this->application->getConfig()->path('server.listeners', []);
        foreach ($listeners as $listener) {
            // 替换配置文件中的IP地址
            if ($ipaddr) {
                $listener['host'] = preg_replace("/\d+\.\d+\.\d+\.\d+/", "{$ipaddr}", $listener['host']);
            }
            $this->server->listen(new $listener['class']($this->application->getName().' ports', $listener['host'], isset($listener['options']) ? $listener['options'] : []));
        }
        return $this;
    }

    /**
     * @return $this
     */
    public function initProcesses()
    {
        $processes = $this->application->getConfig()->path('server.processes', []);
        foreach ($processes as $process) {
            $this->server->process(new $process($this->application->getName().' process'));
        }
        return $this;
    }

    /**
     * @return $this
     */
    public function daemon()
    {
        $this->server->daemon();
        return $this;
    }

    /**
     * @return int
     */
    public function start()
    {
        $this->bootstrap();
        return $this->server->start();
    }

    /**
     * @return int
     */
    public function stop()
    {
        return $this->server->shutdown();
    }

    /**
     * @return int
     */
    public function restart()
    {
        return $this->server->restart();
    }

    /**
     * @return int
     */
    public function reload()
    {
        return $this->server->reload();
    }

    /**
     * @return int
     */
    public function status()
    {
        return $this->server->status();
    }

    /**
     * @param array $dir
     * @return int
     */
    public function watch(array $dir = ['.'])
    {
        return $this->server->watch($dir);
    }

    /**
     * @param InputInterface $input
     */
    public function run(InputInterface $input)
    {
        // 设置守护进程
        if ($input->hasParameterOption([
            '--daemon',
            '-d'
        ], true)
        ) {
            $this->daemon();
        }
        switch ($input->getArgument('action')) {
            case 'start':
                if ($input->hasParameterOption(['--dir'])) {
                    $this->watch([$input->getOption('dir')]);
                } else {
                    $this->start();
                }
                break;
            case 'stop':
                $this->stop();
                break;
            case 'restart':
                $this->restart();
                break;
            case 'reload':
                $this->reload();
                break;
            case 'status':
            default:
                $this->status();
        }
    }
}
