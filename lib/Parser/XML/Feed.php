<?php
/** @license MIT
 * Copyright 2018 J. King et al.
 * See LICENSE and AUTHORS files for details */

declare(strict_types=1);
namespace MensBeam\Lax\Parser\XML;

use MensBeam\Lax\Parser\Exception;
use MensBeam\Lax\Person\Collection as PersonCollection;
use MensBeam\Lax\Category\Collection as CategoryCollection;
use MensBeam\Lax\Feed as FeedStruct;
use MensBeam\Lax\Date;
use MensBeam\Lax\Text;
use MensBeam\Lax\Url;

class Feed implements \MensBeam\Lax\Parser\Feed {
    use Construct;
    use Primitives\Construct;
    use Primitives\Feed;

    protected const LIBXML_OPTIONS = \LIBXML_BIGLINES | \LIBXML_COMPACT | \LIBXML_HTML_NODEFDTD | \LIBXML_NOCDATA | \LIBXML_NOENT | \LIBXML_NONET | \LIBXML_NOERROR | LIBXML_NOWARNING;

    /** @var string */
    protected $data;
    /** @var string */
    protected $contentType;
    /** @var \MensBeam\Lax\Url */
    protected $url;
    /** @var \DOMElement */
    protected $subject;
    /** @var \DOMXpath */
    protected $xpath;

    /** Constructs a parsed feed */
    public function __construct(string $data, string $contentType = null, string $url = null) {
        $this->data = $data;
        $this->contentType = $contentType;
        if (strlen($url ?? "")) {
            $this->url = new Url($url);
        }
    }

    /** Performs initialization of the instance */
    protected function init(FeedStruct $feed): FeedStruct {
        $this->document = new \DOMDocument();
        if (!$this->document->loadXML($this->data, self::LIBXML_OPTIONS)) {
            throw new Exception("notXML");
        }
        $this->document->documentURI = (string) $this->url;
        $this->xpath = new XPath($this->document);
        $this->subject = $this->document->documentElement;
        $ns = $this->subject->namespaceURI;
        $name = $this->subject->localName;
        if (is_null($ns) && $name === "rss") {
            $this->subject = $this->fetchElement("channel") ?? $this->subject;
            $feed->format = "rss";
            $feed->version = $this->document->documentElement->hasAttribute("version") ? $this->document->documentElement->getAttribute("version") : null;
        } elseif ($ns === XPath::NS['rdf'] && $name === "RDF") {
            $feed->format = "rdf";
            $channel = $this->fetchElement("rss1:channel|rss0:channel");
            if ($channel) {
                $this->subject = $channel;
                $feed->version = ($channel->namespaceURI === XPath::NS['rss1']) ? "1.0" : "0.90";
            } else {
                $element = $this->fetchElement("rss1:item|rss0:item|rss1:image|rss0:image");
                if ($element) {
                    $feed->version = ($element->namespaceURI === XPath::NS['rss1']) ? "1.0" : "0.90";
                } else {
                    throw new Exception("notXMLFeed");
                }
            }
        } elseif ($ns === XPath::NS['atom'] && $name === "feed") {
            $feed->format = "atom";
            $feed->version = "1.0";
        } else {
            throw new Exception("notXMLFeed");
        }
        $feed->meta->url = $this->url;
        return $feed;
    }

    /** Parses the feed to extract data */
    public function parse(FeedStruct $feed = null): FeedStruct {
        $feed = $this->init($feed ?? new FeedStruct);
        $feed->meta->url = $this->url;
        $feed->sched->expired = $this->getExpired();
        $feed->id = $this->getId();
        //$feed->lang = $this->getLang();
        //$feed->url = $this->getUrl();
        //$feed->link = $this->getLink();
        //$feed->title = $this->getTitle();
        //$feed->summary = $this->getSummary();
        //$feed->dateModified = $this->getDateModified();
        //$feed->icon = $this->getIcon();
        //$feed->image = $this->getImage();
        //$feed->people = $this->getPeople();
        //$feed->categories = $this->getCategories();
        //$feed->entries = $this->getEntries($feed);
        return $feed;
    }

    public function getId(): ?string {
        return $this->getIdAtom() ?? $this->getIdDC() ?? $this->getIdRss2();
    }

    public function getUrl(): ?Url {
        return $this->getUrlAtom() ?? $this->getUrlRss1() ?? $this->getUrlPod();
    }

    public function getTitle(): ?Text {
        return $this->getTitleAtom() ?? $this->getTitleRss1() ?? $this->getTitleRss2() ?? $this->getTitleDC() ?? $this->getTitlePod();
    }

    public function getLink(): ?Url {
        return $this->getLinkAtom() ?? $this->getLinkRss1() ?? $this->getLinkRss2();
    }

    public function getSummary(): ?Text {
        // unlike most other data, Atom is not preferred, because Atom doesn't really have feed summaries
        return $this->getSummaryDC() ?? $this->getSummaryRss1() ?? $this->getSummaryRss2() ?? $this->getSummaryPod() ?? $this->getSummaryAtom();
    }

    public function getCategories(): CategoryCollection {
        return $this->getCategoriesAtom() ?? $this->getCategoriesRss2() ?? $this->getCategoriesDC() ?? $this->getCategoriesPod() ?? new CategoryCollection;
    }

    public function getPeople(): PersonCollection {
        $authors = $this->getAuthorsAtom() ?? $this->getAuthorsDC() ?? $this->getAuthorsPod() ?? $this->getAuthorsRss2() ?? new PersonCollection;
        $contributors = $this->getContributorsAtom() ?? $this->getContributorsDC() ?? new PersonCollection;
        $editors = $this->getEditorsRss2() ?? new PersonCollection;
        $webmasters = $this->getWebmastersPod() ?? $this->getWebmastersRss2() ?? new PersonCollection;
        return $authors->merge($contributors, $editors, $webmasters);
    }

    public function getDateModified(): ?Date {
        return $this->getDateModifiedAtom() ?? $this->getDateModifiedDC() ?? $this->getDateModifiedRss2();
    }

    public function getEntries(FeedStruct $feed = null): array {
        return $this->getEntriesAtom() ?? $this->getEntriesRss1() ?? $this->getEntriesRss2() ?? [];
    }

    public function getExpired(): ?bool {
        return $this->getExpiredPod();
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
