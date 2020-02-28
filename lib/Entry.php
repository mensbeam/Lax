<?php
/** @license MIT
 * Copyright 2018 J. King et al.
 * See LICENSE and AUTHORS files for details */

declare(strict_types=1);
namespace JKingWeb\Lax;

use JKingWeb\Lax\Category\Collection as CategoryCollection;
use JKingWeb\Lax\Person\Collection as PersonCollection;

class Entry {
    public $link;
    public $relatedLink;
    public $title;
    public $summary;
    public $categories;
    public $people;
    public $dateModified;
    public $dateCreated;

    public function __construct() {
        $this->people = new PersonCollection;
        $this->categories = new CategoryCollection;
    }
}
