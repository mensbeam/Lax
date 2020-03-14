<?php
/** @license MIT
 * Copyright 2018 J. King et al.
 * See LICENSE and AUTHORS files for details */

declare(strict_types=1);
namespace MensBeam\Lax\Parser\JSON;

use MensBeam\Lax\Feed as FeedStruct;
use MensBeam\Lax\Entry as EntryStruct;
use MensBeam\Lax\Person\Collection as PersonCollection;
use MensBeam\Lax\Category\Collection as CategoryCollection;
use MensBeam\Lax\Enclosure\Collection as EnclosureCollection;
use MensBeam\Lax\Category\Category;
use MensBeam\Lax\Date;
use MensBeam\Lax\Enclosure\Enclosure;
use MensBeam\Lax\Text;
use MensBeam\Lax\Url;

class Entry implements \MensBeam\Lax\Parser\Entry {
    use Construct;

    protected $url;
    /** @var \MensBeam\Lax\Feed */
    protected $feed;
    /** @var \Mimey\MimeTypes */
    protected $mime;

    public function __construct(\stdClass $data, FeedStruct $feed) {
        $this->data = $data;
        $this->feed = $feed;
        $this->url = $feed->meta->url ? (string) $feed->meta->url : null;
    }

    protected function init(EntryStruct $entry): EntryStruct {
        return $entry;
    }

    public function parse(EntryStruct $entry = null): EntryStruct {
        $entry = $this->init($entry ?? new EntryStruct);
        $entry->lang = $this->getLang();
        $entry->id = $this->getId();
        $entry->link = $this->getLink();
        $entry->relatedLink = $this->getRelatedLink();
        $entry->title = $this->getTitle();
        $entry->dateModified = $this->getDateModified();
        $entry->dateCreated = $this->getDateCreated();
        $entry->content = $this->getContent();
        $entry->summary = $this->getSummary();
        $entry->banner = $this->getBanner();
        $entry->people = $this->getPeople();
        $entry->categories = $this->getCategories();
        $entry->enclosures = $this->getEnclosures();
        return $entry;
    }

    public function getId(): ?string {
        $id = $this->fetchMember("id", "str") ?? $this->fetchMember("id", "int") ?? $this->fetchMember("id", "float");
        if (is_null($id)) {
            return null;
        } elseif (is_float($id)) {
            if (!fmod($id, 1.0)) {
                return (string) (int) $id; // @codeCoverageIgnore
            } else {
                $id = preg_split("/E\+?/i", str_replace(localeconv()['decimal_point'], ".", (string) $id));
                if (sizeof($id) === 1) {
                    return $id[0];
                } else {
                    $exp = (int) $id[1];
                    $mul = $exp > -1;
                    $exp = abs($exp);
                    [$int, $dec] = explode(".", $id[0]);
                    $dec = strlen($dec) ? str_split($dec, 1) : [];
                    $int = str_split($int, 1);
                    if ($int[0] === "-") {
                        $neg = true;
                        array_shift($int);
                    } else {
                        $neg = false;
                    }
                    while ($exp-- > 0) {
                        if ($mul && $dec) {
                            $int[] = array_shift($dec); // @codeCoverageIgnore
                        } elseif ($mul) {
                            $int[] = "0"; // @codeCoverageIgnore
                        } elseif (!$mul && $int) {
                            array_unshift($dec, array_pop($int));
                        } else {
                            array_unshift($dec, "0");
                        }
                    }
                    return ($neg ? "-" : "").($int ? implode("", $int) : "0").($dec ? (".".rtrim(implode("", $dec), "0")) : "");
                }
            }
        } else {
            return (string) $id;
        }
    }

    public function getLang(): ?string {
        return $this->fetchMember("language", "str") ?? $this->feed->lang;
    }

    public function getPeople(): PersonCollection {
        return $this->getAuthorsV1() ?? $this->getAuthorV1() ?? $this->feed->people ?? new PersonCollection;
    }

    public function getDateModified(): ?Date {
        return $this->fetchDate("date_modified");
    }

    public function getDateCreated(): ?Date {
        return $this->fetchDate("date_published");
    }

    public function getLink(): ?Url {
        return $this->fetchUrl("url");
    }

    public function getRelatedLink(): ?Url {
        return $this->fetchUrl("external_url");
    }

    public function getTitle(): ?Text {
        return $this->fetchText("title");
    }

    public function getSummary(): ?Text {
        return $this->fetchText("summary");
    }

    public function getContent(): ?Text {
        $out = $this->fetchText("content_text");
        $html = $this->fetchMember("content_html", "str");
        if (strlen($html ?? "")) {
            $out = $out ?? new Text;
            $out->html = $html;
            $out->htmlBase = $this->feed->meta->url;
        }
        return $out;
    }

    public function getBanner(): ?Url {
        return $this->fetchUrl("banner_image");
    }

    public function getEnclosures(): EnclosureCollection {
        $out = new EnclosureCollection;
        // handle JSON Feed's special "image" key first
        $img = $this->fetchUrl("image");
        if ($img) {
            $m = new Enclosure;
            $m->url = $img;
            $m->type = "image";
            $m->preferred = true;
            $out[] = $m;
        }
        // handle other attachments
        foreach ($this->fetchMember("attachments", "array") ?? [] as $attachment) {
            $url = $this->fetchUrl("url", $attachment);
            if ($url) {
                $m = new Enclosure;
                $m->url = $url;
                $m->type = $this->fetchType("mime_type", $url, $attachment);
                $m->title = $this->fetchMember("title", "str", $attachment);
                $m->size = $this->fetchMember("size_in_bytes", "int", $attachment);
                $m->duration = $this->fetchMember("duration_in_seconds", "int", $attachment);
                $out[] = $m;
            }
        }
        return $out;
    }

    public function getCategories(): CategoryCollection {
        $out = new CategoryCollection;
        foreach ($this->fetchMember("tags", "array") ?? [] as $tag) {
            if (is_string($tag) && strlen($tag)) {
                $c = new Category;
                $c->name = $tag;
                $out[] = $c;
            }
        }
        return $out;
    }
}
