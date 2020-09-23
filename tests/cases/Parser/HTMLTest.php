<?php
/** @license MIT
 * Copyright 2018 J. King
 * See LICENSE and AUTHORS files for details */

declare(strict_types=1);
namespace MensBeam\Lax\TestCase\Parser;

/**
 * @covers MensBeam\Lax\Parser\HTML\Feed<extended>
 */
class HTMLTest extends AbstractParserTestCase {
    /** @dataProvider provideHTML */
    public function testParseAnHtmlFeed(string $input, string $type, ?string $url, $exp): void {
        $p = new \MensBeam\Lax\Parser\HTML\Feed($input, $type, $url);
        if ($exp instanceof \Exception) {
            $this->expectExceptionObject($exp);
            $p->parse();
        } else {
            $act = $p->parse();
            $this->assertEquals($exp, $act);
        }
    }

    public function provideHTML(): iterable {
        return $this->provideParserTests(__DIR__."/HTML/*.yaml");
    }
}
