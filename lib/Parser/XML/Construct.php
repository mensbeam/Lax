<?php
/** @license MIT
 * Copyright 2018 J. King et al.
 * See LICENSE and AUTHORS files for details */

declare(strict_types=1);
namespace MensBeam\Lax\Parser\XML;

use MensBeam\Lax\Person\Person;
use MensBeam\Lax\Person\Collection as PersonCollection;
use MensBeam\Lax\Text;
use MensBeam\Lax\Date;
use MensBeam\Lax\Url;

abstract class Construct {
    use \MensBeam\Lax\Parser\Construct;

    protected const TEXT_LOOSE = "loose";
    protected const TEXT_PLAIN = "plain";
    protected const TEXT_HTML = "html";

    /** @var \DOMDocument */
    protected $document;
    /** @var \DOMXPath */
    protected $xpath;
    /** @var \DOMElement */
    protected $subject;

    /** Retrieves an element node based on an XPath query */
    protected function fetchElement(string $query, \DOMNode $context = null): ?\DOMElement {
        $node = @$this->xpath->query("(".$query.")[1]", $context ?? $this->subject);
        if ($node === false) {
            throw new \Exception("Invalid XPath query: $query"); // @codeCoverageIgnore
        }
        return ($node->length) ? $node->item(0) : null;
    }

    /** Retrieves the trimmed text content of one or more DOM elements based on an XPath query, optionally matching a pattern
     * 
     * Returns null if no suitable nodes were found
     * 
     * @param string $query The XPath query of the nodes to return
     * @param string|null $pattern The pattern to optionally filter matches with. The pattern should not include delimiters or anchors and is always case-insensitive
     * @param bool|null $multi Whether to return multiple results as an array (true) or one result as a string (false, default)
     * @param \DOMNode $context The context node for the XPath query
     * @return string|array|null
      */
    protected function fetchString(string $query, ?string $pattern = null, ?bool $multi = null, ?\DOMNode $context = null) {
        $out = [];
        $pattern = strlen($pattern ?? "") ? "/^(?:".str_replace("/", "\\/", $pattern).")$/i" : "";
        $multi = $multi ?? false;
        $nodes = $this->xpath->query($query, $context ?? $this->subject);
        foreach ($nodes as $node) {
            $t = $this->trimText($node->textContent);
            if (!$pattern || preg_match($pattern, $t)) {
                if (!$multi) {
                    return $t;
                } else {
                    $out[] = $t;
                }
            }
        }
        return ($out) ? $out : null;
    }

    /** Retrieves and parses a date from one or more DOM elements based on an XPath query
     * 
     * Returns null if no suitable nodes were found  
     * 
     * @param string $query The XPath query of the nodes to return
     * @param bool|null $multi Whether to return multiple results as an array (true) or one result as a date object (false, default)
     * @param \DOMNode $context The context node for the XPath query
     * @return \MensBeam\Lax\Date|array|null
     */
    protected function fetchDate(string $query, ?bool $multi = null, \DOMNode $context = null) {
        $out = [];
        foreach((array) $this->fetchString($query, null, true, $context) as $d) {
            if ($d = $this->parseDate($d ?? "")) {
                if (!$multi) {
                    return $d;
                } else {
                    $out[] = $d;
                }
            }
        }
        return $out ?: null;
    }

    /** Returns the first valid URL matching an XPath query. Relative URLs are resolved when possible
     * 
     * @param string $query The XPath query of the node to return
     * @param \DOMNode $context The context node for the XPath query
     */
    protected function fetchUrl(string $query, \DOMNode $context = null): ?Url {
        foreach ($this->xpath->query($query, $context ?? $this->subject) as $node) {
            $url = trim($node->textContent);
            if (strlen($url)) {
                try {
                    return new Url($url, $node->baseURI);
                } catch (\InvalidArgumentException $e) {
                    // don't return a result that doesn't evaluate to a valid URL of some sort
                }
            }
        }
        return null;
    }

    protected function fetchText(string $query, string $format, \DOMNode $context = null): ?Text {
        foreach ($this->xpath->query($query, $context ?? $this->subject) as $node) {
            $data = trim($node->textContent);
            if (strlen($data)) {
                $out = new Text;
                if ($format === "plain") {
                    $data = $this->trimText($data);
                } elseif ($format === "html" || $format === "loose") {
                    $out->htmlBase = strlen($node->baseURI) ? $node->baseURI : null;
                }
                $out->$format = $data;
                return $out;
            }
        }
        return null;
    }

    /** Returns a node-list of Atom link elements with the desired relation or equivalents.
     *
     * Links without an href attribute are excluded.
     *
     * @see https://tools.ietf.org/html/rfc4287#section-4.2.7.2
     */
    protected function fetchAtomRelations(string $rel = "", \DOMNode $context = null): array {
        // normalize the relation
        $custom = false;
        $rel = trim($rel);
        if ($rel === "") {
            $rel = "alternate";
        } elseif (strpos(strtolower($rel), "http://www.iana.org/assignments/relation/") === 0) {
            $rel = substr($rel, 41);
        } elseif (preg_match("<^[a-z\.\-]+$>i", $rel)) {
            $rel = strtolower($rel);
        } else {
            $custom = true;
            $url = (string) new Url($rel);
        }
        // look at all the links that have a non-empty href attribute
        $out = [];
        foreach ($this->xpath->query("atom:link[normalize-space(@href)]", $context ?? $this->subject) as $l) {
            try {
                new Url($l->getAttribute("href"));
            } catch (\InvalidArgumentException $e) {
                // reject any links which do not have valid URLs
                continue;
            }
            $r = trim($l->getAttribute("rel"));
            if ($custom) {
                if ($url === (string) new Url($r)) {
                    $out[] = $l;
                }
            } else {
                $r = trim(strtolower(rawurldecode($r)));
                $r = (strpos($r, "http://www.iana.org/assignments/relation/") === 0) ? substr($r, 41) : $r;
                $r = !strlen($r) ? "alternate" : $r;
                if ($r === $rel) {
                    $out[] = $l;
                }
            }
        }
        return $out;
    }

