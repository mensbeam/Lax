<?php
/** @license MIT
 * Copyright 2018 J. King et al.
 * See LICENSE and AUTHORS files for details */

declare(strict_types=1);
namespace MensBeam\Lax\Parser\XML;

use MensBeam\Lax\Feed as FeedStruct;
use MensBeam\Lax\Entry as EntryStruct;
use MensBeam\Lax\Person\Collection as PersonCollection;
use MensBeam\Lax\Category\Collection as CategoryCollection;
use MensBeam\Lax\Enclosure\Collection as EnclosureCollection;
use MensBeam\Lax\Date;
use MensBeam\Lax\Text;
use MensBeam\Lax\Url;

class Entry extends Construct implements \MensBeam\Lax\Parser\Entry {
    public function __construct(\DOMElement $data, XPath $xpath, FeedStruct $feed) {
        $this->subject = $data;
        $this->xpath = $xpath;
        $this->feed = $feed;
    }

    public function parse(EntryStruct $entry = null): EntryStruct {
        $entry = $entry ?? new EntryStruct;
        //$entry->lang = $this->getLang();
        //$entry->id = $this->getId();
        //$entry->link = $this->getLink();
        //$entry->relatedLink = $this->getRelatedLink();
        //$entry->title = $this->getTitle();
        //$entry->dateModified = $this->getDateModified();
        //$entry->dateCreated = $this->getDateCreated();
        //$entry->content = $this->getContent();
        //$entry->summary = $this->getSummary();
        //$entry->banner = $this->getBanner();
        //$entry->people = $this->getPeople();
        //$entry->categories = $this->getCategories();
        //$entry->enclosures = $this->getEnclosures();
        return $entry;
    }

    public function getTitle(): ?Text {
        return $this->getTitleAtom() ?? $this->getTitleRss1() ?? $this->getTitleRss2() ?? $this->getTitleDC() ?? $this->getTitlePod() ?? "";
    }

    public function getCategories(): CategoryCollection {
        return $this->getCategoriesAtom() ?? $this->getCategoriesRss2() ?? $this->getCategoriesDC() ?? $this->getCategoriesPod() ?? new CategoryCollection;
    }

    public function getId(): ?string {
        return $this->getIdAtom() ?? $this->getIdDC() ?? $this->getIdRss2() ?? "";
    }

    public function getPeople(): PersonCollection {
        $authors = $this->getAuthorsAtom() ?? $this->getAuthorsDC() ?? $this->getAuthorsPod() ?? $this->getAuthorsRss2() ?? $this->feed->people->filterForRole("author");
        $contributors = $this->getContributorsAtom() ?? $this->getContributorsDC() ?? new PersonCollection;
        return $authors->merge($contributors);
    }

    public function getDateModified(): ?Date {
        return $this->getDateModifiedAtom() ?? $this->getDateModifiedDC() ?? $this->getDateModifiedRss2();
    }

    public function getDateCreated(): ?Date {
        return $this->getDateModifiedAtom();
    }

    public function getLink(): ?Url {
        return $this->getLinkAtom() ?? $this->getLinkRss1() ?? $this->getLinkRss2() ?? "";
    }

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
