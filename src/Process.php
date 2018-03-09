<?php
/**
 * Process.php
 *
 */

namespace Uniondrug\Server;

use swoole_process;
use FastD\Swoole\Process as SwooleProcess;

class Process extends SwooleProcess
{
    /**
     * @var array
     */
    protected $options = [];

    /**
     * @param array $options
     *
     * @return $this
     */
    public function configure(array $options = [])
    {
        $this->options = $options;

        return $this;
    }

    /**
     * @param       $key
     * @param mixed $defaultValue
     *
     * @return mixed
     */
    public function getOption($key, $defaultValue = null)
    {
        if (isset($this->options[$key])) {
            return $this->options[$key];
        }

        return $defaultValue;
    }

    /**
     * @inheritdoc
     */
    public function handle(swoole_process $swoole_process)
    {
        // use sigkill to close process, to keep mysql connection in parents process
        register_shutdown_function(function () {
            swoole_process::kill(getmypid(), SIGKILL);
        });
    }
}
