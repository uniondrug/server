<?php
/**
 * Task.php
 *
 */

namespace UniondrugServer;

use UniondrugServer\Task\TaskHandler;

/**
 * Class Task
 *
 */
class Task
{
    /**
     * 处理器的类名
     *
     * @var string
     */
    protected $handler;

    /**
     * 需要分发给TaskWorker处理的数据，字符串格式
     *
     * @var string
     */
    protected $data;

    /**
     * Task constructor.
     *
     * @param $handle
     * @param $data
     */
    public function __construct($handle, $data)
    {
        $this->handler = $handle;
        $this->data = $data;
    }

    /**
     * @return mixed
     */
    public function getHandler()
    {
        return $this->handler;
    }

    /**
     * @return mixed
     */
    public function getData()
    {
        return $this->data;
    }

    /**
     * @return mixed
     */
    public function handle()
    {
        $handler = $this->handler;
        if (app()->has($handler)) {
            $handlerInstance = app()->get($handler);
            if (is_object($handlerInstance) && $handlerInstance instanceof TaskHandler) {
                return $handlerInstance->handle($this->data);
            } else {
                throw new \RuntimeException("Handler service " . $handler . " found, but is not a valid TaskHandler");
            }
        }
        if (class_exists($handler) && is_subclass_of($handler, TaskHandler::class)) {
            $handlerInstance = new $handler();
            app()->add($handler, $handlerInstance);

            return $handlerInstance->handle($this->data);
        }
        throw new \RuntimeException('No handle named ' . $handler);
    }
}
