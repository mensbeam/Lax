<?php
/** @license MIT
 * Copyright 2018 J. King et al.
 * See LICENSE and AUTHORS files for details */

declare(strict_types=1);
namespace JKingWeb\Lax;

use JKingWeb\Lax\Category\Collection as CategoryCollection;
use JKingWeb\Lax\Person\Collection as PersonCollection;
use JKingWeb\Lax\Enclosure\Collection as EnclosureCollection;

class Entry {
    /** @var string $id The persistent identifier of the entry. This is often identical to the URL of the entry, but the latter may change
     * 
     * While identifiers are usually supposed to be globally unique, in practice they are frequently only unique within the context of a particular newsfeed
     */
    public $id;
    /** @var string $lang The human language of the entry */
    public $lang;
    /** @var string $link The URL of the entry as published somwehere else, usually on the World Wide Web */
    public $link;
    /** @var string $relatedLink The URL of an article related to the entry. For example, if the entry is a commentary on an essay, this property might provide the URL of that essay */
    public $relatedLink;
    /** @var \JKingWeb\Lax\Text $title The title of the entry */
    public $title;
    /** @var \JKingWeb\Lax\Text $content The content of the entry
     * 
     * This may be merely a summary or excerpt for many newsfeeds */
    public $content;
    /** @var \JKingWeb\Lax\Text $summary A short summary or excerpt of the entry, distinct from the content */
    public $summary;
    /** @var \JKingWeb\Lax\Date $dateCreated The date and time at which the entry was first made available */
    public $dateCreated;
    /** @var \JKingWeb\Lax\Date $dateModified The date and time at which the entry was last modified */
    public $dateModified;
    /** @var \JKingWeb\Lax\Category\Collection $categories The set of categories associated with the entry */
    public $categories;
    /** @var \JKingWeb\Lax\Person\Collection $people The set of people (authors, contributors, etc) associated with the entry */
    public $people;
    /** @var \JKingWeb\Lax\Enclosures\Collection $enclosures The set of external files (i.e. enclosuresor attachments) associated with the entry */
    public $enclosures;

    public function __construct() {
        $this->people = new PersonCollection;
        $this->categories = new CategoryCollection;
        $this->enclosures = new EnclosureCollection;
    }
}
