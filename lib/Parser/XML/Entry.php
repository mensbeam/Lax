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
use MensBeam\Lax\Enclosure\Enclosure;
use MensBeam\Lax\Date;
use MensBeam\Lax\MimeType;
use MensBeam\Lax\Text;
use MensBeam\Lax\Url;

class Entry extends Construct implements \MensBeam\Lax\Parser\Entry {
    use \MensBeam\Lax\Parser\AbstractEntry;

    protected const ENCLOSURE_ATTR_INTEGERS = [
        'size'     => "@fileSize",
        'width'    => "@width",
        'height'   => "@height",
        'duration' => "@duration",
        'bitrate'  => "@bitrate",
    ];
    protected const ENCLOSURE_ATTR_BOOLEANS = [
        'preferred' => "@isDefault[normalize-space()='true']",
        'sample'    => "@expression[normalize-space()='sample']",
    ];

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
        return $this->fetchString("atom:id", ".+")                          // Atom identifier
            ?? $this->fetchString("dc:identifier|dct:identifier", ".+")     // Dublin Core identifier
            ?? $this->fetchString("self::rss1:item/@rdf:about")             // RSS 1.0 RDF identifier
            ?? $this->fetchString("rss2:guid", ".+");                       // RSS 2.0 GUID, as string
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
        return $this->fetchDate("atom:updated", self::DATE_LATEST)                  // Atom update date
            ?? $this->fetchDate(self::QUERY_AMBIGUOUS_DATES, self::DATE_LATEST);    // Latest other date
    }

    public function getDateCreated(): ?Date {
        /*  fetching a date works differently from other data as only Atom has
            well-defined semantics here. Thus the semantics of all the other
            formats are equal, and we want the earliest date, but only if
            there are at least two
        */
        return $this->fetchDate("atom:published", self::DATE_EARLIEST)          // Atom creation date
            ?? $this->fetchDate("dct:created|dc:created", self::DATE_LATEST)    // Dublin Core creation date
            ?? $this->getAssumedDateCreated();                                  // Earliest other date
    }

    public function getContent(): ?Text {
        return $this->fetchAtomText("atom:content")                     // Atom content
            ?? $this->fetchText("enc:encoded", self::TEXT_HTML)         // Explicitly encoded HTML content
            ?? $this->fetchText("rss1:description", self::TEXT_LOOSE)   // RSS 1.0 ambiguous text
            ?? $this->fetchText("rss2:description", self::TEXT_LOOSE);  // RSS 2.0 ambiguous text
    }

    public function getSummary(): ?Text {
        return $this->fetchAtomText("atom:summary")                                     // Atom summary
            ?? $this->fetchText("dc:abstract|dct:abstract", self::TEXT_PLAIN)           // Dublin Core abstract
            ?? $this->fetchText("dc:description|dct:description", self::TEXT_PLAIN)     // Dublin Core description
            ?? $this->fetchText("gplay:description", self::TEXT_PLAIN)                  // Google Play podcast description
            ?? $this->fetchText("apple:summary", self::TEXT_PLAIN);                     // iTunes podcast summary
    }

    public function getBanner(): ?Url {
        return null;
    }

    public function getPeople(): PersonCollection {
        // first try getting authors and contributors in the entry itself
        $authors = $this->getAuthors($this->subject);
        $contributors = $this->getContributors($this->subject) ?? new PersonCollection;
        // if there are no authors but there is an Atom <source> element, get both authors and contributors from the source
        if (!$authors) {
            $src = $this->fetchElement("atom:source");
            if ($src) {
                $authors = $this->getAuthors($src) ?? new PersonCollection;
                $srcContributors = $this->getContributors($src) ?? new PersonCollection;
            } else {
                $authors = new PersonCollection;
            }
        }
        // merge all three lists
        return $authors->merge($contributors, $srcContributors ?? new PersonCollection);
    }

    public function getCategories(): CategoryCollection {
        // first try to get categories from the entry itself
        $list = $this->getCategoriesFromNode($this->subject);
        if (!$list) {
            // if there are none, try to get some from the entry's Atom <source> element, if any
            $src = $this->fetchElement("atom:source");
            if ($src) {
                $list = $this->getCategoriesFromNode($src);
            }
        }
        return $list ?? new CategoryCollection;
    }

    public function getEnclosures(): EnclosureCollection {
        return $this->getEnclosuresMediaRss()
            ?? $this->getEnclosuresAtom()
            ?? $this->getEnclosuresRss1()
            ?? $this->getEnclosuresRss2()
            ?? new EnclosureCollection;
    }

    protected function getRelatedLinkDefinitive(): ?url {
        return $this->fetchAtomRelation("related", ["text/html", "application/xhtml+xml"])  // Atom related relation
            ?? $this->fetchUrl("dc:relation|dct:relation")                                  // Dublin Core 'related' term
            ?? $this->fetchUrl("dc:references|dct:references");                             // Dublin Core 'references' term
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
        $guid = $this->fetchUrl(self::QUERY_RSS_PERMALINK);
        if ($link && $guid) {
            if ($link->getScheme() !== $guid->getScheme() || $link->getAuthority() !== $guid->getAuthority()) {
                return [$guid, $link];
            }
        }
        return [$link ?? $guid, null];
    }

    protected function getAssumedDateCreated(): ?Date {
        $dates = $this->fetchDate(self::QUERY_AMBIGUOUS_DATES, self::DATE_ALL);
        if (sizeof($dates) > 1) {
            return $dates[0];
        }
        return null;
    }

    protected function getAuthors(\DOMNode $context): ?PersonCollection {
        return $this->fetchAtomPeople("atom:author", "author", $context)            // Atom authors
            ?? $this->fetchPeople("dc:creator|dct:creator", "author", $context)     // Dublin Core creators
            ?? $this->fetchPeople("rss2:author", "author", $context)                // RSS 2.0 authors
            ?? $this->fetchPeople("gplay:author", "author", $context)               // Google Play authors
            ?? $this->fetchPeople("apple:author", "author", $context);              // iTunes authors
    }

    protected function getContributors(\DOMNode $context): ?PersonCollection {
        return $this->fetchAtomPeople("atom:contributor", "contributor", $context)              // Atom contributors
            ?? $this->fetchPeople("dc:contributor|dct:contributor", "contributor", $context);   // Dublin Core contributors
    }

    protected function getEnclosuresAtom(): ?EnclosureCollection {
        $out = new EnclosureCollection;
        foreach ($this->fetchAtomRelations("enclosure") as $el) {
            $title = $this->fetchString("@title", ".+", false, $el);
            $enc = new Enclosure;
            $enc->url = $this->fetchUrl("@href", $el);
            $enc->type = MimeType::parseLoose($this->fetchString("@type", null, false, $el) ?? "", $enc->url);
            $enc->title = isset($title) ? new Text($title) : null;
            $enc->size = ((int) $this->fetchString("@length", "\d+", false, $el)) ?: null;
            $out[] = $enc;
        }
        return sizeof($out) ? $out : null;
    }

    protected function getEnclosuresMediaRss(): ?EnclosureCollection {
        $out = new EnclosureCollection;
        $entryTitle = $this->fetchTitleMediaRss($this->subject);
        foreach ($this->xpath->query("media:content|media:group", $this->subject) as $node) {
            if ($node->localName === "group") {
                $groupTitle = $this->fetchTitleMediaRss($node) ?? $entryTitle;
                $group = new Enclosure;
                foreach ($this->xpath->query("media:content", $node) as $subNode) {
                    if ($enc = $this->parseMediaRssEnclosure($subNode)) {
                        $enc->title = $enc->title ?? $groupTitle;
                        $group[] = $enc;
                    }
                }
                if (sizeof($group)) {
                    if ($this->fetchString("@isDefault", "(?-i:true)", false, $node)) {
                        $group->preferred = true;
                    }
                    $out[] = $group;
                }
            } else {
                if ($enc = $this->parseMediaRssEnclosure($node)) {
                    $enc->title = $enc->title ?? $entryTitle;
                    $out[] = $enc;
                }
            }
        }
        return sizeof($out) ? $out : null;
    }

    protected function getEnclosuresRss1(): ?EnclosureCollection {
        $out = new EnclosureCollection;
        foreach ($this->xpath->query("rss1file:enclosure", $this->subject) as $el) {
            $url = $this->fetchUrl("@rdf:resource", $el)
                ?? $this->fetchUrl("@rss1file:url", $el)    // the url attribute is deprecated, but still theoretically possible
                ?? $this->fetchUrl("@url", $el);            // the url attribute might also appear in the null namespace
            if ($url) {
                $enc = new Enclosure;
                $enc->url = $url;
                // the enclosure module uses namespaced attributes, but it's conceivable documents might use attributes in the null namespace (which is more usual)
                $enc->type = MimeType::parseLoose($this->fetchString("@rss1file:type", ".+", false, $el) ?? $this->fetchString("@type", ".+", false, $el) ?? "", $enc->url);
                $enc->size = ((int) ($this->fetchString("@rss1file:length", "\d+", false, $el) ?? $this->fetchString("@length", "\d+", false, $el))) ?: null;
                $out[] = $enc;
            }
        }
        return sizeof($out) ? $out : null;
    }

    protected function getEnclosuresRss2(): ?EnclosureCollection {
        $out = new EnclosureCollection;
        foreach ($this->xpath->query("rss2:enclosure", $this->subject) as $el) {
            $url = $this->fetchUrl("@url", $el);
            if ($url) {
                $enc = new Enclosure;
                $enc->url = $url;
                $enc->type = MimeType::parseLoose($this->fetchString("@type", null, false, $el) ?? "", $enc->url);
                $enc->size = ((int) $this->fetchString("@length", "\d+", false, $el)) ?: null;
                $out[] = $enc;
            }
        }
        return sizeof($out) ? $out : null;
    }

    protected function parseMediaRssEnclosure(\DOMElement $node): ?Enclosure {
        assert($node->localName === "content" && $node->namespaceURI === XPath::NS['media']);
        $url = $this->fetchUrl("@url", $node);
        if ($url) {
            $out = new Enclosure;
            $out->url = $url;
            $out->type = MimeType::parseLoose($this->fetchString("@type", ".+", false, $node) ?? "")
                ?? MimeType::parseLoose($this->fetchString("@medium", ".+", false, $node) ?? "")
                ?? MimeType::parseLoose("", $url);
            $out->title = $this->fetchTitleMediaRss($node);
            foreach (self::ENCLOSURE_ATTR_INTEGERS as $prop => $query) {
                $value = (int) $this->fetchString($query, "\d+", false, $node);
                $out->$prop = $value ?: null;
            }
            foreach (self::ENCLOSURE_ATTR_BOOLEANS as $prop => $query) {
                if (!is_null($this->fetchString($query, null, false, $node))) {
                    $out->$prop = true;
                }
            }
            return $out;
        }
        return null;
    }

    protected function fetchTitleMediaRss(\DOMElement $context): ?Text {
        return $this->fetchText("media:title[normalize-space(@type)='html']", "html", $context)
            ?? $this->fetchText("media:title", "plain", $context);
    }
}
