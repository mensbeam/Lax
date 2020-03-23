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
        $entry->lang = $this->getLang();
        $entry->id = $this->getId();
        $entry->link = $this->getLink();
        $entry->relatedLink = $this->getRelatedLink();
        $entry->title = $this->getTitle();
        $entry->dateModified = $this->getDateModified();
        $entry->dateCreated = $this->getDateCreated();
        $entry->content = $this->getContent();
        $entry->summary = $this->getSummary();
        $entry->banner = $this->getBanner();
        $entry->people = $this->getPeople();
        $entry->categories = $this->getCategories();
        $entry->enclosures = $this->getEnclosures();
        return $entry;
    }

    public function getLang(): ?string {
        return $this->getLangXML()      // xml:lang attribute
            ?? $this->getLangDC()       // Dublin Core language
            ?? $this->getLangRss2();    // RSS language
    }

    public function getId(): ?string {
        return $this->fetchString("atom:id", ".+")                  // Atom identifier
            ?? $this->fetchString("dc:identifier", ".+")            // Dublin Core identifier
            ?? $this->fetchString("self::rss1:item/@rdf:about")     // RSS 1.0 RDF identifier
            ?? $this->fetchString("rss2:guid", ".+");               // RSS 2.0 GUID, as string
    }

    public function getLink(): ?Url {
        $link = $this->getLinkAtom() ?? $this->getLinkRss1(); // somme kind of unambigulous link
        if (!$link) {
            /* If there is no reliable related link, attempt to discern 
               both a link and related link from RSS 2.0 metadata, 
               and use the former; otherwise use whichever is available
            */
            $candidates = $this->getLinkAndRelatedRss2();
            if (!$this->getRelatedLinkDefinitive()) {
                $link = $candidates[0];
            } else {
                $link = $candidates[1] ?? $candidates[0];
            }
        }
        return $link;
    }

    public function getRelatedLink(): ?Url {
        return $this->getRelatedLinkDefinitive()
            ?? $this->getLinkAndRelatedRss2()[1];
    }

    public function getTitle(): ?Text {
        return $this->getTitleAtom()    // Atom title
            ?? $this->getTitleRss1()    // RSS 0.90 or RSS 1.0 title
            ?? $this->getTitleRss2()    // RSS 2.0 title
            ?? $this->getTitleDC()      // Dublin Core title
            ?? $this->getTitlePod();    // iTunes podcast title
    }

    public function getDateModified(): ?Date {
        /*  fetching a date works differently from other data as only Atom has
            well-defined semantics here. Thus the semantics of all the other
            formats are equal, and we want the latest date, whatever it is.
        */
        return $this->fetchDate("atom:updated", self::DATE_LATEST)                              // Atom update date
            ?? $this->fetchDate("dc:date|rss2:pubDate|rss2:lastBuildDate", self::DATE_LATEST);  // Latest other datee
    }

    public function getDateCreated(): ?Date {
        /*  fetching a date works differently from other data as only Atom has
            well-defined semantics here. Thus the semantics of all the other
            formats are equal, and we want the earliest date, but only if 
            there are at least two
        */
        return $this->fetchDate("atom:created", self::DATE_EARLIEST)    // Atom creation date
            ?? $this->getAssumedDateCreated();                          // Earliest other date
    }

    public function getContent(): ?Text {
        return null;
    }

    public function getSummary(): ?Text {
        return null;
    }

    public function getBanner(): ?Url {
        return null;
    }

    public function getPeople(): PersonCollection {
        return new PersonCollection;
    }

    public function getCategories(): CategoryCollection {
        return new CategoryCollection;
    }

    public function getEnclosures(): EnclosureCollection {
        return new EnclosureCollection;
    }

    protected function getRelatedLinkDefinitive(): ?url {
        // Only Atom related links are definitive for now
        return $this->fetchAtomRelation("related", ["text/html", "application/xhtml+xml"]);  // Atom related relation
    }

    /** Returns an indexed array containing the entry link (or null)
     * and the entry related link (or null)
     * 
     * This follows the suggestion in RSS 2.0 that if the permalink-GUID 
     * and link differ, then the latter is a related link. For our purposes
     * they are considered to differ if they point to different hosts or 
     * have different schemes
     */
    protected function getLinkAndRelatedRss2(): array {
        $link = $this->fetchUrl("rss2:link");
        $guid = $this->fetchUrl("rss2:guid[not(@isPermalink) or @isPermalink='true']");
        if ($link && $guid) {
            if ($link->getScheme() !== $guid->getScheme() || $link->getAuthority() !== $guid->getAuthority()) {
                return [$guid, $link];
            }
        }
        return [$link ?? $guid, null];
    }

    protected function getAssumedDateCreated(): ?Date {
        $dates = $this->fetchDate("dc:date|rss2:pubDate|rss2:lastBuildDate", self::DATE_ALL);
        if (sizeof($dates) > 1) {
            return $dates[0];
        }
        return null;
    }
}
