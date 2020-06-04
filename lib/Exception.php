<?php
/** @license MIT
 * Copyright 2018 J. King et al.
 * See LICENSE and AUTHORS files for details */

declare(strict_types=1);
namespace MensBeam\Lax;

abstract class Exception extends \Exception {
    public const SYMBOLS = [
        // Parsing: 0x1100
        'notSupportedType'          => [0x1101, "Document type is not supported"],
        'notJSONType'               => [0x1111, "Document Content-Type is not either that of JSON Feed or generic JSON"],
        'notXMLType'                => [0x1111, "Document Content-Type is not that of an XML newsfeed"],
        'notJSON'                   => [0x1112, "Document is not valid JSON"],
        'notXML'                    => [0x1112, "Document is not well-formed XML"],
        'notJSONFeed'               => [0x1113, "Document is not a JSON Feed document"],
        'notXMLFeed'                => [0x1113, "Document is not a newsfeed"],
        // Fetching: 0x1200
        'badRequest'                => [0x1201, "Client request was not accaptable to the server"],
        'notFound'                  => [0x1202, "Resource was not found on server"],
        'notAuthorized'             => [0x1203, "Supplied credentials are insufficient to access the resource"],
        'tooManyRedirects'          => [0x1204, "The configured number of redirects was exceeded"],
        'forbidden'                 => [0x1211, "Access to the resource is forbidden"],
        'serverError'               => [0x1212, "The server returned an error"],
        'networkError'              => [0x1213, "A transport error occurred"],
    ];

    public function __construct(string $symbol, \Exception $e = null) {
        $data = self::SYMBOLS[$symbol] ?? null;
        assert(is_array($data));
        [$code, $msg] = $data;
        parent::__construct($msg, $code, $e);
    }
}
