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
            $this->assertSame($exp, (string) $act);
        } else {
            $this->assertNull(MimeType::parseLoose($input, $url));
        }
    }

    public function provideLooseParsings(): iterable {
        return [
            'Sanity check'                => ["text/html;charset=utf-8", 'TEXT/HTML  ;   CHARSET="utf-\8"; ook=', null],
            'Major type only'             => ["text",                    " text\t",                               null],
            'Document medium'             => ["text",                    " document\t",                           null],
            'Executable medium'           => ["application",             " EXECUTABLE\t",                         null],
            'Arbitrary major type'        => ["ook",                     " OOK\t",                                null],
            'Major type with parameters'  => ["ook",                     " OOK; charset=utf-8",                   null],
            'Bogus major type'            => [null,                      " OOK EEK",                              null],
            'Guessed type'                => ["text/plain",              "",                                      new Url("http://example.com/blah.txt?q=abc")],
            'Medium preferrable to guess' => ["text",                    "text",                                  new Url("http://example.com/blah.txt?q=abc")],
            'Data URI with type'          => ["text/css",                "",                                      new Url("data:text/css;base64,")],
            'Data URI without type'       => ["text/plain",              "",                                      new Url("data:,")],
            'Data URI with bogus type'    => ["text/plain",              "",                                      new Url("data:/,")],
        ];
    }

    /** @dataProvider provideAtomParsings */
    public function testParseAnAtomType(string $input, ?string $exp): void {
        $act = MimeType::parseAtom($input);
        $this->assertInstanceOf(MimeType::class, $act);
        $this->assertSame($exp ?? "unknown/unknown", (string) $act);
    }

    public function provideAtomParsings(): iterable {
        return [
            'Sanity check'   => ['TEXT/HTML; CHARSET="utf-\8"; ook=', "text/html;charset=utf-8"],
            'Plain text'     => ['text',                              "text/plain"],
            'Plain text UC'  => ['TEXT',                              "text/plain"],
            'HTML'           => ['html',                              "text/html"],
            'HTML UC'        => ['HTML',                              "text/html"],
            'XHTML'          => ['xhtml',                             "application/xhtml+xml"],
            'XHTML UC'       => ['XHTML',                             "application/xhtml+xml"],
            'Blank type'     => ['',                                  "text/plain"],
            'Bogus type'     => ['image',                             null],
            'arbitrary type' => ['FONT/TTF',                          "font/ttf"],
        ];
    }

    public function testManipulateAnIncompleteType():void {
        $t = MimeType::parseLoose("text; charset=utf-8");
        $this->assertSame("text", $t->type);
        $this->assertSame("", $t->subtype);
        $this->assertSame("text", $t->essence);
        $this->assertSame([], $t->params);
        $t = MimeType::parseLoose("image");
        $this->assertTrue($t->isImage);
    }
}
