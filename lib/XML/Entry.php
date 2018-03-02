<?php
/** @license MIT
 * Copyright 2018 J. King et al.
 * See LICENSE and AUTHORS files for details */

declare(strict_types=1);
namespace JKingWeb\Lax\XML;

use JKingWeb\Lax\Person\Collection as PersonCollection;
use JKingWeb\Lax\Category\Collection as CategoryCollection;

class Entry extends \JKingWeb\Lax\Entry {
    use Construct;
    use Primitives\Construct;
    use Primitives\Entry;

    /** Constructs a parsed feed */
    public function __construct(\DOMElement $data, Feed $feed, XPath $xpath = null) {
        $this->init($data, $feed, $xpath);
        $this->parse();
    }

    /** Performs initialization of the instance */
    protected function init(\DOMElement $node, Feed $feed, XPath $xpath = null) {
        $this->xpath = $xpath ?? new XPath($node->ownerDocument);
        $this->subject = $node;
        $this->feed = $feed;
    }
    
    /** General function to fetch the entry title */
    public function getTitle(): string {
        return $this->getTitleAtom() ?? $this->getTitleRss1() ?? $this->getTitleRss2() ?? $this->getTitleDC() ?? $this->getTitlePod() ?? "";
    }

    /** General function to fetch the categories of an entry */
    public function getCategories(): CategoryCollection {
        return $this->getCategoriesAtom() ?? $this->getCategoriesRss2() ?? $this->getCategoriesDC() ?? $this->getCategoriesPod() ?? new CategoryCollection;
    }

    /** General function to fetch the entry identifier */
    public function getId(): string {
        return $this->getIdAtom() ?? $this->getIdDC() ?? $this->getIdRss2() ?? "";
    }

    /** General function to fetch a collection of all people associated with a entry */
    public function getPeople(): PersonCollection {
        $authors = $this->getAuthorsAtom() ?? $this->getAuthorsDC() ?? $this->getAuthorsPod() ?? $this->getAuthorsRss2() ?? $this->feed->people->filterForRole("author");
        $contributors = $this->getContributorsAtom() ?? $this->getContributorsDC() ?? new PersonCollection;
        return $authors->merge($contributors);
    }

    /** General function to fetch the modification date of an entry */
    public function getDateModified() {
        return $this->getDateModifiedAtom() ?? $this->getDateModifiedDC() ?? $this->getDateModifiedRss2();
    }
}
