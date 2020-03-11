<?php
/** @license MIT
 * Copyright 2018 J. King et al.
 * See LICENSE and AUTHORS files for details */

declare(strict_types=1);
namespace MensBeam\Lax;

abstract class Exception extends \Exception {
    public const SYMBOLS = [
        // Parsing: 0x1100
        "notJSONType"                => [0x1111, "Document Content-Type is not either that of JSON Feed or generic JSON"],
        "notJSON"                    => [0x1112, "Document is not valid JSON"],
        "notXML"                     => [0x1112, "Document is not well-formed XML"],
        "notJSONFeed"                => [0x1113, "Document is not a JSON Feed document"],
        "notXMLFeed"                 => [0x1113, "Document is not a newsfeed"],
    ];

    public function __construct(string $symbol, \Exception $e = null) {
        $data = self::SYMBOLS[$symbol] ?? null;
        if (!$data) {
            throw new \Exception("Exception symbol \"$symbol\" does not exist");
        }
        [$code, $msg] = $data;
        parent::__construct($msg, $code, $e);
    }
}
