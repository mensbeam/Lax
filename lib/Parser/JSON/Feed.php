<?php
/** @license MIT
 * Copyright 2018 J. King et al.
 * See LICENSE and AUTHORS files for details */

declare(strict_types=1);
namespace JKingWeb\Lax\Parser\JSON;

use JKingWeb\Lax\Person\Person;
use JKingWeb\Lax\Person\Collection as PersonCollection;
use JKingWeb\Lax\Category\Collection as CategoryCollection;

class Feed implements \JKingWeb\Lax\Parser\Feed {
    use Construct;
    use Primitives\Construct;

    /** Constructs a parsed feed */
    public function __construct(string $data, string $contentType = "", string $url = "") {
        $this->init($data, $contentType);
    }

    /** Performs initialization of the instance */
    protected function init(string $data, string $contentType = "", string $url = "") {
        $this->reqUrl = $url;
        $this->json = json_decode($data);
        $this->url = $this->reqUrl;
        $this->type = "json";
        $this->version = $this->fetchMember("version", "str") ?? "";
    }

    /** Parses the feed to extract sundry metadata */
    public function parse(\JKingWeb\Lax\Feed $feed): \JKingWeb\Lax\Feed {
        $feed->id = $this->getId();
        $feed->url = $this->getUrl();
        $feed->link = $this->getLink();
        $feed->title = $this->getTitle();
        $feed->summary = $this->getSummary();
        $feed->people = $this->getPeople();
        $feed->author = $this->people->primary();
        $feed->dateModified = $this->getDateModified();
        $feed->entries = $this->getEntries();
        // do a second pass on missing data we'd rather fill in
        $feed->link = strlen($this->link) ? $this->link : $this->url;
        $feed->title = strlen($this->title) ? $this->title : $this->link;
        // do extra stuff just to test it
        $feed->categories = $this->getCategories();
        return $feed;
    }

    /** General function to fetch the canonical feed URL
     * 
     * If the feed does not include a canonical URL, the request URL is returned instead
     */
    public function getUrl(): string {
        return $this->fetchUrl("feed_url") ?? $this->reqUrl;
    }

    /** General function to fetch the feed title */
    public function getTitle(): string {
        return $this->fetchMember("title", "str") ?? "";
    }

    /** General function to fetch the feed's Web-representation URL */
    public function getLink(): string {
        return $this->fetchUrl("home_page_url") ?? "";
    }

    /** General function to fetch the description of a feed */
    public function getSummary(): string {
        return $this->fetchMember("description", "str") ?? "";
    }

    /** General function to fetch the categories of a feed 
     * 
     * JSON Feed does not have categories at the feed level, so this always returns an empty collection
    */
    public function getCategories(): CategoryCollection {
        return new CategoryCollection;
    }

    /** General function to fetch the feed identifier 
     * 
     * For JSON feeds this is always the feed URL specified in the feed
    */
    public function getId(): string {
        return $this->fetchUrl("feed_url") ?? "";
    }

    /** General function to fetch a collection of people associated with a feed */
    public function getPeople(): PersonCollection {
        return $this->getPeopleV1() ?? new PersonCollection;
    }

    /** General function to fetch the modification date of a feed 
     * 
     * JSON feeds themselves don't have dates, so this always returns null
    */
    public function getDateModified() {
        return null;
    }

    /** General function to fetch the entries of a feed */
    public function getEntries(): array {
        $out = [];
        foreach ($this->fetchMember("items", "array") ?? [] as $data) {
            $entry = new Entry($data, $this);
            if (!strlen($entry->id)) {
                // entries without IDs must be skipped, per spec
                continue;
            } else {
                $out[] = $entry;
            }
        }
        return $out;
    }
}
