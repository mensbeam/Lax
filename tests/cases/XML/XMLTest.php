<?php
/** @license MIT
 * Copyright 2018 J. King
 * See LICENSE and AUTHORS files for details */

declare(strict_types=1);
namespace MensBeam\Lax\TestCase\XML;

/**
 * @covers MensBeam\Lax\Parser\Construct<extended>
 * @covers MensBeam\Lax\Parser\XML\Feed<extended>
 * @covers MensBeam\Lax\Parser\XML\Entry<extended>
 */
class XMLTest extends \MensBeam\Lax\TestCase\AbstractParserTestCase {
    /** @dataProvider provideXML */
    public function testParseAnXmlFeed(string $input, string $type, ?string $url, $exp): void {
        $p = new \MensBeam\Lax\Parser\XML\Feed($input, $type, $url);
        if ($exp instanceof \Exception) {
            $this->expectExceptionObject($exp);
            $p->parse();
        } else {
            $act = $p->parse();
            $this->assertEquals($exp, $act);
        }
    }

    public function provideXML(): iterable {
        return $this->provideParserTests(__DIR__."/*.yaml");
    }
}
