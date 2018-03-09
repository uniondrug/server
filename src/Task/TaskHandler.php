<?php
/**
 * TaskHandler.php
 *
 */
namespace Uniondrug\Server\Task;

/**
 * Class TaskHandler
 *
 */
abstract class TaskHandler extends \Uniondrug\Framework\Injectable
{
    /**
     * @param mixed $data Task data
     *
     * @return mixed
     */
     abstract public function handle($data = []);
}
