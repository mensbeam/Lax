<?php
/** @license MIT
 * Copyright 2018 J. King
 * See LICENSE and AUTHORS files for details */

declare(strict_types=1);
namespace MensBeam\Lax\TestCase\Util;

use MensBeam\Lax\MimeType;
use MensBeam\Lax\Url;

/** @covers \MensBeam\Lax\MimeType */
class MimeTypeTest extends \PHPUnit\Framework\TestCase {
    /** @dataProvider provideLooseParsings */
    public function testParseATypeLoosely(?string $exp, string $input, ?Url $url): void {
        if ($exp) {
            $act = MimeType::parseLoose($input, $url);
            $this->assertInstanceOf(MimeType::class, $act);
            $this->assertSame($exp, (string) $exp);
        } else {
            $this->assertNull(MimeType::parseLoose($input, $url));
        }
    }

    public function provideLooseParsings(): iterable {
        return [
            'Sanity check' => ["text/html;charset=utf-8", 'TEXT/HTML  ;   CHARSET="utf-\8"; ook=', null],
        ];
    }
}
