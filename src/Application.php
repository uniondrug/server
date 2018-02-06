<?php
/**
 * 基于Swoole的应用。从FastD移植过来。为适配Phalcon，增加了输入(Request)输出(Response)的转换功能。
 *
 * @author    XueronNi <xueronni@uniondrug.cn>
 * @copyright 2018
 *
 * @see       https://www.uniondrug.cn
 * @see       https://github.com/uniondrug/server
 */

namespace UniondrugServer;

use ErrorException;
use FastD\Http\Response;
use Pails\Container;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use UniondrugServer\Wrapper\Request;

/**
 * Class Application.
 */
class Application extends Container
{
    /**
     * @var string
     */
    protected $name;

    /**
     * @var bool
     */
    protected $booted = false;

    /**
     * AppKernel constructor.
     *
     * @param $path
     */
    public function __construct($path)
    {
        parent::__construct($path);

        $this->bootstrap();
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @return bool
     */
    public function isBooted()
    {
        return $this->booted;
    }

    /**
     * @return string
     */
    public function getPath()
    {
        return $this->baseDir;
    }

    /**
     * Application bootstrap.
     */
    public function bootstrap()
    {
        if (!$this->booted) {
            // 使用改写的PhalconRequest，原生的有内存泄漏
            $this->setShared('request', Request::class);

            // 初始化Phalcon应用
            {
                $phalconApplication = new \Pails\Application($this);
                // Phalcon Version > 3.3.0
                //$phalconApplication->sendHeadersOnHandleRequest(false);
                //$phalconApplication->sendCookiesOnHandleRequest(false);
                $phalconApplication->boot();
                $this->setShared('PhalconApplication', $phalconApplication);
            }

            date_default_timezone_set($this->getConfig()->get('app.timezone', 'PRC'));
            $this->name = $this->getConfig()->path('app.appName', 'UnionDrug-Server');

            $this->registerExceptionHandler();
            $this->booted = true;
        }
    }

    /**
     * 设置异常处理方法
     */
    protected function registerExceptionHandler()
    {
        error_reporting(-1);

        set_exception_handler([$this, 'handleException']);

        set_error_handler(function ($level, $message, $file = '', $line = 0) {
            throw new ErrorException($message, 0, $level, $file, $line);
        });
    }

    /**
     * @param ServerRequestInterface $request
     *
     * @return ResponseInterface
     */
    public function handleRequest(ServerRequestInterface $request)
    {
        try {
            // 转换数据给Phalcon
            $this->wrapRequest($request);

            // PhalconApplication::handle() return a Response|false, or throw Exception
            $response = $this->wrapResponse($this->get('PhalconApplication')->handle());

        } catch (\Throwable $exception) {
            $response = $this->wrapResponse($this->handleException($exception));
        }

        return $response;
    }

    /**
     * 清理超全局变量和容器内的对象
     *
     * @param ServerRequestInterface     $request
     * @param ResponseInterface|Response $response
     *
     * @return int
     */
    public function shutdown(ServerRequestInterface $request, $response)
    {
        unset($request, $response);

        // 每次请求完之后，重置Request和Response对象，清空超全局变量
        $_GET = $_POST = $_REQUEST = $_FILES = $_SERVER = [];
        $this->getShared('request')->setRawBody(null)->setPutCache(null);
        $this->getShared('response')->setContent(null)->resetHeaders();

        return 0;
    }

    /**
     * 将swoole的psr规范的request，置换到全局变量，供Phalcon使用
     *
     * @param ServerRequestInterface $request
     *
     * @return void
     */
    public function wrapRequest(ServerRequestInterface $request)
    {
        // 设置原始POST的BODY数据
        $this->getShared('request')->setRawBody((string) $request->getBody());

        // 设置超全局变量 GET POST COOKIE SERVER REQUEST
        $_GET = $request->getQueryParams();
        $_POST = $request->getParsedBody();
        $_COOKIE = $request->getCookieParams();
        $_REQUEST = array_merge_recursive($_GET, $_POST, $_COOKIE);
        $_SERVER = $request->getServerParams();

        // 设置Headers
        foreach ($request->getHeaders() as $key => $value) {
            $serverKey = 'HTTP_' . strtoupper($key);
            if (!isset($_SERVER[$serverKey])) {
                $_SERVER[$serverKey] = $request->getHeaderLine($key); // getHeaderLine return a string.
            }
        }

        // 为PHALCON设置rewrite路径
        $_GET['_url'] = $_SERVER['REQUEST_URI'];

        // 上传的文件处理
        if (count($files = $request->getUploadedFiles())) {
            $_FILES = [];
            foreach ($files as $name => $file) {
                /* @var \FastD\Http\UploadedFile $file */
                $_FILES[$name] = [
                    'name'     => $file->getPostFilename(),
                    'type'     => $file->getMimeType(),
                    'tmp_name' => $file->getFilename(),
                    'error'    => $file->getError(),
                    'size'     => $file->getSize(),
                ];
            }
        }
    }

    /**
     * 将Phalcon的Response对象，转化成FastD的Response对象
     *
     * @param bool|\Phalcon\Http\Response $response
     *
     * @return \FastD\Http\Response
     */
    public function wrapResponse($response)
    {
        if ($response instanceof \Phalcon\Http\Response) {
            $statusCode = (int) $response->getStatusCode();
            $wrappedResponse = new Response($response->getContent(), $statusCode ?: 200, array_filter($response->getHeaders()->toArray()));
            if ($response->getCookies()) {
                foreach ($response->getCookies() as $cookie) {
                    $wrappedResponse->withCookie($cookie->getName(), $cookie->getValue(),
                        $cookie->getExpiration(), $cookie->getPath(), $cookie->getDomain(), $cookie->getSecure(), $cookie->getHttpOnly());
                }
            }

            return $wrappedResponse;
        }

        throw new \RuntimeException("Internal Error", -1);
    }
}
