<?php

namespace Infusionsoft\Http;

use fXmlRpc\Transport\HttpAdapterTransport;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\BadResponseException;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\MessageFormatter;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Middleware;
use Psr\Log\LoggerInterface;

class GuzzleHttpClient implements ClientInterface
{

    public $client;
    public $debug;
    public $httpLogAdapter;

    public function __construct($debug, LoggerInterface $httpLogAdapter)
    {
        $this->debug          = $debug;
        $this->httpLogAdapter = $httpLogAdapter;

        $config = ['timeout' => 60];
        if ($this->debug) {
            $config['handler'] = HandlerStack::create();
            $config['handler']->push(
                Middleware::log($this->httpLogAdapter, new MessageFormatter(MessageFormatter::DEBUG))
            );
        }

        $this->client = new Client($config);
    }

    /**
     * @return \fXmlRpc\Transport\TransportInterface
     */
    public function getXmlRpcTransport()
    {

        $adapter = new \Http\Adapter\Guzzle7\Client($this->client);

        return new HttpAdapterTransport(new \Http\Message\MessageFactory\DiactorosMessageFactory(),
            $adapter);
    }

    /**
     * Sends a request to the given URI and returns the raw response.
     *
     * @param string $method
     * @param string $uri
     * @param array $options
     *
     * @return mixed
     * @throws HttpException
     */
    public function request($method, $uri, array $options)
    {
        if ( ! isset($options['headers'])) {
            $options['headers'] = [];
        }

        if ( ! isset($options['body'])) {
            $options['body'] = null;
        }

        try {
            $request  = new Request($method, $uri, $options['headers'], $options['body']);
            $response = $this->client->send($request);

            return $response->getBody();
        } catch (BadResponseException $e) {
            throw new HttpException($e->getMessage(), $e->getCode(), $e);
        }
    }
}
