<?php
/** @license MIT
 * Copyright 2018 J. King et al.
 * See LICENSE and AUTHORS files for details */

declare(strict_types=1);
namespace MensBeam\Lax\HttpClient;

use MensBeam\Lax\Exception as BaseException;

class Exception extends BaseException {
    public function __construct(string $symbol, \Exception $e = null) {
        if (preg_match("/^httpStatus(\d+)$/", $symbol, $match)) {
            $code = (int) $match[1];
            assert($code >= 400, "HTTP status codes under 400 should not produce exceptions");
            if (($code < 500 && $code > 431 && $code !== 451) || in_array($code, [418, 419, 420, 427, 430])) {
                // unassigned 4xx code are changed to 400
                $symbol = "httpStatus400";
            } elseif ($code > 511 || $code === 509) {
                // unassigned 5xx codes and anything 600 or above is changed to 500
                $symbol = "httpStatus500";
            } else {
                $symbol = "httpStatus".$code;
            }
        }
        parent::__construct($symbol, $e);
    }
}
