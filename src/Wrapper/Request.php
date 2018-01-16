<?php
/**
 * Request.php
 *
 */

namespace UniondrugServer\Wrapper;

use Phalcon\Http\Request\Exception;

class Request extends \Phalcon\Http\Request
{
    public function setRawBody($body = null)
    {
        $this->_rawBody = $body;
        return $this;
    }

    public function setPutCache($data = null)
    {
        $this->_putCache = $data;
        return $this;
    }

    public function getMethodReplacement()
    {
        $returnMethod = "";

        if (isset($_SERVER['REQUEST_METHOD'])) {
            $returnMethod = strtoupper($_SERVER['REQUEST_METHOD']);
        } else {
            return "GET";
        }

        if ("POST" === $returnMethod) {
            $overrideMethod = $this->getHeader("X-HTTP-METHOD-OVERRIDE");
            if (!empty($overrideMethod)) {
                $returnMethod = strtoupper($overrideMethod);
            } elseif ($this->_httpMethodParameterOverride) {
                if (isset($_REQUEST['_method'])) {
                    $returnMethod = strtoupper($_REQUEST['_method']);
                }
            }
        }

        if (!$this->isValidHttpMethod($returnMethod)) {
            return "GET";
        }

        return $returnMethod;
    }

    public function isMethod($methods, $strict = null)
    {
        $httpMethod = $this->getMethodReplacement();
        if (is_string($methods)) {
            if ($strict && !$this->isValidHttpMethod($methods)) {
                throw new Exception("Invalid HTTP method: " . $methods);
            }

            return $methods == $httpMethod;
        }

        if (is_array($methods)) {
            foreach ($methods as $method) {
                if ($this->isMethod($method, $strict)) {
                    return true;
                }
            }

            return false;
        }

        if ($strict) {
            throw new Exception("Invalid HTTP method: non-string");
        }

        return false;
    }
}
