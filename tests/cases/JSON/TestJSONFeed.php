<?php
/** @license MIT
 * Copyright 2018 J. King
 * See LICENSE and AUTHORS files for details */

declare(strict_types=1);
namespace JKingWeb\Lax\TestCase\JSON;

use JKingWeb\Lax\Entry;
use JKingWeb\Lax\Parser\Exception;
use JKingWeb\Lax\Parser\JSON\Feed as Parser;
use JKingWeb\Lax\Feed;
use JKingWeb\Lax\Text;
use JKingWeb\Lax\Person\Person;
use JKingWeb\Lax\Person\Collection as PersonCollection;


/** @covers JKingWeb\Lax\Parser\JSON\Feed<extended> */
class TestJSON extends \PHPUnit\Framework\TestCase {
    /** @dataProvider provideJSONFeedVersion1 */
    public function testJSONFeedVersion1($input, string $type, $output): void {
        if (is_object($input)) {
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
            foreach (json_decode(file_get_contents($path)) as $index => $test) {
                if (isset($test->exception)) {
                    $test->output = new Exception((string) $test->exception);
                }
                yield "$file #$index: {$test->description}" => [
                    $test->input,
                    $test->type ?? "",
                    $test->output,
                ];
            }
        }
    }

    protected function makeFeed(\stdClass $output): Feed {
        $f = new Feed;
        foreach ($output as $k => $v) {
            if (in_array($k, ["title", "summary"])) {
                $f->$k = $this->makeText($v);
            } elseif ($k === "people") {
                $c = new PersonCollection;
                foreach ($v as $m) {
                    $p = new Person;
                    foreach ($m as $kk => $vv) {
                        $p->$kk = $vv;
                    }
                    $c[] = $p;
                }
                $f->$k = $c;
            } elseif ($k === "entries") {
                $c = [];
                foreach ($v as $m) {
                    $c[] = $this->makeEntry($m);
                }
                $f->$k = $c;
            } elseif (in_array($k, ["meta", "sched"])) {
                foreach ($v as $kk => $vv) {
                    $f->$k->$kk = $vv;
                }
            } else {
                $f->$k = $v;
            }
        }
        return $f;
    }

    protected function makeEntry(\stdClass $entry): Entry {
        $e = new Entry;
        foreach ($entry as $k => $v) {
            $e->$k = $v;
        }
        return $e;
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