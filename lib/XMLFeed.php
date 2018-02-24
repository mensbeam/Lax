<?php
/** @license MIT
 * Copyright 2018 J. King et al.
 * See LICENSE and AUTHORS files for details */

declare(strict_types=1);
namespace JKingWeb\Lax;

class XMLFeed extends XMLCommon {
    use XMLCommonPrimitives;
    use XMLFeedPrimitives;
    
    public $type;
    public $version;
    public $url;
    public $link;
    public $title;
    public $summary;
    public $categories;
    public $people;
    public $author;

    /** Returns a parsed feed */
    public function __construct(string $data, string $contentType = null, string $url = null) {
        $this->init($data, $contentType, $url);
        $this->parse();
    }

    /** Performs initialization of the instance */
    protected function init(string $data, string $contentType = null, string $url = null) {
        $this->document = new \DOMDocument();
        $this->document->loadXML($data, \LIBXML_BIGLINES | \LIBXML_COMPACT);
        $this->document->documentURI = $url;
        $this->xpath = self::getXPathProcessor($this->document);
        $this->subject = $this->document->documentElement;
        $ns = $this->subject->namespaceURI;
        $name = $this->subject->localName;
        if (is_null($ns) && $name=="rss") {
            $this->subject = $this->fetchElement("./channel") ?? $this->subject;
            $this->type = "rss";
            $this->version = $this->document->documentElement->getAttribute("version");
        } elseif ($ns==self::NS['rdf'] && $name=="RDF") {
            $this->type = "rdf";
            $channel = $this->fetchElement("./rss1:channel|./rss0:channel");
            if ($channel) {
                $this->subject = $channel;
                $this->version = ($channel->namespaceURI==self::NS['rss1']) ? "1.0" : "0.90";
            } else {
                 $element = $this->fetchElement("./rss1:item|./rss0:item|./rss1:image|./rss0:image");
                 if ($element) {
                     $this->version = ($element->namespaceURI==self::NS['rss1']) ? "1.0" : "0.90";
                 }
            }
        } elseif ($ns==self::NS['atom'] && $name=="feed") {
            $this->type = "atom";
            $this->version = "1.0";
        } else {
            throw new \Exception;
        }
        $this->url = $url;
        
    }

    /** Parses the feed to extract sundry metadata */
    protected function parse() {
        $this->id = $this->getId();
        $this->link = $this->getLink();
        $this->title = $this->getTitle();
        $this->summary = $this->getSummary();
        $this->people = $this->getPeople();
        $this->author = $this->people->primary();
        // do a second pass on missing data we'd rather fill in
        $this->link = strlen($this->link) ? $this->link : $this->url;
        $this->title = strlen($this->title) ? $this->title : $this->link;
    }
    
    /** General function to fetch the feed title */
    public function getTitle(): string {
        return $this->getTitleAtom() ?? $this->getTitleRss1() ?? $this->getTitleRss2() ?? $this->getTitleDC() ?? $this->getTitlePod() ?? "";
    }

    /** General function to fetch the feed's Web-representation URL */
    public function getLink(): string {
        return $this->getLinkAtom() ?? $this->getLinkRss1() ?? $this->getLinkRss2() ?? "";
    }

    /** General function to fetch the description of a feed */
    public function getSummary(): string {
        // unlike most other data, Atom is not preferred, because Atom doesn't really have feed summaries
        return $this->getSummaryDC() ?? $this->getSummaryRss1() ?? $this->getSummaryRss2() ?? $this->getSummaryPod() ?? $this->getSummaryAtom() ?? "";
    }

    /** General function to fetch the categories of a feed 
     * 
     * If the $grouped parameter is true, and array of arrays will be returned, keyed by taxonomy/scheme
     * 
     * The $humanFriendly parameter only affects Atom categories
    */
    public function getCategories(bool $grouped = false, bool $humanFriendly = true): array {
        return $this->getCategoriesAtom($grouped, $humanFriendly) ?? $this->getCategoriesRss2($grouped, $humanFriendly) ?? $this->getCategoriesDC($grouped, $humanFriendly) ?? $this->getCategoriesPod($grouped, $humanFriendly) ?? [];
    }

    /** General function to fetch the feed identifier */
    public function getId(): string {
        return $this->getIdAtom() ?? $this->getIdDC() ?? $this->getIdRss2() ?? "";
    }

    /** General function to fetch a collection of people associated with a feed */
    public function getPeople(): PersonCollection {
        $out = $this->getPeopleAtom() ?? $this->getPeopleDC() ?? $this->getPeoplePod();
        if (!$out) {
            // if no Atom, Dublin Core, or podcast people were found, return any available RSS people
            return $this->getPeopleRss2() ?? new PersonCollection;
        } elseif ($out->primary()->role != "author") {
            // if none of the people found are an author (i.e. there are only contributors), add any available Podcast authors first
            $more = $this->getPeoplePod();
            if (!$more) {
                // if no podcast author was found, add any available RSS people
                $more = $this->getPeopleRss2() ?? new PersonCollection;
            } else {
                // otherwise add only non-author RSS people to the podcast people
                $more = $more->merge(($this->getPeopleRss2() ?? new PersonCollection)->filterOutRole("author"));
            }
            // and finally add any additional people found to the contributor list
            return $out->merge($more);
        } else {
            // if the search for Atom, DC and postcast people -did- find an author, add only non-author RSS people (i.e. editors and webmasters)
            return $out->merge(($this->getPeopleRss2() ?? new PersonCollection)->filterOutRole("author"));
        }
    }
}
