<?php
/**
 * 封装Phalcon\Config
 *
 */

namespace UniondrugServer\Wrapper;

class Config
{
    /**
     * @param      $key
     * @param null $defaultValue
     *
     * @return mixed
     */
    public function get($key, $defaultValue = null)
    {
        return $this->toArray(PhalconDi()->get('config')->path($key, $defaultValue));
    }

    /**
     * @param $value
     *
     * @return array|mixed
     */
    public function toArray($value)
    {
        if ($value instanceof \Phalcon\Config) {
            $value = $value->toArray();
        }
        if (is_array($value)) {
            foreach ($value as $k => $v) {
                $value[$k] = $this->toArray($v);
            }
        }

        return $value;
    }
}
