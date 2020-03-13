<?php
/** @license MIT
 * Copyright 2018 J. King et al.
 * See LICENSE and AUTHORS files for details */

declare(strict_types=1);
namespace MensBeam\Lax\Parser\XML\Primitives;

use MensBeam\Lax\Person\Person;
use MensBeam\Lax\Person\Collection as PersonCollection;
use MensBeam\Lax\Category\Category;
use MensBeam\Lax\Category\Collection as CategoryCollection;
use MensBeam\Lax\Date;
use MensBeam\Lax\Parser\XML\Entry as FeedEntry;
use MensBeam\Lax\Text;
use MensBeam\Lax\Url;

trait Construct {
    /** Retrieves multiple element node based on an XPath query */
    protected function fetchElements(string $query, \DOMNode $context = null): \DOMNodeList {
        return $this->xpath->query($query, $context ?? $this->subject);
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

    /** Returns a node-list of Atom link elements with the desired relation or equivalents.
     *
     * Links without an href attribute are excluded.
     *
     * @see https://tools.ietf.org/html/rfc4287#section-4.2.7.2
     */
    protected function fetchAtomRelations(string $rel = ""): \DOMNodeList {
        // FIXME: The XPath evaluation will fail if the relation contains an apostrophe. This is a known and difficult-to-overcome limitation of XPath 1.0 which I consider not worth the effort to address at this time
        if ($rel == "" || $rel == "alternate" || $rel == "http://www.iana.org/assignments/relation/alternate") {
            $cond = "not(@rel) or @rel='' or @rel='alternate' or @rel='http://www.iana.org/assignments/relation/alternate'";
        } elseif (strpos($rel, ":") === false) {
            // FIXME: Checking only for a colon in a link relation is a hack that does not strictly follow IRI rules, but it's adequate for our needs
            $cond = "@rel='$rel' or @rel='http://www.iana.org/assignments/relation/$rel'";
        } elseif (strlen($rel) > 41 && strpos($rel, "http://www.iana.org/assignments/relation/") === 0) {
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
    protected function fetchPeople(string $query, string $role): ?PersonCollection {
        $people = $this->fetchString($query, null, true) ?? [];
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
    protected function fetchPeopleAtom(string $query, string $role): ?PersonCollection {
        $nodes = $this->fetchElements($query);
        $out = new PersonCollection;
        foreach ($nodes as $node) {
            $p = new Person;
            $p->mail = $this->fetchString("atom:email", $node) ?? "";
            $p->name = $this->fetchString("atom:name", $node) ?? $p->mail;
            $p->url = $this->fetchUrl("atom:uri", $node);
            $p->role = $role;
            if (strlen($p->name)) {
                $out[] = $p;
            }
        }
        return count($out) ? $out : null;
    }

    /** Primitive to fetch an Atom feed/entry title
     */
    protected function getTitleAtom(): ?Text {
        // FIXME: fetch rich text
        return $this->fetchStringAtom("atom:title");
    }

    /** Primitive to fetch an RSS feed/entry title */
    protected function getTitleRss2(): ?Text {
        return $this->fetchString("title");
    }

    /** Primitive to fetch an RDF feed/entry title */
    protected function getTitleRss1(): ?Text {
        return $this->fetchString("rss1:title|rss0:title");
    }

    /** Primitive to fetch a Dublin Core feed/entry title */
    protected function getTitleDC(): ?Text {
        return $this->fetchString("dc:title");
    }

    /** Primitive to fetch an Apple podcast/episdoe title */
    protected function getTitlePod(): ?Text {
        return $this->fetchString("apple:title");
    }

    /** Primitive to fetch an Atom feed/entry Web-representation URL */
    protected function getLinkAtom(): ?Url {
        // FIXME: Atom link fetching should ideally prefer links to text/html resources or the like over e.g. other-format newsfeeds, generic XML, images, etc
        $node = $this->fetchAtomRelations();
        return $node->length ? $this->resolveNodeUrl($node->item(0), "href") : null;
    }

    /** Primitive to fetch an RSS feed/entry Web-representation URL */
    protected function getLinkRss2(): ?Url {
        return $this->fetchUrl("link") ?? $this->fetchUrl("guid[not(@isPermalink='false')]");
    }

    /** Primitive to fetch an RDF feed/entry Web-representation URL */
    protected function getLinkRss1(): ?Url {
        return $this->fetchUrl("rss1:link|rss0:link");
    }

    /** Primitive to fetch Atom feed/entry categories */
    protected function getCategoriesAtom(): ?CategoryCollection {
        $out = new CategoryCollection;
        foreach ($this->fetchElements("atom:category[@term]") ?? [] as $node) {
            $c = new Category;
            $c->domain = $this->trimText($node->getAttribute("scheme"));
            $c->label = $this->trimText($node->getAttribute("label"));
            $c->name = $this->trimText($node->getAttribute("term"));
            if (strlen($c->name)) {
                $out[] = $c;
            }
        }
        return count($out) ? $out : null;
    }

    /** Primitive to fetch RSS feed/entry categories */
    protected function getCategoriesRss2(): ?CategoryCollection {
        $out = new CategoryCollection;
        foreach ($this->fetchElements("category") ?? [] as $node) {
            $c = new Category;
            $c->domain = $this->trimText($node->getAttribute("domain"));
            $c->name = $this->trimText($node->textContent);
            if (strlen($c->name)) {
                $out[] = $c;
            }
        }
        return count($out) ? $out : null;
    }

    /** Primitive to fetch Dublin Core feed/entry categories
     *
     * Dublin Core doesn't have an obvious category type, so we use 'subject' as a nearest approximation
     */
    protected function getCategoriesDC(): ?CategoryCollection {
        $out = new CategoryCollection;
        foreach ($this->fetchString("dc:subject", null, true) ?? [] as $text) {
            if (strlen($text)) {
                $c = new Category;
                $c->name = $text;
                $out[] = $c;
            }
        }
        return count($out) ? $out : null;
    }

    /** Primitive to fetch podcast/episode categories */
    protected function getCategoriesPod(): ?CategoryCollection {
        $out = new CategoryCollection;
        foreach ($this->fetchElements("apple:category|gplay:category") ?? [] as $node) {
            $c = new Category;
            $c->name = $this->trimText($node->getAttribute("text"));
            if (strlen($c->name)) {
                $out[] = $c;
            }
        }
        return count($out) ? $out : null;
    }

    /** Primitive to fetch a collection of authors associated with a feed/entry via Dublin Core */
    protected function getAuthorsDC(): ?PersonCollection {
        return $this->fetchPeople("dc:creator", "author");
    }

    /** Primitive to fetch a collection of contributors associated with a feed/entry via Dublin Core */
    protected function getContributorsDC(): ?PersonCollection {
        return $this->fetchPeople("dc:ccontributor", "contributor");
    }

    /** Primitive to fetch a collection of authors associated with an RSS feed/entry */
    protected function getAuthorsRss2(): ?PersonCollection {
        return $this->fetchPeople("author", "author");
    }

    /** Primitive to fetch a collection of editors associated with an RSS feed/entry */
    protected function getEditorsRss2(): ?PersonCollection {
        return $this->fetchPeople("managingEditor", "editor");
    }

    /** Primitive to fetch a collection of authors associated with an RSS feed/entry */
    protected function getWebmastersRss2(): ?PersonCollection {
        return $this->fetchPeople("webMaster", "webMaster");
    }

    /** Primitive to fetch a collection of contributors associated with an Atom feed */
    protected function getContributorsAtom(): ?PersonCollection {
        return $this->fetchPeopleAtom("atom:contributor", "contributor");
    }

    /** Primitive to fetch a collection of authors associated with a podcast/episode
     *
     * The collection only ever contains the first author found: podcasts implicitly have only one author
     */
    protected function getAuthorsPod(): ?PersonCollection {
        $out = new PersonCollection;
        $p = new Person;
        $p->name = $this->fetchString("gplay:author|apple:author") ?? "";
        $p->mail = $this->fetchString("gplay:email|apple:email") ?? "";
        $p->role = "author";
        if (strlen($p->name)) {
            $out[] = $p;
        }
        return count($out) ? $out : null;
    }

    /** Primitive to fetch a collection of webmasters associated with a podcast
     *
     * The collection only ever contains the first webmaster found: podcasts implicitly have only one webmaster
     */
    protected function getWebmastersPod(): ?PersonCollection {
        $out = new PersonCollection;
        $node = $this->fetchElement("gplay:owner|apple:owner");
        if ($node) {
            $p = new Person;
            $p->name = $this->fetchString("gplay:author|apple:author", $node) ?? "";
            $p->mail = $this->fetchString("gplay:email|apple:email", $node) ?? "";
            $p->role = "webmaster";
            if (strlen($p->name)) {
                $out[] = $p;
            }
        }
        return count($out) ? $out : null;
    }

    /** Primitive to fetch an Atom feed or entry's canonical URL */
    protected function getUrlAtom(): ?Url {
        $node = $this->fetchAtomRelations("self");
        return $node->length ? $this->resolveNodeUrl($node->item(0), "href") : null;
    }

    /** Primitive to fetch the modification date of an Atom feed/entry */
    protected function getDateModifiedAtom(): ?Date {
        return $this->fetchDate("atom:updated");
    }

    /** Primitive to fetch the modification date of an Atom feed/entry */
    protected function getDateModifiedDC(): ?Date {
        return $this->fetchDate("dc:date");
    }

    /** Primitive to fetch the modification date of an Atom entry */
    protected function getDateCreatedAtom(): ?Date {
        return $this->fetchDate("atom:published");
    }

    /** Primitive to fetch the list of entries in an Atom feed */
    protected function getEntriesAtom(): ?array {
        $out = [];
        foreach ($this->fetchElements("atom:entry") ?? [] as $node) {
            $out[] = new FeedEntry($node, $this, $this->xpath);
        }
        return count($out) ? $out : null;
    }

    /** Primitive to fetch the list of entries in an RDF feed */
    protected function getEntriesRss1(): ?array {
        $out = [];
        foreach ($this->fetchElements("rss1:item", $this->subject->ownerDocument->documentElement) ?? $this->fetchElements("rss1:item") ?? $this->fetchElements("rss0:item", $this->subject->ownerDocument->documentElement) ?? $this->fetchElements("rss0:item") ?? [] as $node) {
            $out[] = new FeedEntry($node, $this, $this->xpath);
        }
        return count($out) ? $out : null;
    }

    /** Primitive to fetch the list of entries in an RSS feed */
    protected function getEntriesRss2(): ?array {
        $out = [];
        foreach ($this->fetchElements("item") ?? [] as $node) {
            $out[] = new FeedEntry($node, $this, $this->xpath);
        }
        return count($out) ? $out : null;
    }

    /** Primitive to fetch the URL of a article related to the entry */
    protected function getRelatedLinkAtom(): ?Url {
        // FIXME: Atom link fetching should ideally prefer links to text/html resources or the like over e.g. other-format newsfeeds, generic XML, images, etc
        $node = $this->fetchAtomRelations("related");
        return $node->length ? $this->resolveNodeUrl($node->item(0), "href") : null;
    }
}
