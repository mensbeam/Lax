<?php
/** @license MIT
 * Copyright 2018 J. King et al.
 * See LICENSE and AUTHORS files for details */

declare(strict_types=1);
namespace JKingWeb\Lax\Parser\JSON;

use JKingWeb\Lax\Feed as FeedStruct;
use JKingWeb\Lax\Person\Person;
use JKingWeb\Lax\Person\Collection as PersonCollection;
use JKingWeb\Lax\Category\Collection as CategoryCollection;
use JKingWeb\Lax\Parser\Exception;
use JKingWeb\Lax\Text;
use JKingWeb\Lax\Date;

class Feed implements \JKingWeb\Lax\Parser\Feed {
    use Construct;

    const MIME_TYPES = [
        "application/json",         // generic JSON
        "application/feed+json",    // JSON Feed-specific type
        "text/json",                // obsolete type for JSON
    ];

    const VERSIONS = [
        'https://jsonfeed.org/version/1'   => "1",
        'https://jsonfeed.org/version/1.1' => "1.1",
    ];

    protected $data;
    protected $contentType;
    protected $url;

    /** Constructs a feed parser without actually doing anything */
    public function __construct(string $data, string $contentType = "", string $url = "") {
        $this->data = $data;
        $this->contentType = $contentType;
        $this->url = $url;
    }

    /** Performs format-specific preparation and validation */
    protected function init(FeedStruct $feed): FeedStruct {
        $type = preg_replace("/[\s;,].*/", "", trim(strtolower($this->contentType)));
        if (strlen($type) && !in_array($type, self::MIME_TYPES)) {
            throw new Exception("notJSONType");
        }
        $data = @json_decode($this->data, false, 20);
        if (!is_object($data)) {
            throw new Exception("notJSON");
        } elseif (!isset($data->version) || !preg_match("<^https://jsonfeed\.org/version/(\d+(?:\.\d+)?)$>", $data->version, $match)) {
            throw new Exception("notJSONFeed");
        }
        $this->data = $data;
        $this->version = $match[1];
        $feed->format = "json";
        $feed->version = $this->version;
        return $feed;
    }

    /** Parses the feed to extract sundry metadata */
    public function parse(FeedStruct $feed): FeedStruct {
        $feed = $this->init($feed);
        $feed->title = $this->getTitle();
        $feed->id = $this->getId();
        $feed->url = $this->getUrl();
        $feed->link = $this->getLink();
        $feed->summary = $this->getSummary();
        $feed->icon = $this->getIcon();
        $feed->image = $this->getImage();
        $feed->people = $this->getPeople();
        return $feed;
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
    public function getUrl(): ?string {
        return $this->fetchUrl("feed_url");
    }

    /** General function to fetch the feed title */
    public function getTitle(): ?Text {
        return $this->fetchText("title");
    }

    /** General function to fetch the feed's Web-representation URL */
    public function getLink(): ?string {
        return $this->fetchUrl("home_page_url");
    }

    /** General function to fetch the description of a feed */
    public function getSummary(): ?Text {
        return $this->fetchText("description");
    }

    /** General function to fetch the categories of a feed 
     * 
     * JSON Feed does not have categories at the feed level, so this always returns null
    */
    public function getCategories(): ?CategoryCollection {
        return null;
    }

    /** General function to fetch the feed identifier 
     * 
     * For JSON feeds this is always the feed URL specified in the feed
    */
    public function getId(): ?string {
        return $this->fetchUrl("feed_url");
    }

    /** General function to fetch a collection of people associated with a feed */
    public function getPeople(): PersonCollection {
        return $this->getAuthorsV1() ?? $this->getAuthorV1() ?? new PersonCollection;
    }

    /** General function to fetch the modification date of a feed 
     * 
     * JSON feeds themselves don't have dates, so this always returns null
    */
    public function getDateModified(): ?Date {
        return null;
    }

    public function getIcon(): ?string {
        return $this->fetchUrl("favicon");
    }

    public function getImage(): ?string {
        return $this->fetchUrl("icon");
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
