<?php
/**
 * Table.php
 *
 */
namespace UniondrugServer;

class Table extends \Swoole\Table
{
    protected $columns = [];

    /**
     * @param $size
     *
     * @return static
     */
    public static function setup($size)
    {
        $table = new static($size);

        return $table->initialize();
    }

    /**
     * @return static
     */
    protected function initialize()
    {
        foreach ($this->columns as $columnName => $definition) {
            $this->column($columnName, $definition[0], $definition[1]);
        }

        if (!$this->create()) {
            throw new \RuntimeException('Create swoole_table failed.');
        }

        return $this;
    }

    /**
     * @param $key
     * @param $column
     *
     * @return mixed
     */
    public function getColumn($key, $column)
    {
        if (!array_key_exists($column, $this->columns)) {
            throw new \RuntimeException('\'' . $column . '\' not exists');
        }
        if ($row = $this->get($key)) {
            return $row[$column];
        }
        return false;
    }

    /**
     * @return array
     */
    public function toArray()
    {
        $array = [];
        foreach ($this as $key => $value) {
            $array[$key] = $value;
        }
        return $array;
    }
}
