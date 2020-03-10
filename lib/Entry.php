<?php
/** @license MIT
 * Copyright 2018 J. King et al.
 * See LICENSE and AUTHORS files for details */

declare(strict_types=1);
namespace MensBeam\Lax;

use MensBeam\Lax\Category\Collection as CategoryCollection;
use MensBeam\Lax\Person\Collection as PersonCollection;
use MensBeam\Lax\Enclosure\Collection as EnclosureCollection;

class Entry {
    /** @var string $id The persistent identifier of the entry. This is often identical to the URL of the entry, but the latter may change
     *
     * While identifiers are usually supposed to be globally unique, in practice they are frequently only unique within the context of a particular newsfeed
     */
    public $id;
    /** @var string $lang The human language of the entry */
    public $lang;
    /** @var \MensBeam\Lax\Url $link The URL of the entry as published somwehere else, usually on the World Wide Web */
    public $link;
    /** @var \MensBeam\Lax\Url $relatedLink The URL of an article related to the entry. For example, if the entry is a commentary on an essay, this property might provide the URL of that essay */
    public $relatedLink;
    /** @var \MensBeam\Lax\Text $title The title of the entry */
    public $title;
    /** @var \MensBeam\Lax\Text $content The content of the entry
     *
     * This may be merely a summary or excerpt for many newsfeeds */
    public $content;
    /** @var \MensBeam\Lax\Text $summary A short summary or excerpt of the entry, distinct from the content */
    public $summary;
    /** @var \MensBeam\Lax\Date $dateCreated The date and time at which the entry was first made available */
    public $dateCreated;
    /** @var \MensBeam\Lax\Date $dateModified The date and time at which the entry was last modified */
    public $dateModified;
    /** @var \MensBeam\Lax\Url $banner The URL of an image to use as a banner. Only applicable to JSON Feed documents */
    public $banner;
    /** @var \MensBeam\Lax\Category\Collection $categories The set of categories associated with the entry */
    public $categories;
    /** @var \MensBeam\Lax\Person\Collection $people The set of people (authors, contributors, etc) associated with the entry */
    public $people;
    /** @var \MensBeam\Lax\Enclosures\Collection $enclosures The set of external files (i.e. enclosuresor attachments) associated with the entry */
    public $enclosures;

    public function __construct() {
        $this->people = new PersonCollection;
        $this->categories = new CategoryCollection;
        $this->enclosures = new EnclosureCollection;
    }
}
