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
        $this->link = $this->getLink();
        $this->title = $this->getTitle() ?? $this->link;
        $this->summary = $this->getSummary();
    }
    
    /** General function to fetch the feed title */
    public function getTitle() {
        return $this->getTitleAtom() ?? $this->getTitleRss1() ?? $this->getTitleRss2() ?? $this->getTitleDC() ?? $this->getTitleApple();
    }

    /** General function to fetch the feed's Web-representation URL */
    public function getLink() {
        return $this->getLinkAtom() ?? $this->getLinkRss1() ?? $this->getLinkRss2();
    }

    /** General function to fetch the description of a feed */
    public function getSummary() {
        // unlike most other data, Atom is not preferred, because Atom doesn't really have feed summaries
        return $this->getSummaryDC() ?? $this->getSummaryRss1() ?? $this->getSummaryRss2() ?? $this->getSummaryApple() ?? $this->getSummaryAtom();
    }

    /** General function to fetch the categories of a feed 
     * 
     * If the $grouped parameter is true, and array of arrays will be returned, keyed by taxonomy/scheme
     * 
     * The $humanFriendly parameter only affects Atom categories
    */
    public function getCategories(bool $grouped = false, bool $humanFriendly = true) {
        return $this->getCategoriesAtom($grouped, $humanFriendly) ?? $this->getCategoriesRss2($grouped, $humanFriendly) ?? $this->getCategoriesDC($grouped, $humanFriendly) ?? $this->getCategoriesApple($grouped, $humanFriendly);
    }
}
