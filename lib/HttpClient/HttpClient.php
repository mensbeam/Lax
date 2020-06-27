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
    public const ACCEPT_IMAGE = "image/*";
    public const ACCEPT_ICON = "image/svg+xml, image/png;q=0.9, image/*;q=0.1";

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

    /** Sends an HTTP request and returns a response
     * 
     * Redirects are followed up to the configured threshold. If credentials 
     * are supplied in the URL, these only apply to the original request; 
     * credentials are not sent to redirecr URLs, even for the same origin.
     */
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
                    if ($url = Url::fromString($loc[$b], (string) $request->getUri()->withUserInfo("", ""))) {
                        $request = $request->withUri($url);
                        if ($code === 303 && !in_array($request->getMethod(), ["GET", "HEAD"])) {
                            $request = $request->withMethod("GET");
                        }
                        continue 2;
                    }
                }
                return $response;
            }
        }
        throw new Exception("tooManyRedirects");
    }

    public function createRequest(string $method, $uri, array $headers = []): RequestInterface {
        $req = $this->requestFactory->createRequest($method, $uri);
        if (strlen($this->userAgent)) {
            $req = $req->withHeader("User-Agent", $this->userAgent);
        }
        foreach ($headers as $k => $v) {
            if (!is_array($v)) {
                $v = [$v];
            } else {
                $v = array_values($v);
            }
            foreach ($v as $kk => $vv) {
                if ($vv instanceof \DateTimeImmutable) {
                    $vv = $vv->setTimezone(new \DateTimeZone("UTC"))->format(\DateTimeInterface::RFC7231);
                } elseif ($vv instanceof \DateTime) {
                    $vv = clone $vv;
                    $vv->setTimezone(new \DateTimeZone("UTC"));
                    $v = $vv->format(\DateTimeInterface::RFC7231);
                }
                $m = $kk === 0 ? "withHeader" : "withAddedHeader";
                $req->$m($k, (string) $vv);
            }
        }
        return $req;
    }

    public function fetch($uri, string $accept = "*/*", string $etag = "", ?\DateTimeInterface $lastModified = null): ResponseInterface {
        $headers = ['Accept' => $accept];
        if (strlen($etag)) {
            $headers['ETag'] = $etag;
        }
        if ($lastModified) {
            $headers['Last-Modified'] = $lastModified;
        }
        return $this->sendRequest($this->createRequest("GET", $uri, $headers));
    }
}
