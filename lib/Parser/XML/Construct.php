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
}
