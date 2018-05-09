<?php
/**
 * 用于直接输出日志到控制台
 */

namespace Uniondrug\Server\Utils;

use Phalcon\Di;
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
     * @param        $msg
     * @param string $level
     * @param array  $ext
     */
    public function log($msg, $level = 'INFO', ...$ext)
    {
        $level = strtoupper($level);
        $format = $this->getFormat($level);
        $time = date("Y-m-d H:i:s");
        $wid = 0;
        $processFlag = '#';
        $pid = getmypid();
        $msg = sprintf($msg, ...$ext);

        // only works under swoole mode
        if (Di::getDefault()->has('server')) {
            $processFlag = isset(swoole()->master_pid) ? '@' : '#';
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

            // Write to console
            $messages = sprintf("[%s %s%d.%d]<%s>\t%s\t</%s>%s", $time, $processFlag, $pid, $wid, $format, $level, $format, $msg);
            $this->writeln($messages);
        }

        // Log to file
        try {
            $logMethod = strtolower($level);

            $logger = app()->getLogger("server");
            if (method_exists($logger, $logMethod)) {
                $logMessage = sprintf("[%s%d.%d] %s", $processFlag, $pid, $wid, $msg);
                call_user_func_array([$logger, $logMethod], [$logMessage]);
            }
        } catch (\Exception $e) {
            $this->writeln(sprintf("[%s %s%d.%d]<error>\tERROR\t</error>%s", $time, $processFlag, $pid, $wid, $e->getMessage()));
        }
    }

    /**
     * @param       $msg
     * @param array $ext
     */
    public function error($msg, ...$ext)
    {
        $this->log($msg, 'ERROR', ...$ext);
    }

    /**
     * @param       $msg
     * @param array $ext
     */
    public function warning($msg, ...$ext)
    {
        $this->log($msg, 'WARNING', ...$ext);
    }

    /**
     * @param       $msg
     * @param array $ext
     */
    public function info($msg, ...$ext)
    {
        $this->log($msg, 'INFO', ...$ext);
    }

    /**
     * @param       $msg
     * @param array $ext
     */
    public function debug($msg, ...$ext)
    {
        $this->log($msg, 'DEBUG', ...$ext);
    }
}
