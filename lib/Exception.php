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
        'httpStatus400'             => [0x1201, "Client request was not acceptable to the server (code 400)"],
        'httpStatus401'             => [0x1202, "Supplied credentials are insufficient to access the resource (code 401)"],
        'httpStatus402'             => [0x1201, "Client request was not acceptable to the server (code 402)"],
        'httpStatus403'             => [0x1212, "Access to the resource is forbidden (code 403)"],
        'httpStatus404'             => [0x1203, "The requested resource was not found on the server (code 404)"],
        'httpStatus405'             => [0x1201, "Client request was not acceptable to the server (code 405)"],
        'httpStatus406'             => [0x1201, "Client request was not acceptable to the server (code 406)"],
        'httpStatus407'             => [0x1201, "Client request was not acceptable to the server (code 407)"],
        'httpStatus408'             => [0x1201, "Client request was not acceptable to the server (code 408)"],
        'httpStatus409'             => [0x1201, "Client request was not acceptable to the server (code 409)"],
        'httpStatus410'             => [0x1203, "The requested resource was not found on the server (code 410)"],
        'httpStatus411'             => [0x1201, "Client request was not acceptable to the server (code 411)"],
        'httpStatus412'             => [0x1201, "Client request was not acceptable to the server (code 412)"],
        'httpStatus413'             => [0x1201, "Client request was not acceptable to the server (code 413)"],
        'httpStatus414'             => [0x1201, "Client request was not acceptable to the server (code 414)"],
        'httpStatus415'             => [0x1201, "Client request was not acceptable to the server (code 415)"],
        'httpStatus416'             => [0x1201, "Client request was not acceptable to the server (code 416)"],
        'httpStatus417'             => [0x1201, "Client request was not acceptable to the server (code 417)"],
        'httpStatus421'             => [0x1201, "Client request was not acceptable to the server (code 421)"],
        'httpStatus422'             => [0x1201, "Client request was not acceptable to the server (code 422)"],
        'httpStatus423'             => [0x1201, "Client request was not acceptable to the server (code 423)"],
        'httpStatus424'             => [0x1201, "Client request was not acceptable to the server (code 424)"],
        'httpStatus425'             => [0x1201, "Client request was not acceptable to the server (code 425)"],
        'httpStatus426'             => [0x1201, "Client request was not acceptable to the server (code 426)"],
        'httpStatus428'             => [0x1201, "Client request was not acceptable to the server (code 428)"],
        'httpStatus429'             => [0x1201, "Client request was not acceptable to the server (code 429)"],
        'httpStatus431'             => [0x1201, "Client request was not acceptable to the server (code 431)"],
        'httpStatus451'             => [0x1212, "Access to the resource is forbidden (code 451)"],
        'httpStatus500'             => [0x1211, "The server reported an error (code 500)"],
        'httpStatus501'             => [0x1211, "The server reported an error (code 501)"],
        'httpStatus502'             => [0x1211, "The server reported an error (code 502)"],
        'httpStatus503'             => [0x1211, "The server reported an error (code 503)"],
        'httpStatus504'             => [0x1211, "The server reported an error (code 504)"],
        'httpStatus505'             => [0x1211, "The server reported an error (code 505)"],
        'httpStatus506'             => [0x1211, "The server reported an error (code 506)"],
        'httpStatus507'             => [0x1211, "The server reported an error (code 507)"],
        'httpStatus508'             => [0x1211, "The server reported an error (code 508)"],
        'httpStatus510'             => [0x1211, "The server reported an error (code 510)"],
        'httpStatus511'             => [0x1211, "The server reported an error (code 511)"],
        'tooManyRedirects'          => [0x1204, "The configured number of redirects was exceeded while trying to access the resource"],
    ];

    public function __construct(string $symbol, \Exception $e = null) {
        $data = self::SYMBOLS[$symbol] ?? null;
        assert(is_array($data), "Error symbol is not defined");
        [$code, $msg] = $data;
        parent::__construct($msg, $code, $e);
    }
}
