<?php
/** @license MIT
 * Copyright 2018 J. King et al.
 * See LICENSE and AUTHORS files for details */

declare(strict_types=1);
namespace JKingWeb\Lax\Parser\JSON;

use JKingWeb\Lax\Person\Person;
use JKingWeb\Lax\Person\Collection as PersonCollection;
use JKingWeb\Lax\Category\Collection as CategoryCollection;
use JKingWeb\Lax\Category\Category;

class Entry implements \JKingWeb\Lax\Parser\Entry {
    use Construct;

    protected $url;

    /** Constructs a parsed feed */
    public function __construct($data, \JKingWeb\Lax\Parser\Feed $feed) {
        $this->init($data, $feed);
    }

    /** Performs initialization of the instance */
    protected function init(\stdClass $data, Feed $feed) {
        $this->feed = $feed;
        $this->json = $data;
        $this->url = $feed->url;
    }

    /** Parses the feed to extract sundry metadata */
    protected function parse(\stdClass $data, \JKingWeb\Lax\Feed $feed): \JKingWeb\Lax\Entry {
        $entry = new \JKingWeb\Lax\Entry;
        $entry->id = $this->getId();
        $entry->link = $this->getLink();
        $entry->relatedLink = $this->getRelatedLink();
        $entry->title = $this->getTitle();
        $entry->people = $this->getPeople();
        $entry->author = $this->people->primary() ?? $this->feed->author;
        $entry->dateModified = $this->getDateModified();
        $entry->dateCreated = $this->getDateCreated();
        // do a second pass on missing data we'd rather fill in
        $entry->title = strlen($this->title) ? $this->title : $this->link;
        // do extra stuff just to test it
        $entry->categories = $this->getCategories();
        return $entry; 
    }

    /** General function to fetch the categories of an entry */
    public function getCategories(): CategoryCollection {
        $out = new CategoryCollection;
        foreach ($this->fetchMember("tags", "array") ?? [] as $tag) {
            $tag = $this->trimText((string) $tag);
            if (strlen($tag)) {
                $c = new Category;
                $c->name = $tag;
                $out[] = $c;
            }
        }
        return $out;
    }

    /** General function to fetch the entry identifier */
    public function getId(): string {
        return $this->fetchMember("id", "str") ?? "";
    }

    /** General function to fetch a collection of people associated with an entry */
    public function getPeople(): PersonCollection {
        return $this->getPeopleV1() ?? new PersonCollection;
    }

    /** General function to fetch the modification date of an entry */
    public function getDateModified() {
        return $this->fetchDate("date_modified");
    }

    /** General function to fetch the creation date of an entry */
    public function getDateCreated() {
        return $this->fetchDate("date_published");
    }

    /** General function to fetch the entry title */
    public function getTitle(): string {
        return $this->fetchMember("title", "str") ?? "";
    }

    /** General function to fetch the entry's Web-representation URL */
    public function getLink(): string {
        return $this->fetchUrl("url") ?? "";
    }

    /** General function to fetch the URL of a article related to the entry */
    public function getRelatedLink(): string {
        return $this->fetchUrl("external_url") ?? "";
    }
}
