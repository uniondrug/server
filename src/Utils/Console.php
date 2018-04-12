<?php
/**
 * 用于直接输出日志到控制台
 */

namespace Uniondrug\Server\Utils;

use Symfony\Component\Console\Output\ConsoleOutput;

/**
 * Class Console
 *
 * @package Uniondrug\Server
 */
class Console extends ConsoleOutput
{
    /**
     * @param $level
     *
     * @return string
     */
    public function getFormat($level)
    {
        switch (strtoupper($level)) {
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
    public function log($msg, $level = 'INFO')
    {
        $level = strtoupper($level);
        $format = $this->getFormat($level);
        $time = date("Y-m-d H:i:s");
        $wid = 0;
        $processFlag = isset(swoole()->master_pid) ? '@' : '#';
        $pid = getmypid();
        if (isset(swoole()->worker_id) && swoole()->worker_id >= 0) {
            $wid = swoole()->worker_id;
            if (swoole()->taskworker) {
                $processFlag = '^'; // taskworker
            } else {
                $processFlag = '*'; // worker
            }
        }
        if (isset(swoole()->manager_pid) && swoole()->manager_pid == $pid) {
            $processFlag = '$'; // manager
        }
        if (isset(swoole()->master_pid) && swoole()->master_pid == $pid) {
            $processFlag = '#'; // master
        }

        $messages = sprintf("[%s %s%d.%d]<%s>\t%s\t</%s>%s", $time, $processFlag, $pid, $wid, $format, $level, $format, $msg);
        $this->writeln($messages);
    }

    /**
     * @param $msg
     */
    public function error($msg)
    {
        $this->log($msg, 'ERROR');
    }

    /**
     * @param $msg
     */
    public function warning($msg)
    {
        $this->log($msg, 'WARNING');
    }

    /**
     * @param $msg
     */
    public function info($msg)
    {
        $this->log($msg, 'INFO');
    }

    /**
     * @param $msg
     */
    public function debug($msg)
    {
        $this->log($msg, 'DEBUG');
    }
}
