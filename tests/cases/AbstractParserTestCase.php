<?php
/** @license MIT
 * Copyright 2018 J. King
 * See LICENSE and AUTHORS files for details */

declare(strict_types=1);
namespace MensBeam\Lax\TestCase;

use MensBeam\Lax\Feed;
use MensBeam\Lax\Date;
use MensBeam\Lax\Text;
use MensBeam\Lax\Url;
use MensBeam\Lax\Entry;
use MensBeam\Lax\Metadata;
use MensBeam\Lax\Schedule;
use MensBeam\Lax\MimeType;
use MensBeam\Lax\Person\Person;
use MensBeam\Lax\Category\Category;
use MensBeam\Lax\Enclosure\Enclosure;
use MensBeam\Lax\Person\Collection as PersonCollection;
use MensBeam\Lax\Category\Collection as CategoryCollection;
use MensBeam\Lax\Enclosure\Collection as EnclosureCollection;
use MensBeam\Lax\Parser\Exception;
use Symfony\Component\Yaml\Yaml;
use Symfony\Component\Yaml\Parser as YamlParser;
use GuzzleHttp\Psr7\Response;

class AbstractParserTestCase extends \PHPUnit\Framework\TestCase {
    protected function provideParserTests(string $glob): iterable {
        foreach (new \GlobIterator($glob, \FilesystemIterator::CURRENT_AS_PATHNAME | \FilesystemIterator::KEY_AS_FILENAME) as $file => $path) {
            foreach ((new YamlParser)->parseFile($path, Yaml::PARSE_OBJECT_FOR_MAP) as $description => $test) {
                if (isset($test->exception)) {
                    $test->output = new Exception((string) $test->exception);
                } else {
                    $test->output = $this->makeFeed($test->output);
                }
                if (is_object($test->input)) {
                    assert((isset($test->input->head) && $test->input->head instanceof \stdClass) || (isset($test->input->body) && is_string($test->input->body)), "Input is not in a correct format");
                    $test->input = new Response($test->input->status ?? 200, (array) ($test->input->head ?? []), $test->input->body ?? null);
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
            } elseif ($k === "dateModified") {
                $f->$k = new Date($v, new \DateTimeZone("UTC"));
            } elseif ($k === "people") {
                $c = new PersonCollection;
                foreach ($v as $m) {
                    $c[] = $this->makePerson($m);
                }
                $f->$k = $c;
            } elseif ($k === "categories") {
                $c = new CategoryCollection;
                foreach ($v as $m) {
                    $o = new Category;
                    foreach ($m as $kk => $vv) {
                        $o->$kk = $vv;
                    }
                    $c[] = $o;
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
        if (is_iterable($enclosure->data ?? null)) {
            foreach ($enclosure->data as $k => $v) {
                $e[$k] = $this->makeEnclosure($v);
            }
            $e->preferred = $enclosure->preferred ?? null;
        } else {
            foreach ($enclosure as $k => $v) {
                if ($k === "url") {
                    $e->$k = $this->makeUrl($v);
                } elseif ($k === "title") {
                    $e->$k = $this->makeText($v);
                } elseif ($k === "type") {
                    $e->$k = MimeType::parseLoose($v);
                } else {
                    $e->$k = $v;
                }
            }
        }
        return $e;
    }

    private function makeMeta(\stdClass $meta): Metadata {
        $m = new Metadata;
        foreach ($meta as $k => $v) {
            if ($k === "url") {
                $m->$k = new Url($v);
            } elseif ($k === "type") {
                $m->$k = MimeType::parse($v);
            } elseif (in_array($k, ["date", "lastModified", "expires"])) {
                $m->$k = new Date($v);
            } elseif (in_array($k, ["age", "maxAge"])) {
                $m->$k = new \DateInterval($v);
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
