<?php
/** @license MIT
 * Copyright 2018 J. King et al.
 * See LICENSE and AUTHORS files for details */

declare(strict_types=1);
namespace JKingWeb\Lax\Parser;

use JKingWeb\Lax\Person\Collection as PersonCollection;
use JKingWeb\Lax\Category\Collection as CategoryCollection;
use JKingWeb\Lax\Date;
use JKingWeb\Lax\Text;
use JKingWeb\Lax\Feed as FeedStruct;

interface Feed {
    /** Returns the globally unique identifier of the newsfeed; this is usually a URI */
    public function getId(): ?string;

    /** Returns the human language of the newsfeed */
    public function getLang(): ?string;

    /** Returns the canonical URL of the newsfeed, as contained in the document itself */
    public function getUrl(): ?string;

    /** Returns the title text of the newsfeed, which may be plain text or HTML */
    public function getTitle(): ?Text;

    /** Returns the URL of the publication this newsfeed summarizes */
    public function getLink(): ?string;

    /** Returns a short description of the newsfeed, either in plain text or HTML */
    public function getSummary(): ?Text;

    /** Returns the date and time at which the newsfeed was last modified, as contained in the document itself */
    public function getDateModified(): ?Date;

    /** Returns the URL of a small image used as an icon to identify the newsfeed */
    public function getIcon(): ?string;

    /** Returns the URL of a large image used as a poster or banner to identify the newsfeed */
    public function getImage(): ?string;

    /** Returns a collection of categories associated with the newsfeed as a whole. Each category is a structured Category object */
    public function getCategories(): CategoryCollection;

    /** Returns a collection of persons associated with the newsfeed as a whole. Each person is a structured Person object */
    public function getPeople(): PersonCollection;

    /** Returns the list of entries
     * 
     * @param \JKingWeb\Lax\Feed $feed The newsfeed to which the entry belongs. Some data from the newsfeed may be used in parsing the entry
     */
    public function getEntries(FeedStruct $feed = null): array;

    /** Returns whether the newsfeed has ceased publication */
    public function getExpired(): ?bool;
}