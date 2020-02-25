<?php
/** @license MIT
 * Copyright 2018 J. King et al.
 * See LICENSE and AUTHORS files for details */

declare(strict_types=1);
namespace JKingWeb\Lax;

abstract class Exception extends \Exception {
    const SYMBOLS = [
        // Parsing: 0x1100
        "notJSONType"                => [0x1111, "Document Content-Type is not either that of JSON Feed or generic JSON"],  
        "notJSON"                    => [0x1112, "Document is not valid JSON"],
        "notJSONFeed"                => [0x1113, "Document is not a JSON Feed document"],
        "unsupportedJSONFeedVersion" => [0x1114, "Document specifies an unsupported JSON Feed version"]
    ];

    public function __construct(string $symbol, \Exception $e = null) {
        $data = self::SYMBOLS[$symbol] ?? null;
        if (!$data) {
            throw new \Exception("Exception symbol \"$symbol\" does not exist");
        }
        list($code, $msg) = $data;
        parent::__construct($msg, $code, $e);
    }
}