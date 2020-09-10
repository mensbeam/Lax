<?php
/** @license MIT
 * Copyright 2018 J. King et al.
 * See LICENSE and AUTHORS files for details */

declare(strict_types=1);
namespace MensBeam\Lax\Parser;

use MensBeam\Lax\Feed as FeedStruct;
use MensBeam\Lax\Entry as EntryStruct;

trait AbstractEntry {
    public function __construct($data, FeedStruct $feed) {
        $this->data = $data;
        $this->feed = $feed;
        $this->url = $feed->meta->url ? (string) $feed->meta->url : null;
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
}