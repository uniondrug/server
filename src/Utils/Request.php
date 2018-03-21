<?php
/**
 * Request.php
 *
 */

namespace Uniondrug\Server\Utils;

/**
 * Class Request
 *
 * @package Uniondrug\Server\Utils
 */
class Request
{
    /**
     * @var \swoole_http_request
     */
    protected $request;

    /**
     * Request constructor.
     *
     * @param \swoole_http_request $request
     */
    public function __construct(\swoole_http_request $request)
    {
        $this->request = $request;
    }

    /**
     * Request Time
     *
     * @return double
     */
    public function getRequestTime()
    {
        return isset($this->request->server['request_time_float']) ? (double) $this->request->server['request_time_float'] : microtime(1);
    }

    /**
     * @return string
     */
    public function getRequestUrl()
    {
        $uri = $this->getURI();
        $queryString = $this->getServer('QUERY_STRING');
        if ($queryString) {
            return $uri . '?' . $queryString;
        }
        return $uri;
    }

    /**
     * @return null|string
     */
    public function getRequestProto()
    {
        return $this->getServer('SERVER_PROTOCOL');
    }

    /**
     * RETURN: GET /some/path?query=value HTTP/1.1
     *
     * @return string
     */
    public function getUserRequest()
    {
        return sprintf("%s %s %s", $this->getMethod(), $this->getRequestUrl(), $this->getRequestProto());
    }

    /**
     * @param $name
     *
     * @return null|mixed
     */
    public function getQuery($name)
    {
        if (isset($this->request->get[$name])) {
            return $this->request->get[$name];
        }

        return null;
    }

    /**
     * @param $name
     *
     * @return null|mixed
     */
    public function getPost($name)
    {
        if (isset($this->request->post[$name])) {
            return $this->request->post[$name];
        }

        return null;
    }

    /**
     * @param $name
     *
     * @return null|mixed
     */
    public function getCookie($name)
    {
        if (isset($this->request->cookie[$name])) {
            return $this->request->cookie[$name];
        }

        return null;
    }

    /**
     * @return string
     */
    public function getRawBody()
    {
        return $this->request->rawContent();
    }

    /**
     * @param $name
     *
     * @return string
     */
    public function getHeader($name)
    {
        $name = strtolower($name);
        if (isset($this->request->header[$name])) {
            return $this->request->header[$name];
        }

        $name = strtr($name, '-', '_');
        if (isset($this->request->server[$name])) {
            return $this->request->server[$name];
        }

        if (isset($this->request->server['http_' . $name])) {
            return $this->request->server['http_' . $name];
        }

        return '';
    }

    /**
     * @param $name
     *
     * @return null|string
     */
    public function getServer($name)
    {
        $name = strtolower($name);
        if (isset($this->request->server[$name])) {
            return $this->request->server[$name];
        }

        return null;
    }

    /**
     * @return string
     */
    public function getScheme()
    {
        if ($https = $this->getServer('https')) {
            if ($https != 'off') {
                return 'https';
            }
        }

        return 'http';
    }

    /**
     * @return string
     */
    public function getServerAddress()
    {
        if ($serverAddr = $this->getServer('SERVER_ADDR')) {
            return $serverAddr;
        }

        return gethostbyname('localhost');
    }

    /**
     * @return string
     */
    public function getServerName()
    {
        if ($serverName = $this->getServer('SERVER_NAME')) {
            return $serverName;
        }

        return 'localhost';
    }

    /**
     * @return string|false
     */
    public function getClientAddress()
    {
        $address = null;
        $address = $this->getServer('HTTP_X_FORWARDED_FOR');
        if ($address === null) {
            $address = $this->getServer('HTTP_CLIENT_IP');
        }
        if ($address === null) {
            $address = $this->getServer('REMOTE_ADDR');
        }

        if (is_string($address)) {
            if (strpos($address, ',') !== false) {
                return explode(',', $address)[0];
            }

            return $address;
        }

        return false;
    }

    /**
     * @return string
     */
    public function getHttpHost()
    {
        $host = $this->getServer('HTTP_HOST');
        if (!$host) {
            $host = $this->getServer('SERVER_NAME');
            if (!$host) {
                $host = $this->getServer('SERVER_ADDR');
            }
        }

        return (string) $host;

    }

    /**
     * @return string
     */
    public function getURI()
    {
        return $this->getServer('REQUEST_URI');
    }

    /**
     * @return int
     */
    public function getPort()
    {
        $host = $this->getServer('HTTP_HOST');
        if ($host) {
            $pos = strrpos($host, ':');
            if (false !== $pos) {
                return (int) substr($host, $pos + 1);
            }

            return 'https' === $this->getScheme() ? 443 : 80;
        }

        return (int) $this->getServer('SERVER_PORT');
    }

    /**
     * @return string
     */
    public function getMethod()
    {
        $validMethod = ['GET', 'POST', 'PUT', 'DELETE', 'HEAD', 'OPTIONS', 'PATCH', 'PURGE', 'TRACE', 'CONNECT'];
        if ($method = $this->getServer('REQUEST_METHOD')) {
            $method = strtoupper($method);
            if (in_array($method, $validMethod)) {
                return $method;
            }
        }

        return 'GET';
    }

    /**
     * @return string
     */
    public function getUserAgent()
    {
        return $this->request->header['user-agent'];
    }

    /**
     * @return string
     */
    public function getHTTPReferer()
    {
        return $this->getServer('HTTP_REFERER');
    }

    /**
     * @return string
     */
    public function getContentType()
    {
        if ($contentType = $this->getServer('CONTENT_TYPE')) {
            return $contentType;
        }
        if ($contentType = $this->getServer('HTTP_CONTENT_TYPE')) {
            return $contentType;
        }

        return null;
    }

    /**
     * @return null|string
     */
    public function getBasicAuthUser()
    {
        return $this->getServer('PHP_AUTH_USER');
    }
}
