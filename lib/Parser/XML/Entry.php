<?php
/** @license MIT
 * Copyright 2018 J. King et al.
 * See LICENSE and AUTHORS files for details */

declare(strict_types=1);
namespace JKingWeb\Lax\Parser\XML;

use JKingWeb\Lax\Person\Collection as PersonCollection;
use JKingWeb\Lax\Category\Collection as CategoryCollection;
use JKingWeb\Lax\Enclosure\Collection as EnclosureCollection;
use JKingWeb\Lax\Date;
use JKingWeb\Lax\Text;
use JKingWeb\Lax\Url;

class Entry implements \JKingWeb\Lax\Parser\Entry {
    use Construct;
    use Primitives\Construct;
    use Primitives\Entry;

    /** Constructs a parsed feed */
    public function __construct(\DOMElement $data, Feed $feed, XPath $xpath = null) {
        $this->init($data, $feed, $xpath);
    }

    /** Performs initialization of the instance */
    protected function init(\DOMElement $node, Feed $feed, XPath $xpath = null) {
        $this->xpath = $xpath ?? new XPath($node->ownerDocument);
        $this->subject = $node;
        $this->feed = $feed;
    }

    /** Parses the feed to extract sundry metadata */
    protected function parse(\DOMElement $data, \JKingWeb\Lax\Feed $feed): \JKingWeb\Lax\Entry {
        $entry = new \JKingWeb\Lax\Entry;
        $entry->id = $this->getId();
        $entry->link = $this->getLink();
        $entry->relatedLink = $this->getRelatedLink();
        $entry->title = $this->getTitle();
        $entry->people = $this->getPeople();
        $entry->author = $this->people->primary() ?? $this->feed->author;
        $entry->dateModified = $this->getDateModified();
        $entry->dateCreated = $this->getDateCreated();
        // do a second pass on missing data we'd rather fill in
        $entry->title = strlen($this->title) ? $this->title : $this->link;
        // do extra stuff just to test it
        $entry->categories = $this->getCategories();
        return $entry;
    }

    /** General function to fetch the entry title */
    public function getTitle(): ?Text {
        return $this->getTitleAtom() ?? $this->getTitleRss1() ?? $this->getTitleRss2() ?? $this->getTitleDC() ?? $this->getTitlePod() ?? "";
    }

    /** General function to fetch the categories of an entry */
    public function getCategories(): CategoryCollection {
        return $this->getCategoriesAtom() ?? $this->getCategoriesRss2() ?? $this->getCategoriesDC() ?? $this->getCategoriesPod() ?? new CategoryCollection;
    }

    /** General function to fetch the entry identifier */
    public function getId(): ?string {
        return $this->getIdAtom() ?? $this->getIdDC() ?? $this->getIdRss2() ?? "";
    }

    /** General function to fetch a collection of all people associated with a entry */
    public function getPeople(): PersonCollection {
        $authors = $this->getAuthorsAtom() ?? $this->getAuthorsDC() ?? $this->getAuthorsPod() ?? $this->getAuthorsRss2() ?? $this->feed->people->filterForRole("author");
        $contributors = $this->getContributorsAtom() ?? $this->getContributorsDC() ?? new PersonCollection;
        return $authors->merge($contributors);
    }

    /** General function to fetch the modification date of an entry */
    public function getDateModified(): ?Date {
        return $this->getDateModifiedAtom() ?? $this->getDateModifiedDC() ?? $this->getDateModifiedRss2();
    }

    /** General function to fetch the creation date of an entry */
    public function getDateCreated(): ?Date {
        return $this->getDateModifiedAtom();
    }

    /** General function to fetch the Web URL of the entry */
    public function getLink(): ?Url {
        return $this->getLinkAtom() ?? $this->getLinkRss1() ?? $this->getLinkRss2() ?? "";
    }

    /** General function to fetch the URL of a article related to the entry
     *
     * This is only reliable with Atom feeds
     */
    public function getRelatedLink(): ?Url {
        return $this->getRelatedLinkAtom() ?? "";
    }

    public function getBanner(): ?Url {
        return null;
    }

    public function getContent(): ?Text {
        return new Text;
    }

    public function getEnclosures(): EnclosureCollection {
        return new EnclosureCollection;
    }

    public function getLang(): ?string {
        return null;
    }

    public function getSummary(): ?Text {
        return new Text;
    }
}
