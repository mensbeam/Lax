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
    public $dateCreated;

    /** Parses the feed to extract sundry metadata */
    protected function parse() {
        $this->id = $this->getId();
        $this->link = $this->getLink();
        $this->title = $this->getTitle();
        $this->people = $this->getPeople();
        $this->author = $this->people->primary() ?? $this->feed->author;
        $this->dateModified = $this->getDateModified();
        $this->dateCreated = $this->getDateCreated();
        // do a second pass on missing data we'd rather fill in
        $this->title = strlen($this->title) ? $this->title : $this->link;
        // do extra stuff just to test it
        $this->categories = $this->getCategories();
    }

    /** General function to fetch the entry title */
    abstract public function getTitle(): string;

    /** General function to fetch the categories of an entry */
    abstract public function getCategories(): CategoryCollection;

    /** General function to fetch the entry identifier */
    abstract public function getId(): string;

    /** General function to fetch a collection of people associated with an entry */
    abstract public function getPeople(): PersonCollection;

    /** General function to fetch the entry's modification date */
    abstract public function getDateModified();

    /** General function to fetch the entry's creation date */
    abstract public function getDateCreated();

    /** General function to fetch the Web URL of the entry */
    abstract public function getLink(): string;
}
