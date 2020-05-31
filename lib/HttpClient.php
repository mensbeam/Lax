<?php
/** @license MIT
 * Copyright 2018 J. King et al.
 * See LICENSE and AUTHORS files for details */

declare(strict_types=1);
namespace MensBeam\Lax;

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
}
