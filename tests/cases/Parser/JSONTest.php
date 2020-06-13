<?php
/** @license MIT
 * Copyright 2018 J. King
 * See LICENSE and AUTHORS files for details */

declare(strict_types=1);
namespace MensBeam\Lax\TestCase\Parser;

/**
 * @covers MensBeam\Lax\Parser\JSON\Feed<extended>
 * @covers MensBeam\Lax\Parser\JSON\Entry<extended>
 */
class JSONTest extends AbstractParserTestCase {
    /** @dataProvider provideJSONFeed */
    public function testParseAJsonFeed(string $input, string $type, ?string $url, $exp): void {
        $p = new \MensBeam\Lax\Parser\JSON\Feed($input, $type, $url);
        if ($exp instanceof \Exception) {
            $this->expectExceptionObject($exp);
            $p->parse();
        } else {
            $act = $p->parse();
            $this->assertEquals($exp, $act);
        }
    }

    public function provideJSONFeed(): iterable {
        return $this->provideParserTests(__DIR__."/JSON/*.yaml");
    }
}
