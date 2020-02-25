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
    public function getTitle(): string;

    /** General function to fetch the categories of an entry */
    public function getCategories(): CategoryCollection;

    /** General function to fetch the entry identifier */
    public function getId(): string;

    /** General function to fetch a collection of people associated with an entry */
    public function getPeople(): PersonCollection;

    /** General function to fetch the entry's modification date */
    public function getDateModified();

    /** General function to fetch the entry's creation date */
    public function getDateCreated();

    /** General function to fetch the Web URL of the entry */
    public function getLink(): string;

    /** General function to fetch the URL of a article related to the entry */
    public function getRelatedLink(): string;
}
