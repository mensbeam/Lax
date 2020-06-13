<?php
/** @license MIT
 * Copyright 2018 J. King
 * See LICENSE and AUTHORS files for details */

declare(strict_types=1);
namespace MensBeam\Lax\TestCase\Parser;

use GuzzleHttp\Psr7\Response;

/** @covers MensBeam\Lax\Parser\HTTP\Message */
class HTTPTest extends AbstractParserTestCase {
    /** @dataProvider provideHTTPMessages */
    public function testParseAnHttpMessage(Response $input, ?string $url, $exp): void {
        $p = new \MensBeam\Lax\Parser\HTTP\Message($input, $url);
        if ($exp instanceof \Exception) {
            $this->expectExceptionObject($exp);
            $p->parse();
        } else {
            $act = $p->parse();
            $this->assertEquals($exp->meta, $act);
        }
    }

    public function provideHTTPMessages(): iterable {
        foreach ($this->provideParserTests(__DIR__."/HTTP/*.yaml") as $k => $t) {
            array_splice($t, 1, 1);
            yield $k => $t;
        }
    }
}
