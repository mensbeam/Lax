<?php
/** @license MIT
 * Copyright 2018 J. King
 * See LICENSE and AUTHORS files for details */

declare(strict_types=1);
namespace MensBeam\Lax\TestCase;

use MensBeam\Lax\HttpClient\Exception;

/** 
 * @covers MensBeam\Lax\HttpClient\HttpClient
 * @covers MensBeam\Lax\HttpClient\Exception
 */
class ParserTest extends \PHPUnit\Framework\TestCase {
    /** @dataProvider provideCodes */
    public function testCreateExceptions(string $symbol, int $exp): void {
        $exp = new Exception("httpStatus".$exp);
        $act = new Exception($symbol);
        $this->expectExceptionObject($exp);
        throw $act;
    }

    public function provideCodes(): iterable {
        // see https://www.iana.org/assignments/http-status-codes/http-status-codes.xhtml
        $exceptions = [418, 419, 420, 427, 430, 451, 509];
        $cutoff = [400 => 432, 500 => 512];
        for ($a = 400; $a < $cutoff[400]; $a++) {
            $out = $a;
            if (in_array($a, $exceptions)) {
                $out = 400;
            }
            yield "httpStatus".$a => ["httpStatus".$a, $out];
        }
        for ($a = $cutoff[400]; $a < 500; $a++) {
            $out = 400;
            if (in_array($a, $exceptions)) {
                $out = $a;
            }
            yield "httpStatus".$a => ["httpStatus".$a, $out];
        }
        for ($a = 500; $a < $cutoff[500]; $a++) {
            $out = $a;
            if (in_array($a, $exceptions)) {
                $out = 500;
            }
            yield "httpStatus".$a => ["httpStatus".$a, $out];
        }
        for ($a = $cutoff[500]; $a < 600; $a++) {
            $out = 500;
            if (in_array($a, $exceptions)) {
                $out = $a;
            }
            yield "httpStatus".$a => ["httpStatus".$a, $out];
        }
        foreach ([600, 999, 1000, 1401] as $a) {
            yield "httpStatus".$a => ["httpStatus".$a, 500];
        }
        yield "httpStatus000401" => ["httpStatus000401", 401];
    }
}