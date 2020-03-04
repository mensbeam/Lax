<?php
/** @license MIT
 * Copyright 2018 J. King et al.
 * See LICENSE and AUTHORS files for details */

declare(strict_types=1);
namespace JKingWeb\Lax\Parser;

use JKingWeb\Lax\Person\Collection as PersonCollection;
use JKingWeb\Lax\Category\Collection as CategoryCollection;
use JKingWeb\Lax\Enclosure\Collection as EnclosureCollection;
use JKingWeb\Lax\Date;
use JKingWeb\Lax\Text;
use JKingWeb\Lax\Url;

interface Entry {
    /** Returns the globally unique identifier of the entry; this is usually a URI */
    public function getId(): ?string;

    /** Returns the human language of the entry */
    public function getLang(): ?string;

    /** Returns the title text of the entry, which may be plain text or HTML */
    public function getTitle(): ?Text;

    /** Returns the URL of the published article this entry summarizes */
    public function getLink(): ?Url;

    /** Returns the URL of an article related to the entry */
    public function getRelatedLink(): ?Url;

    /** Returns the content of the entry, either in plain text or HTML */
    public function getContent(): ?Text;

    /** Returns a short description of the entry, either in plain text or HTML; this should be distinct from the content */
    public function getSummary(): ?Text;

    /** Returns the date and time at which the entry was first made available */
    public function getDateCreated(): ?Date;

    /** Returns the date and time at which the entry was last modified */
    public function getDateModified(): ?Date;

    /** Returns the URL of a large image used as a banner when displaying the entry
     * 
     * This is only used by JSON Feed entries
     */
    public function getBanner(): ?Url;

    /** Returns a collection of categories associated with the entry */
    public function getCategories(): CategoryCollection;

    /** Returns a collection of persons associated with the entry*/
    public function getPeople(): PersonCollection;

    /** Returns a collection of external files associated with the entry i.e. attachments */
    public function getEnclosures(): EnclosureCollection;
}
