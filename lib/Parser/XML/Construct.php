<?php
/** @license MIT
 * Copyright 2018 J. King et al.
 * See LICENSE and AUTHORS files for details */

declare(strict_types=1);
namespace JKingWeb\Lax\Parser\XML;

use JKingWeb\Lax\Person\Person;
use JKingWeb\Lax\Person\Collection as PersonCollection;

trait Construct {
    use \JKingWeb\Lax\Parser\Construct;

    /** @var \DOMDocument */
    public    $document;
    /** @var \DOMXPath */
    protected $xpath;
    /** @var \DOMElement */
    protected $subject;

    /** Retrieves an element node based on an XPath query */
    protected function fetchElement(string $query, \DOMNode $context = null) {
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
            $out[] = $this->trimText($node->textContent);
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

    /** Retrieves and parses a date from the content of a DOM element based on an XPath query  */
    protected function fetchDate(string $query, \DOMNode $context = null) {
        return $this->parseDate($this->fetchText($query, $context) ?? "");
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
        return $this->xpath->query("atom:link[@href][$cond]", $this->subject);
    }

    /** Finds and parses RSS person-texts and returns a collection of person objects
     * 
     * Each can have a name, e-mail address, or both
     * 
     * The following forms will yield both a name and address:
     * 
     * - user@example.com (Full Name)
     * - Full Name <user@example.com>
     */
    protected function fetchPeople(string $query, string $role) {
        $people = $this->fetchTextMulti($query) ?? [];
        $out = new PersonCollection;
        foreach ($people as $person) {
            if (!strlen($person)) {
                continue;
            }
            $p = new Person;
            if (preg_match("/^([^@\s]+@\S+) \((.+?)\)$/", $person, $match)) { // tests "user@example.com (Full Name)" form
                if ($this->validateMail($match[1])) {
                    $p->name = trim($match[2]);
                    $p->mail = $match[1];
                } else {
                    $p->name = $person;
                }
            } elseif (preg_match("/^((?:\S|\s(?!<))+) <([^>]+)>$/", $person, $match)) { // tests "Full Name <user@example.com>" form
                if ($this->validateMail($match[2])) {
                    $p->name = trim($match[1]);
                    $p->mail = $match[2];
                } else {
                    $p->name = $person;
                }
            } elseif ($this->validateMail($person)) {
                $p->name = $person;
                $p->mail = $person;
            } else {
                $p->name = $person;
            }
            $p->role = $role;
            $out[] = $p;
        }
        return count($out) ? $out : null;
    }

    /** Finds and parses Atom person-constructs, and returns a collection of Person objects */
    protected function fetchPeopleAtom(string $query, string $role) {
        $nodes = $this->fetchElements($query);
        $out = new PersonCollection;
        foreach ($nodes as $node) {
            $p = new Person;
            $p->mail = $this->fetchText("atom:email", $node) ?? "";
            $p->name = $this->fetchText("atom:name", $node) ?? $p->mail;
            $p->url = $this->fetchUrl("atom:uri", $node);
            $p->role = $role;
            if (strlen($p->name)) {
                $out[] = $p;
            }
        }
        return count($out) ? $out : null;
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
        return $this->resolveUrl($url, $base);
    }

    protected function fetchUrl(string $query, \DOMElement $context = null, string $attr = "", string $ns = null) {
        $nodes = $this->fetchElements($query, $context);
        foreach ($nodes as $node) {
            $url = strlen($attr) ? $node->getAttributeNS($ns, $attr) : $this->trimText($node->textContent);
            $url = $this->trimText($node->textContent);
            if (strlen($url)) {
                return $this->resolveUrl($url, $node->baseURI);
            }
        }
        return null;
    }
}
