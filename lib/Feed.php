<?php
/** @license MIT
 * Copyright 2018 J. King et al.
 * See LICENSE and AUTHORS files for details */

declare(strict_types=1);
namespace JKingWeb\Lax;

use JKingWeb\Lax\Person\Person;
use JKingWeb\Lax\Person\Collection as PersonCollection;

abstract class Feed {
    protected $reqUrl;

    public $type;
    public $version;
    public $url;
    public $link;
    public $title;
    public $summary;
    public $categories;
    public $people;
    public $author;
    public $dateModified;

    /** Constructs a parsed feed */
    abstract public function __construct(string $data, string $contentType = "", string $url = "");

    /** Parses the feed to extract sundry metadata */
    protected function parse() {
        $this->id = $this->getId();
        $this->url = $this->getUrl();
        $this->link = $this->getLink();
        $this->title = $this->getTitle();
        $this->summary = $this->getSummary();
        $this->people = $this->getPeople();
        $this->author = $this->people->primary();
        $this->dateModified = $this->getDateModified();
        // do a second pass on missing data we'd rather fill in
        $this->link = strlen($this->link) ? $this->link : $this->url;
        $this->title = strlen($this->title) ? $this->title : $this->link;
    }
    
    /** General function to fetch the canonical feed URL */
    abstract public function getUrl(): string;
    
    /** General function to fetch the feed title */
    abstract public function getTitle(): string;

    /** General function to fetch the feed's Web-representation URL */
    abstract public function getLink(): string;

    /** General function to fetch the description of a feed */
    abstract public function getSummary(): string;

    /** General function to fetch the categories of a feed 
     * 
     * If the $grouped parameter is true, and array of arrays will be returned, keyed by taxonomy/scheme
     * 
     * The $humanFriendly parameter only affects Atom categories
    */
    abstract public function getCategories(bool $grouped = false, bool $humanFriendly = true): array;

    /** General function to fetch the feed identifier */
    abstract public function getId(): string;

    /** General function to fetch a collection of people associated with a feed */
    abstract public function getPeople(): PersonCollection;

    /** General function to fetch the feed's modification date */
    abstract public function getDateModified();
}
