<?php
/** @license MIT
 * Copyright 2018 J. King et al.
 * See LICENSE and AUTHORS files for details */

declare(strict_types=1);
namespace JKingWeb\Lax\Parser\XML;

use JKingWeb\Lax\Person\Collection as PersonCollection;
use JKingWeb\Lax\Category\Collection as CategoryCollection;
use JKingWeb\Lax\Feed as FeedStruct;
use JKingWeb\Lax\Date;
use JKingWeb\Lax\Text;
use JKingWeb\Lax\Url;

class Feed implements \JKingWeb\Lax\Parser\Feed {
    use Construct;
    use Primitives\Construct;
    use Primitives\Feed;

    /** Constructs a parsed feed */
    public function __construct(string $data, string $contentType = "", string $url = "") {
        $this->init($data, $contentType, $url);
    }

    /** Performs initialization of the instance */
    protected function init(string $data, string $contentType = "", string $url = "") {
        $this->reqUrl = $url;
        $this->document = new \DOMDocument();
        $this->document->loadXML($data, \LIBXML_BIGLINES | \LIBXML_COMPACT);
        $this->document->documentURI = $url;
        $this->xpath = new XPath($this->document);
        $this->subject = $this->document->documentElement;
        $ns = $this->subject->namespaceURI;
        $name = $this->subject->localName;
        if (is_null($ns) && $name=="rss") {
            $this->subject = $this->fetchElement("channel") ?? $this->subject;
            $this->type = "rss";
            $this->version = $this->document->documentElement->getAttribute("version");
        } elseif ($ns==XPath::NS['rdf'] && $name=="RDF") {
            $this->type = "rdf";
            $channel = $this->fetchElement("rss1:channel|rss0:channel");
            if ($channel) {
                $this->subject = $channel;
                $this->version = ($channel->namespaceURI==XPath::NS['rss1']) ? "1.0" : "0.90";
            } else {
                 $element = $this->fetchElement("rss1:item|rss0:item|rss1:image|rss0:image");
                 if ($element) {
                     $this->version = ($element->namespaceURI==XPath::NS['rss1']) ? "1.0" : "0.90";
                 }
            }
        } elseif ($ns==XPath::NS['atom'] && $name=="feed") {
            $this->type = "atom";
            $this->version = "1.0";
        } else {
            throw new \Exception;
        }
        $this->url = $url;
    }

    /** Parses the feed to extract sundry metadata */
    public function parse(FeedStruct $feed = null): FeedStruct {
        $feed = $feed ?? new FeedStruct;
        $feed->id = $this->getId();
        $feed->url = $this->getUrl();
        $feed->link = $this->getLink();
        $feed->title = $this->getTitle();
        $feed->summary = $this->getSummary();
        $feed->people = $this->getPeople();
        $feed->author = $this->people->primary();
        $feed->dateModified = $this->getDateModified();
        $feed->entries = $this->getEntries($feed);
        // do a second pass on missing data we'd rather fill in
        $feed->link = strlen($this->link) ? $this->link : $this->url;
        $feed->title = strlen($this->title) ? $this->title : $this->link;
        // do extra stuff just to test it
        $feed->categories = $this->getCategories();
        return $feed;
    }
    
    /** General function to fetch the canonical feed URL
     * 
     * If the feed does not include a canonical URL, the request URL is returned instead
     */
    public function getUrl(): ?Url {
        return $this->getUrlAtom() ?? $this->getUrlRss1() ?? $this->getUrlPod() ?? $this->reqUrl;
    }
    
    /** General function to fetch the feed title */
    public function getTitle(): ?Text {
        return $this->getTitleAtom() ?? $this->getTitleRss1() ?? $this->getTitleRss2() ?? $this->getTitleDC() ?? $this->getTitlePod() ?? "";
    }

    /** General function to fetch the feed's Web-representation URL */
    public function getLink(): ?Url {
        return $this->getLinkAtom() ?? $this->getLinkRss1() ?? $this->getLinkRss2() ?? "";
    }

    /** General function to fetch the description of a feed */
    public function getSummary(): ?Text {
        // unlike most other data, Atom is not preferred, because Atom doesn't really have feed summaries
        return $this->getSummaryDC() ?? $this->getSummaryRss1() ?? $this->getSummaryRss2() ?? $this->getSummaryPod() ?? $this->getSummaryAtom() ?? "";
    }

    /** General function to fetch the categories of a feed */
    public function getCategories(): CategoryCollection {
        return $this->getCategoriesAtom() ?? $this->getCategoriesRss2() ?? $this->getCategoriesDC() ?? $this->getCategoriesPod() ?? new CategoryCollection;
    }

    /** General function to fetch the feed identifier */
    public function getId(): ?string {
        return $this->getIdAtom() ?? $this->getIdDC() ?? $this->getIdRss2() ?? "";
    }

    /** General function to fetch a collection of all people associated with a feed */
    public function getPeople(): PersonCollection {
        $authors = $this->getAuthorsAtom() ?? $this->getAuthorsDC() ?? $this->getAuthorsPod() ?? $this->getAuthorsRss2() ?? new PersonCollection;
        $contributors = $this->getContributorsAtom() ?? $this->getContributorsDC() ?? new PersonCollection;
        $editors = $this->getEditorsRss2() ?? new PersonCollection;
        $webmasters = $this->getWebmastersPod() ?? $this->getWebmastersRss2() ?? new PersonCollection;
        return $authors->merge($contributors, $editors, $webmasters);
    }

    /** General function to fetch the modification date of a feed */
    public function getDateModified(): ?Date {
        return $this->getDateModifiedAtom() ?? $this->getDateModifiedDC() ?? $this->getDateModifiedRss2();
    }

    /** General function to fetch the entries of a feed */
    public function getEntries(FeedStruct $feed = null): array {
        return $this->getEntriesAtom() ?? $this->getEntriesRss1() ?? $this->getEntriesRss2() ?? [];
    }

    public function getExpired(): ?bool {
        return null;
    }

    public function getLang(): ?string {
        return null;
    }

    public function getIcon(): ?Url {
        return null;
    }

    public function getImage(): ?Url {
        return null;
    }
}
