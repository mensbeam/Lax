<?php
/** @license MIT
 * Copyright 2018 J. King
 * See LICENSE and AUTHORS files for details */

declare(strict_types=1);
namespace MensBeam\Lax\TestCase;

/* Test format is as follows:

    Each test is a YAML map with the following keys:

    - `input`: The test input as a string
    - `output`: The result of the parsing upon success; described in more detail below
    - `exception`: The exception ID thrown upon failure
    - `type`: An HTTP Content-Type (with or without parameters) for the document
    - `doc_url`: A fictitious URL where a newsfeed might be located, used for relative URL resolution

    The 'input' key along with either 'output' or 'exception' are required for all tests.

    The test output is necessarily mangled due to the limits of YAML:

    - Any field which should be an absolute URL should be written as a string,
      which will be transformed accordingly. Relative URLs should be represented
      as a sequence with the relative part first, followed by the base that should
      be applied to it
    - Any collections should be represented as sequences of maps, which will
      all be transformed accordingly
    - Rich text can either be supplied as a string (which will yield a Text object
      with plain-text content) or as a map with any of the properties of the
      Text class listed

    The transformations as performed by the `makeFeed` and `makeEntry` methods
    of the abstract test case.

*/

use MensBeam\Lax\Feed;
use MensBeam\Lax\Date;
use MensBeam\Lax\Text;
use MensBeam\Lax\Url;
use MensBeam\Lax\Entry;
use MensBeam\Lax\Metadata;
use MensBeam\Lax\Schedule;
use MensBeam\Lax\Person\Person;
use MensBeam\Lax\Category\Category;
use MensBeam\Lax\Enclosure\Enclosure;
use MensBeam\Lax\Person\Collection as PersonCollection;
use MensBeam\Lax\Category\Collection as CategoryCollection;
use MensBeam\Lax\Enclosure\Collection as EnclosureCollection;
use MensBeam\Lax\Parser\Exception;
use Symfony\Component\Yaml\Yaml;
use Symfony\Component\Yaml\Parser as YamlParser;

class AbstractParserTestCase extends \PHPUnit\Framework\TestCase {
    protected function provideParserTests(string $glob): iterable {
        foreach (new \GlobIterator($glob, \FilesystemIterator::CURRENT_AS_PATHNAME | \FilesystemIterator::KEY_AS_FILENAME) as $file => $path) {
            foreach ((new YamlParser)->parseFile($path, Yaml::PARSE_OBJECT_FOR_MAP) as $description => $test) {
                if (isset($test->exception)) {
                    $test->output = new Exception((string) $test->exception);
                } else {
                    $test->output = $this->makeFeed($test->output);
                }
                yield "$file: {$description}" => [
                    $test->input,
                    $test->type ?? "",
                    $test->doc_url ?? null,
                    $test->output,
                ];
            }
        }
    }

    private function makeFeed(\stdClass $output): Feed {
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
            } elseif ($k === "meta") {
                $f->$k = $this->makeMeta($v);
            } elseif ($k === "sched") {
                $f->$k = $this->makeSched($v);
            } elseif (in_array($k, ["url", "link", "icon", "image"])) {
                $f->$k = $this->makeUrl($v);
            } else {
                $f->$k = $v;
            }
        }
        return $f;
    }

    private function makeEntry(\stdClass $entry): Entry {
        $e = new Entry;
        foreach ($entry as $k => $v) {
            if (in_array($k, ["link", "relatedLink", "banner"])) {
                $e->$k = $this->makeUrl($v);
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

    private function makeText($data): Text {
        if (is_string($data)) {
            return new Text($data);
        }
        $out = new Text;
        foreach ($data as $k => $v) {
            $out->$k = $v;
        }
        return $out;
    }

    private function makePerson(\stdClass $person): Person {
        $p = new Person;
        foreach ($person as $k => $v) {
            if (in_array($k, ["url", "avatar"])) {
                $p->$k = $this->makeUrl($v);
            } else {
                $p->$k = $v;
            }
        }
        return $p;
    }

    private function makeEnclosure(\stdClass $enclosure): Enclosure {
        $e = new Enclosure;
        foreach ($enclosure as $k => $v) {
            if ($k === "urli") {
                $e->$k = $this->makeUrl($v);
            } else {
                $e->$k = $v;
            }
        }
        return $e;
    }

    private function makeMeta(\stdClass $meta): Metadata {
        $m = new Metadata;
        foreach ($meta as $k => $v) {
            if ($k === 'url') {
                $m->$k = new Url($v);
            } else {
                $m->$k = $v;
            }
        }
        return $m;
    }

    private function makeSched(\stdClass $sched): Schedule {
        $s = new Schedule;
        foreach ($sched as $k => $v) {
            if ($k === 'base') {
                $s->$k = new Date($v);
            } elseif ($k === 'interval') {
                $s->$k = new \DateInterval($v);
            } else {
                $s->$k = $v;
            }
        }
        return $s;
    }

    private function makeUrl($url): ?Url {
        if (is_array($url)) {
            return new Url($url[0] ?? "", $url[1] ?? null);
        } else {
            return new Url($url);
        }
    }
}
