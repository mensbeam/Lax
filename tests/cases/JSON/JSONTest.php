<?php
/** @license MIT
 * Copyright 2018 J. King
 * See LICENSE and AUTHORS files for details */

declare(strict_types=1);
namespace JKingWeb\Lax\TestCase\JSON;

/* Test format is as follows:

    Each test is a JSON object with the following keys:

    - `description`: a short human-readable description of the test
    - `base_url`: A base URL against which relative URLs should be resolved
    - `input`: The test input, as a string or directly as a JSON Feed structure
    - `output`: The result of the parsing upon success; described in more detail below
    - `exception`: The exception ID thrown upon failure

    The 'description' and 'input' keys along with either 'output' or 'exception'
    are required for all tests.

    The test output is necessarily mangled due to the limits of JSON:

    - Any field which should be a URL should be written as a string, which
      will be transformed accordingly
    - Any collections should be represented as arrays of objects, which will
      all be transformed accordingly
    - Rich text can either be supplied as a string (which will yield a Text object 
      with plain-text content) or as an object with any of the properties of the
      Text class listed

    The transformations as performed by the `makeFeed` and `makeEntry` methods 
    of the abstract test case.

*/

use JKingWeb\Lax\Date;
use JKingWeb\Lax\Feed;
use JKingWeb\Lax\Entry;
use JKingWeb\Lax\Text;
use JKingWeb\Lax\Url;
use JKingWeb\Lax\Parser\Exception;
use JKingWeb\Lax\Parser\JSON\Feed as Parser;
use JKingWeb\Lax\Person\Person;
use JKingWeb\Lax\Category\Category;
use JKingWeb\Lax\Enclosure\Enclosure;
use JKingWeb\Lax\Person\Collection as PersonCollection;
use JKingWeb\Lax\Category\Collection as CategoryCollection;
use JKingWeb\Lax\Enclosure\Collection as EnclosureCollection;

/** 
 * @covers JKingWeb\Lax\Parser\JSON\Feed<extended>
 * @covers JKingWeb\Lax\Parser\JSON\Entry<extended>
 */
class JSONTest extends \PHPUnit\Framework\TestCase {
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
                    $c[] = $this->makePerson($m);
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
            } elseif (in_array($k, ["url", "link", "icon", "image"])) {
                $f->$k = new Url($v);
            } else {
                $f->$k = $v;
            }
        }
        return $f;
    }

    protected function makeEntry(\stdClass $entry): Entry {
        $e = new Entry;
        foreach ($entry as $k => $v) {
            if (in_array($k, ["link", "relatedLink", "banner"])) {
                $e->$k = new Url($v);
            } elseif (in_array($k, ["dateCreated", "dateModified"])) {
                $e->$k = new Date($v, new \DateTimeZone("UTC"));
            } elseif (in_array($k, ["title", "summary", "content"])) {
                $e->$k = $this->makeText($v);
            } elseif ($k === "people") {
                $c = new PersonCollection;
                foreach ($v as $m) {
                    $c[] = $this->makePerson($m);
                }
                $e->$k = $c;
            } elseif ($k === "enclosures") {
                $c = new EnclosureCollection;
                foreach ($v as $m) {
                    $c[] = $this->makeEnclosure($m);
                }
                $e->$k = $c;
            } elseif ($k === "categories") {
                $c = new CategoryCollection;
                foreach ($v as $m) {
                    $o = new Category;
                    foreach ($m as $kk => $vv) {
                        $o->$kk = $vv;
                    }
                    $c[] = $o;
                }
                $e->$k = $c;
            } else {
                $e->$k = $v;
            }
        }
        return $e;
    }

    protected function makeText($data): Text {
        if (is_string($data)) {
            return new Text($data);
        }
        $out = new Text;
        foreach ($data as $k => $v) {
            $out->$k = $v;
        }
        return $out;
    }

    protected function makePerson(\stdClass $person): Person {
        $p = new Person;
        foreach ($person as $k => $v) {
            if (in_array($k, ["url", "avatar"])) {
                $p->$k = new Url($v);
            } else {
                $p->$k = $v;
            }
        }
        return $p;
    }

    protected function makeEnclosure(\stdClass $enclosure): Enclosure {
        $e = new Enclosure;
        foreach ($enclosure as $k => $v) {
            if ($k === "urli") {
                $e->$k = new Url($v);
            } else {
                $e->$k = $v;
            }
        }
        return $e;
    }
}