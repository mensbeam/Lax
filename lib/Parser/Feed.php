<?php
/** @license MIT
 * Copyright 2018 J. King et al.
 * See LICENSE and AUTHORS files for details */

declare(strict_types=1);
namespace JKingWeb\Lax\Parser;

use JKingWeb\Lax\Person\Collection as PersonCollection;
use JKingWeb\Lax\Category\Collection as CategoryCollection;

interface Feed {
    /** General function to fetch the canonical feed URL */
    abstract public function getUrl(): string;

    /** General function to fetch the feed title */
    abstract public function getTitle(): string;

    /** General function to fetch the feed's Web-representation URL */
    abstract public function getLink(): string;

    /** General function to fetch the description of a feed */
    abstract public function getSummary(): string;

    /** General function to fetch the categories of a feed */
    abstract public function getCategories(): CategoryCollection;

    /** General function to fetch the feed identifier */
    abstract public function getId(): string;

    /** General function to fetch a collection of people associated with a feed */
    abstract public function getPeople(): PersonCollection;

    /** General function to fetch the feed's modification date */
    abstract public function getDateModified();

    /** General function to fetch the feed's modification date */
    abstract public function getEntries() : array;
}
