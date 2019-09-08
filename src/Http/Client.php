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
        do {
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
