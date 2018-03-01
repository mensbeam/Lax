<?php
/** @license MIT
 * Copyright 2018 J. King et al.
 * See LICENSE and AUTHORS files for details */

declare(strict_types=1);
namespace JKingWeb\Lax;

use JKingWeb\Lax\Person\Collection as PersonCollection;
use JKingWeb\Lax\Category\Collection as CategoryCollection;

abstract class Entry {
    protected $feed;

    public $link;
    public $externalLink;
    public $title;
    public $summary;
    public $categories;
    public $people;
    public $author;
    public $dateModified;

    /** Parses the feed to extract sundry metadata */
    protected function parse() {
        $this->id = $this->getId();
        //$this->url = $this->getUrl();
        //$this->link = $this->getLink();
        //$this->title = $this->getTitle();
        //$this->summary = $this->getSummary();
        $this->people = $this->getPeople();
        $this->author = $this->people->primary() ?? $this->feed->author;
        //$this->dateModified = $this->getDateModified();
        // do a second pass on missing data we'd rather fill in
        //$this->link = strlen($this->link) ? $this->link : $this->url;
        //$this->title = strlen($this->title) ? $this->title : $this->link;
        // do extra stuff just to test it
        $this->categories = $this->getCategories();
    }

    /** General function to fetch the canonical feed URL */
    //abstract public function getUrl(): string;

    /** General function to fetch the feed title */
    //abstract public function getTitle(): string;

    /** General function to fetch the feed's Web-representation URL */
    //abstract public function getLink(): string;

    /** General function to fetch the description of a feed */
    //abstract public function getSummary(): string;

    /** General function to fetch the categories of a feed */
    abstract public function getCategories(): CategoryCollection;

    /** General function to fetch the feed identifier */
    abstract public function getId(): string;

    /** General function to fetch a collection of people associated with a feed */
    abstract public function getPeople(): PersonCollection;

    /** General function to fetch the feed's modification date */
    //abstract public function getDateModified();
}
