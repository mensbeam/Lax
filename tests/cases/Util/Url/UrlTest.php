<?php
/** @license MIT
 * Copyright 2018 J. King
 * See LICENSE and AUTHORS files for details */

declare(strict_types=1);
namespace MensBeam\Lax\TestCase\Util\Url;

use MensBeam\Lax\Url;

/** @covers MensBeam\Lax\Url<extended> */
class UrlTest extends Psr7TestCase {
    protected function createUri($uri = '') {
        return new Url($uri);
    }

    /** @dataProvider provideStandardParsingTests */
    public function xtestParsePerWhatwgRules(string $input, string $base, ?string $exp): void {
        $act = Url::fromString($input, $base);
        if (is_null($exp)) {
            $this->assertNull($act);
        } else {
            $this->assertSame($exp, (string) $act);
        }
    }

    public function provideStandardParsingTests(): iterable {
        $indexOffset = 0;
        $description = "";
        foreach (json_decode(file_get_contents(__DIR__."/urltestdata.json")) as $index => $test) {
            if (is_string($test)) {
                // the array member is a description of the next member
                // the index offset should be decremented, the description stored, and this entry skipped
                $indexOffset--;
                $description = $test;
                continue;
            } else {
                $index += $indexOffset;
                $description = $description ? ": $description" : "";
                yield "#$index$description" => [$test->input, $test->base, $test->href ?? null];
                $description = null;
            }
        }
    }
}
