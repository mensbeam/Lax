<?php
/** @license MIT
 * Copyright 2018 J. King
 * See LICENSE and AUTHORS files for details */

declare(strict_types=1);
namespace MensBeam\Lax\TestCase\Parser;

use MensBeam\Lax\Parser\Parser;
use MensBeam\Lax\Parser\Exception;
use MensBeam\Lax\Parser\XML\Feed as XMLParser;
use MensBeam\Lax\Parser\JSON\Feed as JSONParser;

/** @covers MensBeam\Lax\Parser\Parser */
class ParserTest extends \PHPUnit\Framework\TestCase {
    /** @dataProvider provideContentTypes */
    public function testFindCorrectParserForContentType(string $in, $exp): void {
        if ($exp instanceof \Throwable) {
            $this->expectExceptionObject($exp);
            Parser::findParserForType($in);
        } else {
            $this->assertSame($exp, Parser::findParserForType($in));
        }
    }

    public function provideContentTypes(): iterable {
        return [
            ["application/feed+json",    JSONParser::class],
            ["application/json",         JSONParser::class],
            ["text/json",                JSONParser::class],
            ["application/atom+xml",     XMLParser::class],
            ["application/rss+xml",      XMLParser::class],
            ["application/rdf+xml",      XMLParser::class],
            ["application/xml",          XMLParser::class],
            ["text/xml",                 XMLParser::class],
            ["text/plain",               new Exception("notSupportedType")],
            ["not a type",               null],
            ["text/json; charset=utf-8", JSONParser::class],
        ];
    }

    /** @dataProvider provideDetectableContent */
    public function testFindCorrectTypeForContent(string $in, string $exp): void {
        $this->assertSame($exp, Parser::findTypeForContent($in));
    }

    public function provideDetectableContent(): iterable {
        return [
            ['{""}',                             "application/json"],
            [" \n  {\"v",                        "application/json"],
            ["<?xml",                            "application/xml"],
            [" \n <?xml",                        "application/xml"],
            ["<!DOCTYPE html>",                  "text/html"],
            ["<!DOCTYPE html ",                  "text/html"],
            [" \n <!DOCTYPE html>",              "text/html"],
            [" \n <!DOCTYPE html\n",             "text/html"],
            [" <!-- --> <!-- oops -->\n <rss>",  "application/rss+xml"],
            [" <!--> <!-- oops -->\n <rss>",     "application/rss+xml"],
            ["<feed ",                           "application/atom+xml"],
            ["<atom:feed ",                      "application/atom+xml"],
            ["<RDF ",                            "application/rdf+xml"],
            ["<rdf:RDF ",                        "application/rdf+xml"],
            ["<opml>",                           "application/xml"],
            ["plain text",                       "application/octet-stream"],
        ];
    }
}