<?php
/** @license MIT
 * Copyright 2018 J. King et al.
 * See LICENSE and AUTHORS files for details */

declare(strict_types=1);
namespace JKingWeb\Lax\Parser\JSON;

use JKingWeb\Lax\Feed as FeedStruct;
use JKingWeb\Lax\Entry as EntryStruct;
use JKingWeb\Lax\Person\Collection as PersonCollection;
use JKingWeb\Lax\Category\Collection as CategoryCollection;
use JKingWeb\Lax\Category\Category;
use JKingWeb\Lax\Date;
use JKingWeb\Lax\Text;

class Entry implements \JKingWeb\Lax\Parser\Entry {
    use Construct;

    protected $url;
    /** @var \JKingWeb\Lax\Feed */
    protected $feed;

    public function __construct(\stdClass $data, FeedStruct $feed) {
        $this->data = $data;
        $this->feed = $feed;
        $this->url = $feed->meta->url ?? "";
    }

    protected function init(EntryStruct $entry): EntryStruct {
        return $entry;
    }

    public function parse(EntryStruct $entry = null): EntryStruct {
        $entry = $this->init($entry ?? new EntryStruct);
        $entry->id = $this->getId();
        $entry->link = $this->getLink();
        $entry->relatedLink = $this->getRelatedLink();
        $entry->title = $this->getTitle();
        $entry->dateModified = $this->getDateModified();
        $entry->dateCreated = $this->getDateCreated();
        $entry->people = $this->getPeople();
        $entry->categories = $this->getCategories();
        return $entry; 
    }

    public function getCategories(): CategoryCollection {
        $out = new CategoryCollection;
        foreach ($this->fetchMember("tags", "array") ?? [] as $tag) {
            if (is_string($tag)) {
                $tag = $this->trimText($tag);
                if (strlen($tag)) {
                    $c = new Category;
                    $c->name = $tag;
                    $out[] = $c;
                }
            }
        }
        return $out;
    }

    public function getId(): ?string {
        $id = $this->fetchMember("id", "str") ?? $this->fetchMember("id", "int") ?? $this->fetchMember("id", "float");
        if (is_null($id)) {
            return null;
        } elseif (is_float($id)) {
            if (!fmod($id, 1.0)) {
                return (string) (int) $id;
            } else {
                $id = preg_split("/E\+?/i", str_replace(localeconv()['decimal_point'], ".", (string) $id));
                if (sizeof($id) === 1) {
                    return $id[0];
                } else {
                    $exp = (int) $id[1];
                    $mul = $exp > -1;
                    $exp = abs($exp);
                    list($int, $dec) = explode(".", $id[0]);
                    $dec = strlen($dec) ? str_split($dec, 1) : [];
                    $int = str_split($int, 1);
                    if ($int[0] === "-") {
                        $neg = true;
                        array_shift($int);
                    } else {
                        $neg = false;
                    }
                    while ($exp > 0) {
                        if ($mul && $dec) {
                            $int[] = array_shift($dec);
                        } elseif ($mul) {
                            $int[] = "0";
                        } elseif (!$mul && $int) {
                            array_unshift($dec, array_pop($int));
                        } else {
                            array_unshift($dec, "0");
                        }
                        $exp--;
                    }
                    return ($neg ? "-" : "") . ($int ? implode("", $int) : "0") . ($dec ? ("." . rtrim(implode("", $dec), "0")) : "");
                }
            }
        } else {
            return (string) $id;
        }
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

    public function getLink(): ?string {
        return $this->fetchUrl("url");
    }

    public function getRelatedLink(): ?string {
        return $this->fetchUrl("external_url");
    }

    public function getTitle(): ?Text {
        return $this->fetchText("title");
    }

    public function getSummary(): ?Text {
        return $this->fetchText("summary");
    }

    public function getContent(): ?Text {
        $out = $this->fetchText("content_text") ?? new Text;
        $html = $this->fetchMember("content_html", "str");
        if (strlen($html ?? "")) {
            $out->html = $html;
            $out->htmlBase = $this->feed->meta->url;
        }
        return $out;
    }
}
