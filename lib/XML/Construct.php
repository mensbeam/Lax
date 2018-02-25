<?php
/** @license MIT
 * Copyright 2018 J. King et al.
 * See LICENSE and AUTHORS files for details */

declare(strict_types=1);
namespace JKingWeb\Lax\XML;

use JKingWeb\Lax\Person\Person;

trait Construct {
    use \JKingWeb\Lax\Construct;

    /** @var \DOMDocument */
    public    $document;
    /** @var \DOMXPath */
    protected $xpath;
    /** @var \DOMElement */
    protected $subject;

    /** Retrieves an element node based on an XPath query */
    protected function fetchElement(string $query, \DOMNode $context = null) {
        $context = $context ?? $this->subject;
        $node = @$this->xpath->query("(".$query.")[1]", $context ?? $this->subject);
        if ($node===false) {
            throw new \Exception("Invalid XPath query: $query");
        }
        return ($node->length) ? $node->item(0) : null;
    }

    /** Retrieves multiple element node based on an XPath query */
    protected function fetchElements(string $query, \DOMNode $context = null): \DOMNodeList {
        return $this->xpath->query($query, $context ?? $this->subject);
    }

    /** Retrieves the trimmed text content of a DOM element based on an XPath query  */
    protected function fetchText(string $query, \DOMNode $context = null) {
        $node = $this->fetchElement($query, $context);
        return ($node) ? $this->trimText($node->textContent) : null;
    }

    /** Retrieves the trimmed text content of multiple DOM elements based on an XPath query  */
    protected function fetchTextMulti(string $query, \DOMNode $context = null) {
        $out = [];
        $nodes = $this->xpath->query($query, $context ?? $this->subject);
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

    /** Resolves a URL contained in a DOM element's atrribute or text 
     * 
     * This automatically performs xml:base and HTML <base> resolution
     * 
     * Specifying the empty string for $attr results in the element content being used as a URL
    */
    protected function resolveNodeUrl(\DOMElement $node = null, string $attr = "", string $ns = null): string {
        $base = $node->baseURI;
        $url = strlen($attr) ? $node->getAttributeNS($ns, $attr) : $this->trimText($node->textContent);
        return $this->resolveURL($url, $base);
    }

    protected function parsePersonAtom(\DOMNode $node) {
        $p = new Person;
        $p->mail = $this->fetchText("./atom:email", $node) ?? "";
        $p->name = $this->fetchText("./atom:name", $node) ?? $p->mail;
        if (!strlen($p->name)) {
            return null;
        }
        $url = $this->fetchElement("./atom:uri", $node);
        if ($url) {
            $p->url = $this->resolveNodeUrl($url);
        }
        $p->role = $node->localName;
        return $p;
    }
}
