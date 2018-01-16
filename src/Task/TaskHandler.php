<?php
/**
 * TaskHandler.php
 *
 */
namespace UniondrugServer\Task;

/**
 * Class TaskHandler
 *
 */
abstract class TaskHandler
{
    /**
     * @param string $data Task data
     *
     * @return mixed
     */
     abstract public function handle($data);
}
