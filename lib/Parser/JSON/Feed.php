<?php
/** @license MIT
 * Copyright 2018 J. King et al.
 * See LICENSE and AUTHORS files for details */

declare(strict_types=1);
namespace JKingWeb\Lax\Parser\JSON;

use JKingWeb\Lax\Text;
use JKingWeb\Lax\Date;
use JKingWeb\Lax\Entry;
use JKingWeb\Lax\Feed as FeedStruct;
use JKingWeb\Lax\Person\Collection as PersonCollection;
use JKingWeb\Lax\Category\Collection as CategoryCollection;
use JKingWeb\Lax\Parser\Exception;
use JKingWeb\Lax\Parser\JSON\Entry as EntryParser;

class Feed implements \JKingWeb\Lax\Parser\Feed {
    use Construct;

    protected const MIME_TYPES = [
        "application/json",         // generic JSON
        "application/feed+json",    // JSON Feed-specific type
        "text/json",                // obsolete type for JSON
    ];
    protected const VERSIONS = [
        'https://jsonfeed.org/version/1'   => "1",
        'https://jsonfeed.org/version/1.1' => "1.1",
    ];

    protected $data;
    protected $contentType;
    protected $url;

    /** Constructs a feed parser without actually doing anything */
    public function __construct(string $data, string $contentType = null, string $url = null) {
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

    /** Parses the feed to extract data */
    public function parse(FeedStruct $feed = null): FeedStruct {
        $feed = $this->init($feed ?? new FeedStruct);
        $feed->meta->url = $this->url;
        $feed->sched->expired = $this->getExpired();
        $feed->id = $this->getId();
        $feed->lang = $this->getLang();
        $feed->url = $this->getUrl();
        $feed->link = $this->getLink();
        $feed->title = $this->getTitle();
        $feed->summary = $this->getSummary();
        $feed->dateModified = $this->getDateModified();
        $feed->icon = $this->getIcon();
        $feed->image = $this->getImage();
        $feed->people = $this->getPeople();
        $feed->categories = $this->getCategories();
        $feed->entries = $this->getEntries($feed);
        return $feed;
    }

    /** {@inheritdoc}
     * 
     * For JSON feeds this is always the feed URL specified in the feed
    */
    public function getId(): ?string {
        return $this->fetchUrl("feed_url");
    }

    public function getLang(): ?string {
        return $this->fetchMember("language", "str");
    }

    public function getUrl(): ?string {
        return $this->fetchUrl("feed_url");
    }

    public function getLink(): ?string {
        return $this->fetchUrl("home_page_url");
    }

    public function getTitle(): ?Text {
        return $this->fetchText("title");
    }

    /** {@inheritdoc}
     * 
     *  JSON feeds themselves don't have dates, so this always returns null
    */
    public function getDateModified(): ?Date {
        return null;
    }

    public function getSummary(): ?Text {
        return $this->fetchText("description");
    }

    /** {@inheritdoc}
     * 
     * JSON Feed does not have categories at the feed level, so this always returns and empty collection
    */
    public function getCategories(): CategoryCollection {
        return new CategoryCollection;
    }

    public function getPeople(): PersonCollection {
        return $this->getAuthorsV1() ?? $this->getAuthorV1() ?? new PersonCollection;
    }

    public function getIcon(): ?string {
        return $this->fetchUrl("favicon");
    }

    public function getImage(): ?string {
        return $this->fetchUrl("icon");
    }

    public function getEntries(FeedStruct $feed = null): array {
        $out = [];
        $feed = $feed ?? new FeedStruct;
        foreach ($this->fetchMember("items", "array") ?? [] as $data) {
            $entry = (new EntryParser($data, $feed))->parse();
            if (!strlen((string) $entry->id)) {
                // entries without IDs must be skipped, per spec
                continue;
            } else {
                $out[] = $entry;
            }
        }
        return $out;
    }

    public function getExpired(): ?bool {
        return $this->fetchMember("expired", "bool");
    }
}
