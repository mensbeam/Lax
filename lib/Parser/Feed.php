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
    /** General function to fetch the canonical feed URL */
    public function getUrl(): ?string;

    /** General function to fetch the feed title */
    public function getTitle(): ?Text;

    /** General function to fetch the feed's Web-representation URL */
    public function getLink(): ?string;

    /** General function to fetch the description of a feed */
    public function getSummary(): ?Text;

    /** General function to fetch the categories of a feed */
    public function getCategories(): CategoryCollection;

    /** General function to fetch the feed identifier */
    public function getId(): ?string;

    /** General function to fetch a collection of people associated with a feed */
    public function getPeople(): PersonCollection;

    /** General function to fetch the feed's modification date */
    public function getDateModified(): ?Date;

    /** General function to fetch the feed's modification date */
    public function getEntries(FeedStruct $feed = null): array;
}
