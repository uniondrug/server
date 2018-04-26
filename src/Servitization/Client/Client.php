<?php
/**
 * @author    jan huang <bboyjanhuang@gmail.com>
 * @copyright 2017
 *
 * @see       https://www.github.com/janhuang
 * @see       http://www.fast-d.cn/
 */

namespace Uniondrug\Server\Servitization\Client;

use Uniondrug\Http\Response;
use Uniondrug\Packet\Json;
use Uniondrug\Swoole\Client as SwooleClient;

/**
 * Class Consumer.
 */
class Client extends SwooleClient
{
    /**
     * @return string
     */
    public function getProtocol()
    {
        return $this->scheme;
    }

    /**
     * 同步客户端，已经连接过，则尝试ping一下。
     *
     * @return bool
     */
    public function ping()
    {
        if ($this->client->isConnected() && !$this->async) {
            $this->client->send('ping');
            $res = $this->receive();
            return $res === 'pong';
        }

        return false;
    }

    /**
     * @param string $data
     *
     * @return Response
     * @throws \Uniondrug\Packet\Exceptions\PacketException
     */
    public function send($data = '')
    {
        $response = parent::send($data);

        return $this->wrapResponse($response);
    }

    /**
     * @param $response
     *
     * @return Response
     */
    protected function wrapResponse($response)
    {
        $statusCode = 200;
        $headers = [];

        if (false !== (strpos($response, "\r\n\r\n")) && false !== strpos($this->scheme, 'http')) {
            list($responseHeaders, $response) = explode("\r\n\r\n", $response, 2);
            $responseHeaders = preg_split('/\r\n/', $responseHeaders, null, PREG_SPLIT_NO_EMPTY);

            $code = array_shift($responseHeaders);
            list(, $statusCode) = explode(' ', $code);
            $headers = [];
            array_map(function ($headerLine) use (&$headers) {
                list($key, $value) = explode(':', $headerLine, 2);
                $headers[$key] = trim($value);
                unset($headerLine, $key, $value);
            }, $responseHeaders);

            if (isset($headers['Transfer-Encoding']) && $headers['Transfer-Encoding'] == 'chunked') {
                $response = $this->decodeChunked($response);
            }
            if (isset($headers['Content-Encoding'])) {
                $response = zlib_decode($response);
            }
            unset($responseHeaders, $code);
        } elseif ('tcp' === strtolower($this->scheme)) {
            try {
                $responseData = Json::decode($response, 1);
                $response = $responseData['body'];
                $headers  = $responseData['headers'];
            } catch (\Exception $e) {
                // Do nothing
            }
        }

        return new Response($response, $statusCode, $headers);
    }

    /**
     * @param $str
     *
     * @return bool|string
     */
    public function decodeChunked($str)
    {
        // A string to hold the result
        $result = '';

        // Split input by CRLF
        $parts = explode("\r\n", $str);

        // These vars track the current chunk
        $chunkLen = 0;
        $thisChunk = '';

        // Loop the data
        while (($part = array_shift($parts)) !== null) {
            if ($chunkLen) {
                // Add the data to the string
                // Don't forget, the data might contain a literal CRLF
                $thisChunk .= $part . "\r\n";
                if (strlen($thisChunk) == $chunkLen) {
                    // Chunk is complete
                    $result .= $thisChunk;
                    $chunkLen = 0;
                    $thisChunk = '';
                } else if (strlen($thisChunk) == $chunkLen + 2) {
                    // Chunk is complete, remove trailing CRLF
                    $result .= substr($thisChunk, 0, -2);
                    $chunkLen = 0;
                    $thisChunk = '';
                } else if (strlen($thisChunk) > $chunkLen) {
                    // Data is malformed
                    return false;
                }
            } else {
                // If we are not in a chunk, get length of the new one
                if ($part === '') continue;
                if (!$chunkLen = hexdec($part)) break;
            }
        }

        // Return the decoded data of FALSE if it is incomplete
        return ($chunkLen) ? false : $result;
    }
}
