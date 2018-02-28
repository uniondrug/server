<?php
/**
 * 用于直接输出日志到控制台
 */

namespace Uniondrug\Server;

use Phalcon\Di\Injectable;
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\Console\Output\ConsoleOutputInterface;

/**
 * Class Console
 *
 * @package Uniondrug\Server
 */
class Console extends Injectable
{
    /**
     * @return ConsoleOutputInterface
     */
    private function getConsoleOutput()
    {
        return $this->getDI()->getShared(ConsoleOutput::class);
    }

    /**
     * @param $level
     *
     * @return string
     */
    private function getFormat($level)
    {
        switch ($level) {
            case 'ERROR':
            case 'WARNING':
                return 'error';
            case 'INFO':
                return 'info';
            case 'DEBUG':
            default:
                return 'comment';
        }

    }

    /**
     * @param $level
     * @param $msg
     */
    public function log($level, $msg)
    {
        $time = date("Y-m-d H:i:s");
        $pid = getmypid();
        $level = strtoupper($level);
        $format = $this->getFormat($level);

        $this->getConsoleOutput()->writeln(sprintf("[%s] <%s>%9s</%s> [%05d] %s", $time, $format, $level, $format, $pid, $msg));
    }

    /**
     * @param $msg
     */
    public function error($msg)
    {
        $this->log('ERROR', $msg);
    }

    /**
     * @param $msg
     */
    public function warning($msg)
    {
        $this->log('WARNING', $msg);
    }

    /**
     * @param $msg
     */
    public function info($msg)
    {
        $this->log('INFO', $msg);
    }

    /**
     * @param $msg
     */
    public function debug($msg)
    {
        $this->log('DEBUG', $msg);
    }
}
