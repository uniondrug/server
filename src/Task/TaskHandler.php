<?php
/**
 * TaskHandler.php
 *
 */
namespace UniondrugServer\Task;

use Phalcon\Di\Injectable;

/**
 * Class TaskHandler
 *
 */
abstract class TaskHandler extends Injectable
{
    /**
     * @param string $data Task data
     *
     * @return mixed
     */
     abstract public function handle($data);
}
