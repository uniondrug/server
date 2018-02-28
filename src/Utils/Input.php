<?php
/**
 * TCP服务器输入参数解析工具
 *
 * @author Xueron Ni
 * @date   2018-02-28
 */

namespace Uniondrug\Server\Utils;

/**
 * Class Input
 *
 * @package Uniondrug\Server\Utils
 */
class Input
{
    /**
     * @var string
     */
    protected $cmd;

    /**
     * @var array
     */
    protected $params = [];

    /**
     * Input constructor.
     *
     * @param $input
     */
    public function __construct($input)
    {
        $input = trim($input);
        if (strpos($input, ' ') === false) {
            $this->cmd = strtolower($input);
        } else {
            $this->cmd = strtolower(substr($input, 0, strpos($input, ' ')));
            $this->params = $this->parseParams(substr($input, strpos($input, ' ') + 1));
        }
    }

    /**
     * @param $input
     *
     * @return static
     */
    public static function parse($input)
    {
        return new static($input);
    }

    /**
     * @return string
     */
    public function getCmd()
    {
        return $this->cmd;
    }

    /**
     * @param      $index
     * @param null $default
     *
     * @return mixed|null
     */
    public function getParam($index, $default = null)
    {
        if (isset($this->params[$index])) {
            return $this->params[$index];
        }

        return $default;
    }

    /**
     * @return array
     */
    public function getParams()
    {
        return $this->params;
    }

    /**
     * @param $paramString
     *
     * @return array
     */
    public function parseParams($paramString)
    {
        $paramString = trim($paramString);
        if (empty($paramString)) {
            return [];
        }

        $output = [];
        $length = strlen($paramString);
        $quoted = false; // 在引号内

        $param = '';
        for ($i = 0; $i < $length; $i++) {
            $char = substr($paramString, $i, 1);
            if ($char == ' ' && !$quoted) {
                if (!empty($param)) {
                    $output[] = $param;
                    $param = '';
                }
                continue;
            }
            if ($char == '"') {
                if (!$quoted) {
                    $quoted = true;
                } else {
                    $quoted = false;
                    if (!empty($param)) {
                        $output[] = $param;
                        $param = '';
                    }
                }
                continue;
            }
            $param .= $char;
        }
        if (!empty($param) && !$quoted) {
            $output[] = $param;
        }

        return $output;
    }
}
