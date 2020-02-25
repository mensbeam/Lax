<?php
/** @license MIT
 * Copyright 2018 J. King
 * See LICENSE and AUTHORS files for details */

declare(strict_types=1);
namespace JKingWeb\Lax\TestCase\JSON;

use JKingWeb\Lax\Parser\JSON\Feed;

/** @covers JKingWeb\Lax\Parser\JSON\Feed<extended> */
class TestJSON extends \PHPUnit\Framework\TestCase {
    /** @dataProvider provideJSONFeedVersion1 */
    public function testJSONFeedVersion1($input, string $type, $output): void {
        if (is_array($input)) {
            $input = json_encode($input);
        } elseif (!is_string($input)) {
            throw new \Exception("Test input is invalid");
        }
        if ($output instanceof \Exception) {
            $this->expectExceptionObject($output);
            new Feed($input, $type);
        } else {
            $this->assertTrue(false);
        }
    }

    public function provideJSONFeedVersion1(): iterable {
        foreach (new \GlobIterator(__DIR__."/*.json", \FilesystemIterator::CURRENT_AS_PATHNAME | \FilesystemIterator::KEY_AS_FILENAME) as $file => $path) {
            foreach (json_decode(file_get_contents($path), true) as $index => $test) {
                if (isset($test['exception'])) {
                    $class = "\JKingWeb\Lax\Parser\Exception" . $test['exception'][0];
                    $test['output'] = new $class($test['exception'][1] ?? "");
                }
                yield "$file #$index: {$test['description']}" => [
                    $test['input'],
                    $test['type'] ?? "",
                    $test['output'],
                ];
            }
        }
    }
}