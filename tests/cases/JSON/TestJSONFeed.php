<?php
/** @license MIT
 * Copyright 2018 J. King
 * See LICENSE and AUTHORS files for details */

declare(strict_types=1);
namespace JKingWeb\Lax\TestCase\JSON;

use JKingWeb\Lax\Parser\Exception;
use JKingWeb\Lax\Feed;
use JKingWeb\Lax\Parser\JSON\Feed as Parser;
use JKingWeb\Lax\Text;

/** @covers JKingWeb\Lax\Parser\JSON\Feed<extended> */
class TestJSON extends \PHPUnit\Framework\TestCase {
    /** @dataProvider provideJSONFeedVersion1 */
    public function testJSONFeedVersion1($input, string $type, $output): void {
        if (is_array($input)) {
            $input = json_encode($input);
        } elseif (!is_string($input)) {
            throw new \Exception("Test input is invalid");
        }
        $p = new Parser($input, $type);
        if ($output instanceof \Exception) {
            $this->expectExceptionObject($output);
            $p->parse(new Feed);
        } else {
            $act = $p->parse(new Feed);
            $exp = $this->makeFeed($output);
            $this->assertEquals($exp, $act);
        }
    }

    public function provideJSONFeedVersion1(): iterable {
        foreach (new \GlobIterator(__DIR__."/*.json", \FilesystemIterator::CURRENT_AS_PATHNAME | \FilesystemIterator::KEY_AS_FILENAME) as $file => $path) {
            foreach (json_decode(file_get_contents($path), true) as $index => $test) {
                if (isset($test['exception'])) {
                    $test['output'] = new Exception((string) $test['exception']);
                }
                yield "$file #$index: {$test['description']}" => [
                    $test['input'],
                    $test['type'] ?? "",
                    $test['output'],
                ];
            }
        }
    }

    protected function makeFeed(array $output): Feed {
        $f = new Feed;
        foreach ($output as $k => $v) {
            if (in_array($k, ["title", "summary"])) {
                $f->$k = $this->makeText($v);
            } else {
                $f->$k = $v;
            }
        }
        return $f;
    }

    protected function makeText($data): Text {
        if (is_string($data)) {
            return new Text($data);
        }
        $out = new Text;
        foreach(["plain", "html", "xhtml", "loose"] as $k) {
            if (isset($data[$k])) {
                $out->$k = $data[$k];
            }
        }
        return $out;
    }
}