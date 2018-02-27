<?php
/**
 * ReloadProcess.php
 *
 */

namespace Uniondrug\Server\Processes;

use Phalcon\Text;
use swoole_process;
use Uniondrug\Server\Process;

class ReloadProcess extends Process
{
    private $interval = 3;

    private $lastMD5;

    public function handle(swoole_process $swoole_process)
    {
        parent::handle($swoole_process);

        process_rename(app()->getName() . ' [ReloadProcess]');

        $this->lastMD5 = $this->md5File();

        $this->run();
    }

    public function run()
    {
        while (true) {
            sleep($this->interval);
            $md5File = $this->md5File();
            if (strcmp($this->lastMD5, $md5File) !== 0) {
                $this->log("File changed, start reloading ...");
                server()->reload();
                $this->log("Reloaded");
            }
            $this->lastMD5 = $md5File;
        }
    }

    public function md5File()
    {
        $md5File = array();
        $iterator = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator(app()->appPath()), \RecursiveIteratorIterator::SELF_FIRST);
        foreach ($iterator as $item) {
            if (Text::endsWith($item, '.php', false)) {
                $md5File[] = md5_file($item);
            }
        }
        return implode('', $md5File);
    }

    public function log($msg)
    {
        $time = date("Y-m-d H:i:s");
        $pid = getmypid();

        echo sprintf("[%s] [\e[0;32m%9s\e[0m] [%05d] %s", $time, 'NOTICE', $pid, $msg) . PHP_EOL;
    }
}
