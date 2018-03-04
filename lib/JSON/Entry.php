<?php
/** @license MIT
 * Copyright 2018 J. King et al.
 * See LICENSE and AUTHORS files for details */

declare(strict_types=1);
namespace JKingWeb\Lax\JSON;

use JKingWeb\Lax\Person\Person;
use JKingWeb\Lax\Person\Collection as PersonCollection;
use JKingWeb\Lax\Category\Collection as CategoryCollection;

class Entry extends \JKingWeb\Lax\Entry {
    use Construct;
    use Primitives\Construct;

    protected $url;

    /** Constructs a parsed feed */
    public function __construct($data, \JKingWeb\Lax\Feed $feed) {
        $this->init($data, $feed);
        $this->parse();
    }

    /** Performs initialization of the instance */
    protected function init(\stdClass $data, Feed $feed) {
        $this->feed = $feed;
        $this->json = $data;
        $this->url = $feed->url;
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
}
