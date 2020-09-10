<?php
/** @license MIT
 * Copyright 2018 J. King et al.
 * See LICENSE and AUTHORS files for details */

declare(strict_types=1);
namespace MensBeam\Lax\Parser;

use MensBeam\Lax\Url;
use MensBeam\Lax\Feed as FeedStruct;

trait AbstractFeed {
    /** Constructs a feed parser */
    public function __construct(string $data, string $contentType = null, string $url = null) {
        $this->data = $data;
        $this->contentType = $contentType;
        if (strlen($url ?? "")) {
            $this->url = $url;
        }
    }

    /** Parses the feed to extract data */
    public function parse(FeedStruct $feed = null): FeedStruct {
        $feed = $this->init($feed ?? new FeedStruct);
        $feed->meta->url = strlen($this->url ?? "") ? new Url($this->url) : null;
        $feed->sched = $this->getSchedule();
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
}