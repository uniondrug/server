<?php
/**
 * 自动Reload服务器
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

        console()->debug('[ReloadProcess] process started.');

        $this->lastMD5 = $this->md5File();

        $this->run();
    }

    public function run()
    {
        while (true) {
            sleep($this->interval);
            if (!app()->getConfig()->path('server.autoreload', false)) {
                continue;
            }
            $md5File = $this->md5File();
            if (strcmp($this->lastMD5, $md5File) !== 0) {
                console()->debug("[ReloadProcess] File changed, start reloading ...");
                server()->reload();
                console()->debug("[ReloadProcess] Reloaded");
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
}
