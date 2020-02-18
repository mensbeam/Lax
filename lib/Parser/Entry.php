<?php
/** @license MIT
 * Copyright 2018 J. King et al.
 * See LICENSE and AUTHORS files for details */

declare(strict_types=1);
namespace JKingWeb\Lax\Parser;

use JKingWeb\Lax\Person\Collection as PersonCollection;
use JKingWeb\Lax\Category\Collection as CategoryCollection;

interface Entry {
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

    /** General function to fetch the URL of a article related to the entry */
    abstract public function getRelatedLink(): string;
}
