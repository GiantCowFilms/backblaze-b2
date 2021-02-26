<?php

namespace BackblazeB2\Http;

use BackblazeB2\ErrorHandler;
use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\Exception\GuzzleException;
use Psr\Http\Message\ResponseInterface;

/**
 * Client wrapper around Guzzle.
 */
class Client extends GuzzleClient
{
    public static $MAX_RETRY = 15;

    /**
     * Sends a response to the B2 API, automatically handling decoding JSON and errors.
     *
     * @param string $method
     * @param null   $uri
     * @param array  $options
     * @param bool   $asJson
     *
     * @throws GuzzleException
     *
     * @return mixed|ResponseInterface|string
     */
    public function request($method, $uri = null, array $options = [], $asJson = true)
    {
        $retries = 0;
        if (isset($options['body'])) {
            $resource = $options['body'];
        }
        do {
            if (isset($options['body'])) {
                // This doesn't need to be closed, it will be closed by Guzzle
                // in the call to parent::request
                $temp_resource = tmpfile();
                stream_copy_to_stream($resource,$temp_resource);
                rewind($temp_resource);
                $options['body'] = $temp_resource;
            }
            $response = parent::request($method, $uri, $options);
            $retries++;
        } while ($response->getStatusCode() > 499 && $response->getStatusCode() < 600 && $retries < self::$MAX_RETRY);

        if ($response->getStatusCode() !== 200) {
            ErrorHandler::handleErrorResponse($response);
        }

        if ($asJson) {
            return json_decode($response->getBody(), true);
        }

        return $response->getBody()->getContents();
    }
}
