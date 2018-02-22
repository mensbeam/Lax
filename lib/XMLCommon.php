<?php
/** @license MIT
 * Copyright 2018 J. King et al.
 * See LICENSE and AUTHORS files for details */

declare(strict_types=1);
namespace JKingWeb\Lax;

abstract class XMLCommon {
    /** @var \DOMDocument */
    public    $document;
    /** @var \DOMXPath */
    protected $xpath;
    /** @var \DOMElement */
    protected $subject;
    protected $base = "";
    
    const NS = [
        'atom'  => "http://www.w3.org/2005/Atom",                   // Atom syndication format                  https://tools.ietf.org/html/rfc4287
        'rss1'  => "http://purl.org/rss/1.0/",                      // RDF site summary 1.0                     http://purl.org/rss/1.0/spec
        'rss0'  => "http://channel.netscape.com/rdf/simple/0.9/",   // RDF Site Summary 0.90                    http://www.rssboard.org/rss-0-9-0
        'dc'    => "http://purl.org/dc/elements/1.1/",              // Dublin Core metadata                     http://purl.org/rss/1.0/modules/dc/
        'sched' => "http://purl.org/rss/1.0/modules/syndication/",  // Syndication schedule extension           http://purl.org/rss/1.0/modules/syndication/
        'enc'   => "http://purl.org/rss/1.0/modules/content/",      // Explicitly encoded content extension     http://purl.org/rss/1.0/modules/content/
        'media' => "http://search.yahoo.com/mrss/",                 // Embedded media extension                 http://www.rssboard.org/media-rss
        // RSS 2.0 does not have a namespace                        // Really Simple Syndication 2.0.11         http://www.rssboard.org/rss-specification
        'rdf'   => "http://www.w3.org/1999/02/22-rdf-syntax-ns#",   // Resource Description Framework
        'xhtml' => "http://www.w3.org/1999/xhtml",                  // XHTML
        'apple' => "http://www.itunes.com/DTDs/Podcast-1.0.dtd"     // iTunes podcasts                          https://help.apple.com/itc/podcasts_connect/#/itcb54353390
    ];
    
    /** Returns an XPath processor with various necessary namespace prefixes defined */
    public static function getXPathProcessor(\DOMDocument $doc): \DOMXPath {
        $proc = new \DOMXPath($doc);
        foreach (self::NS as $prefix => $url) {
            $proc->registerNamespace($prefix, $url);
        }
        return $proc;
    }

    /** Trims plain text and collapses whitespace */
    protected function trimText(string $text): string {
        return trim(preg_replace("<\s{2,}>s", " ", $text));
    }

    /** Takes an HTML string as input and returns a sanitized version of that string
     * 
     * The $outputHtml parameter, when false, outputs only the plain-text content of the sanitized HTML
     */
    protected function sanitizeString(string $markup, bool $outputHtml = true): string {
        if (!preg_match("/<\S/", $markup)) {
            // if the string does not appear to actually contain markup besides entities, we can skip most of the sanitization
            return $outputHtml ? $markup : $this->trimText(html_entity_decode($markup, \ENT_QUOTES | \ENT_HTML5, "UTF-8"));
        } else {
            return "OOK!";
        }
    }

    /** Retrieves an element node based on an XPath query */
    protected function fetchElement(string $query) {
        $node = $this->xpath->query("(".$query.")[1]", $this->subject);
        return ($node->length) ? $node->item(0) : null;
    }

    /** Retrieves multiple element node based on an XPath query */
    protected function fetchElements(string $query) {
        return $this->xpath->query($query, $this->subject);
    }

    /** Retrieves the trimmed text content of a DOM element based on an XPath query  */
    protected function fetchText(string $query) {
        $node = $this->fetchElement($query);
        return ($node) ? $this->trimText($node->textContent) : null;
    }

    /** Retrieves the trimmed text content of multiple DOM elements based on an XPath query  */
    protected function fetchTextMulti(string $query) {
        $out = [];
        $nodes = $this->xpath->query($query, $this->subject);
        foreach ($nodes as $node) {
            $out[] = $this->trimText($node->item(0)->textContent);
        }
        return ($out) ? $out : null;
    }

    /** Retrieves the trimmed plain-text or HTML content of an Atom text construct based on an XPath query */
    protected function fetchTextAtom(string $query, bool $html = false) {
        $node = $this->fetchElement($query);
        if ($node) {
            if (!$node->hasAttribute("type") || $node->getAttribute("type")=="text") {
                return $html ? htmlspecialchars($this->trimText($node->textContent), \ENT_QUOTES | \ENT_HTML5) : $this->trimText($node->textContent);
            } elseif ($node->getAttribute("type")=="xhtml") {
                $node = $node->getElementsByTagNameNS(self::NS['xhtml'], "div")->item(0);
                return $node ? $this->sanitizeElement($node, $html) : null;
            } elseif ($node->getAttribute("type")=="html") {
                return $this->sanitizeString($node->textContent, $html);
            } else {
                return null;
            }
        } else {
            return null;
        }
    }

    /** Returns a node-list of Atom link elements with the desired relation or equivalents.
     * 
     * Links without an href attribute are excluded.
     * 
     * @see https://tools.ietf.org/html/rfc4287#section-4.2.7.2
     */
    protected function fetchAtomRelations(string $rel = ""): \DOMNodeList {
        // FIXME: The XPath evaluation will fail if the relation contains an apostrophe. This is a known and difficult-to-overcome limitation of XPath 1.0 which I consider not worth the effort to address at this time
        if ($rel=="" || $rel=="alternate" || $rel=="http://www.iana.org/assignments/relation/alternate") {
            $cond = "not(@rel) or @rel='' or @rel='alternate' or @rel='http://www.iana.org/assignments/relation/alternate'";
        } elseif (strpos($rel, ":")===false) {
            // FIXME: Checking only for a colon in a link relation is a hack that does not strictly follow IRI rules, but it's adequate for our needs    
            $cond = "@rel='$rel' or @rel='http://www.iana.org/assignments/relation/$rel'";
        } elseif (strlen($rel) > 41 && strpos($rel, "http://www.iana.org/assignments/relation/")===0) {
            $rel = substr($rel, 41);
            $cond = "@rel='$rel' or @rel='http://www.iana.org/assignments/relation/$rel'";
        } else {
            $cond = "@rel='$rel'";
        }
        return $this->xpath->query("./atom:link[@href][$cond]", $this->subject);
    }

    /** Resolves a relative URL against a base URL */
    protected function resolveUrl(string $url, string $base = null): string {
        $base = $base ?? "";
        return \Sabre\Uri\resolve($base, $url);
    }
}
