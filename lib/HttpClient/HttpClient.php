<?php
/** @license MIT
 * Copyright 2018 J. King et al.
 * See LICENSE and AUTHORS files for details */

declare(strict_types=1);
namespace MensBeam\Lax\HttpClient;

use MensBeam\Lax\Url;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Client\ClientInterface;

class HttpClient implements RequestFactoryInterface, ClientInterface {
    public const ACCEPT_FEED = "application/atom+xml, application/rss+xml;q=0.9, application/xml;q=0.8,  text/xml;q=0.8, */*;q=0.1";

    /** @var string $userAgent The User-Agent to identify as */
    public $userAgent = "";
    /** @var int $maxRedirects The number of redirects after which Lax should give up */
    public $maxRedirects = 10;

    /** @var \Psr\Http\Message\RequestFactoryInterface */
    protected $requestFactory = null;
    /** @var \Psr\Http\Client\ClientInterface */
    protected $client = null;
    
    public function __construct(ClientInterface $clientImplementation, RequestFactoryInterface $requestFactory) {
        $this->client = $clientImplementation;
        $this->requestFactory = $requestFactory;
    }

    public function sendRequest(RequestInterface $request): ResponseInterface {
        $stop = $this->maxRedirects;
        for ($a = 0; $a <= $stop; $a++) {
            $response = $this->client->sendRequest($request);
            $code = $response->getStatusCode();
            if ($code < 300 || $code === 304) {
                return $response;
            } elseif ($code >= 400) {
                throw new Exception("httpStatus".$code);
            } else {
                $loc = $response->getHeader("Location");
                for ($b = 0; $b < sizeof($loc); $b++) {
                    if ($url = Url::fromString($loc[$b], (string) $request->getUri())) {
                        $request = $request->withUri($url);
                        if ($code === 303 && !in_array($request->getMethod(), ["GET", "HEAD"])) {
                            $request = $request->withMethod("GET");
                            continue 2;
                        }
                    }
                }
                return $response;
            }
        }
        throw new Exception("tooManyRedirects");
    }

    public function createRequest(string $method, $uri): RequestInterface {
        return $this->requestFactory->createRequest($method, $uri);
    }
}
