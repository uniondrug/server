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
use Exception;
use FastD\Container\Container;
use FastD\Container\ServiceProviderInterface;
use FastD\Http\HttpException;
use FastD\Http\Response;
use FastD\Http\ServerRequest;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Symfony\Component\Debug\Exception\FatalThrowableError;
use Throwable;
use UniondrugServer\Servitization\Client\Client;
use UniondrugServer\Wrapper\Config;
use UniondrugServer\Wrapper\Request;

/**
 * Class Application.
 */
class Application extends Container
{
    const VERSION = 'v1.0.0';

    /**
     * @var Application
     */
    public static $app;

    /**
     * @var string
     */
    protected $path;

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
        $this->path = $path;

        static::$app = $this;

        $this->add('app', $this);

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
        return $this->path;
    }

    /**
     * Application bootstrap.
     */
    public function bootstrap()
    {
        if (!$this->booted) {
            $this->add('PhalconDi', new \Pails\Container($this->path));

            // 使用改写的PhalconRequest，原生的有内存泄漏
            PhalconDi()->setShared('request', Request::class);

            // 初始化Phalcon应用
            {
                $phalconApplication = new \Pails\Application(PhalconDi());
                // Phalcon Version > 3.3.0
                //$phalconApplication->sendHeadersOnHandleRequest(false);
                //$phalconApplication->sendCookiesOnHandleRequest(false);
                $phalconApplication->boot();
                $this->add('PhalconApplication', $phalconApplication);
            }

            // 初始化客户端和配置文件服务
            $this->add('config', new Config());
            $this->add('client', new Client());

            date_default_timezone_set(config()->get('app.timezone', 'PRC'));
            $this->name = config()->get('app.appName', 'UnionDrug-Server');

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
     * @param ServiceProviderInterface[] $services
     */
    protected function registerServicesProviders(array $services)
    {
        foreach ($services as $service) {
            $this->register(new $service());
        }
    }

    /**
     * @param ServerRequestInterface $request
     *
     * @return Response|\Symfony\Component\HttpFoundation\Response|\Phalcon\Http\Response
     */
    public function handleRequest(ServerRequestInterface $request)
    {
        $this->add('request', $request);

        try {
            // 转换数据给Phalcon
            $this->wrapRequest($request);

            // PhalconApplication::handle() return a Response|false, or throw Exception
            $response = $this->wrapResponse($this->get('PhalconApplication')->handle());
        } catch (\Throwable $exception) {
            $response = $this->handleException($exception);
        }

        $this->add('response', $response);

        // 每次请求完之后，重置Request和Response对象，清空超全局变量
        $_GET = $_POST = $_REQUEST = $_FILES = $_SERVER = [];
        PhalconDi()->getShared('request')->setRawBody(null)->setPutCache(null);
        PhalconDi()->getShared('response')->setStatusCode(200)->setContent(null)->resetHeaders();

        return $response;
    }

    /**
     * @param Response|\Symfony\Component\HttpFoundation\Response|\Phalcon\Http\Response $response
     */
    public function handleResponse($response)
    {
        $response->send();
    }

    /**
     * @param \Exception|\Throwable|\Error $e
     *
     * @return Response
     */
    public function handleException($e)
    {
        // Save to container
        $this->add('exception', $e);

        // Log
        $logContext = [
            'error' => $e->getMessage(),
            'errno' => $e->getCode(),
            'file'  => $e->getFile(),
            'line'  => $e->getLine(),
            'trace' => $e->getTraceAsString(),
        ];
        logger("framework")->error("{error} ({errno}) in {file}:{line}\nStack trace:\n{trace}", $logContext);

        // Response
        if ($e instanceof \Error) {
            $statusCode = 500;
        } else {
            $statusCode = 200;
        }
        return json(call_user_func(config()->get('exception.response'), $e), $statusCode);
    }

    /**
     * Started application.
     *
     * @return int
     */
    public function run()
    {
        $request = ServerRequest::createServerRequestFromGlobals();

        $response = $this->handleRequest($request);

        $this->handleResponse($response);

        return $this->shutdown($request, $response);
    }

    /**
     * @param ServerRequestInterface                                       $request
     * @param ResponseInterface|\Symfony\Component\HttpFoundation\Response $response
     *
     * @return int
     */
    public function shutdown(ServerRequestInterface $request, $response)
    {
        $this->offsetUnset('request');
        $this->offsetUnset('response');
        if ($this->offsetExists('exception')) {
            $this->offsetUnset('exception');
        }
        unset($request, $response);

        return 0;
    }

    /**
     * @param ServerRequestInterface $request
     *
     * @return void
     */
    public function wrapRequest(ServerRequestInterface $request)
    {
        // 设置原始POST的BODY数据
        PhalconDi()->getShared('request')->setRawBody((string) $request->getBody());

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
     * @param bool|\Phalcon\Http\Response $response
     *
     * @return \FastD\Http\Response
     */
    public function wrapResponse($response)
    {
        if ($response instanceof \Phalcon\Http\Response) {
            $statusCode = (int) $response->getStatusCode();
            $wrappedResponse = new Response($response->getContent(), $statusCode ?: 200, $response->getHeaders()->toArray());
            if ($response->getCookies()) {
                foreach ($response->getCookies() as $cookie) {
                    $wrappedResponse->withCookie($cookie->getName(), $cookie->getValue(),
                        $cookie->getExpiration(), $cookie->getPath(), $cookie->getDomain(), $cookie->getSecure(), $cookie->getHttpOnly());
                }
            }

            return $wrappedResponse;
        }

        throw new \RuntimeException("Internal Error");
    }
}