    /** Returns the first Atom link URL which matches the desired relation, with nearest desired media type, or no media type if none match */
    protected function fetchAtomRelation(string $rel = "", array $mediaTypes = [], \DOMNode $context = null): ?Url {
        // tidy ther list of media types; this orders them worst (0)to best (highest index) and then creates a hashtable
        $mediaTypes = array_flip(array_reverse(array_values(array_unique(array_map(function(string $t) {
            return strtolower(trim($t));
        }, $mediaTypes)))));
        $rels = $this->fetchAtomRelations($rel, $context);
        if ($rels && !$mediaTypes) {
            return new Url($rels[0]->getAttribute("href"), $rels[0]->baseURI);
        }
        $result = array_reduce($rels, function($best, $cur) use ($mediaTypes) {
            $t = trim($cur->getAttribute("type"));
            // absence of media type is acceptable if no better match yet exists
            if (!strlen($t)) {
                if (!$best) {
                    return [$cur, -1]; // any match will rank higher than -1
                }
            }
            $t = $this->parseMediaType($t);
            if ($t) {
                $rank = $mediaTypes[$t] ?? null;
                if (!is_null($rank) && (!$best || $rank > $best[1])) {
                    // if the media type is acceptable and there is currently no candidate or the candidate ranks lower, use the current link
                    return [$cur, $rank];
                }
            }
            return $best;
        });
        return $result ? new Url($result[0]->getAttribute("href"), $result[0]->baseURI) : null;
    }

    protected function fetchAtomText(string $query, \DOMNode $context = null): ?Text {
        $out = new Text;
        $populated = false;
        foreach ($this->xpath->query($query, $context ?? $this->subject) as $node) {
            if ($node->hasAttribute("src")) {
                // ignore any external content
                continue;
            }
            // get the content type; assume "text" if not provided
            $type = trim($node->getAttribute("type"));
            $type = $this->parseMediaType((!strlen($type)) ? "text" : $type);
            if ($type === "text" || $type === "text/plain") {
                if (is_null($out->plain)) {
                    $plain = $this->trimText($node->textContent);
                    if (strlen($plain)) {
                        $out->plain = $plain;
                        $populated = true;
                    }
                }
            } elseif ($type === "html" || $type === "text/html") {
                if (is_null($out->html)) {
                    $html = trim($node->textContent);
                    if (strlen($html)) {
                        $out->html = $html;
                        $out->htmlBase = strlen($node->baseURI) ? $node->baseURI : null;
                        $populated = true;
                    }
                }
            } elseif ($type === "xhtml" || $type === "application/xhtml+xml") {
                if (is_null($out->xhtml) && ($xhtml = $this->fetchElement("xhtml:div", $node))) {
                    $out->xhtml = $xhtml->ownerDocument->saveXML($xhtml);
                    $out->xhtmlBase = strlen($xhtml->baseURI) ? $xhtml->baseURI : null;
                    $populated = true;
                }
            }
        }
        return $populated ? $out : null;
    }

    /** Primitive to fetch an Atom feed/entry identifier */
    protected function getIdAtom(): ?string {
        return $this->fetchString("atom:id", ".+");
    }

    /** Primitive to fetch an RSS feed/entry identifier
     *
     * Using RSS' <guid> for feed identifiers is non-standard, but harmless
     */
    protected function getIdRss2(): ?string {
        return $this->fetchString("guid", ".+");
    }

    /** Primitive to fetch a Dublin Core feed/entry identifier */
    protected function getIdDC(): ?string {
        return $this->fetchString("dc:identifier", ".+");
    }

    protected function getLangXML(): ?string {
        // walk up the tree looking for the nearest language tag
        $el = $this->subject;
        do {
            $out = $this->fetchString("@xml:lang", ".+", false, $el);
            $el = $el->parentNode;
        } while (is_null($out) && $el);
        return $out;
    }

    protected function getLangDC(): ?string {
        return $this->fetchString("dc:language", ".+");
    }

    protected function getLangRss2(): ?string {
        return $this->fetchString("language", ".+");
    }

    protected function getLinkAtom(): ?Url {
        return $this->fetchAtomRelation("alternate", ["text/html", "application/xhtml+xml"]);
    }

    protected function getLinkRss2(): ?Url {
        return $this->fetchUrl("link") ?? $this->fetchUrl("guid[not(@isPermalink) or @isPermalink='true']");
    }

    protected function getLinkRss1(): ?Url {
        return $this->fetchUrl("rss1:link|rss0:link");
    }

    protected function getTitleAtom(): ?Text {
        return $this->fetchAtomText("atom:title");
    }

    protected function getTitleRss1(): ?Text {
        return $this->fetchText("rss1:title|rss0:title", self::TEXT_LOOSE);
    }

    protected function getTitleRss2(): ?Text {
        return $this->fetchText("title", self::TEXT_LOOSE);
    }

    protected function getTitleDC(): ?Text {
        return $this->fetchText("dc:title", self::TEXT_PLAIN);
    }

    protected function getTitlePod(): ?Text {
        return $this->fetchText("apple:title", self::TEXT_PLAIN);
    }
    
}
