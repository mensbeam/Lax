<?php
/** @license MIT
 * Copyright 2018 J. King et al.
 * See LICENSE and AUTHORS files for details */

declare(strict_types=1);
namespace JKingWeb\Lax\XML\Primitives;

use JKingWeb\Lax\Person\Person;
use JKingWeb\Lax\Person\Collection as PersonCollection;
use JKingWeb\Lax\Category\Category;
use JKingWeb\Lax\Category\Collection as CategoryCollection;
use JKingWeb\Lax\XML\Entry as FeedEntry;

trait Construct {

    /** Primitive to fetch an Atom feed/entry title
     * 
     * This fetches the title in plain text rather than HTML, even if HTML is provided in the feed/entry
     */
    protected function getTitleAtom() {
        return $this->fetchTextAtom("atom:title");
    }

    /** Primitive to fetch an RSS feed/entry title */
    protected function getTitleRss2() {
        return $this->fetchText("title");
    }

    /** Primitive to fetch an RDF feed/entry title */
    protected function getTitleRss1() {
        return $this->fetchText("rss1:title|rss0:title");
    }

    /** Primitive to fetch a Dublin Core feed/entry title */
    protected function getTitleDC() {
        return $this->fetchText("dc:title");
    }

    /** Primitive to fetch an Apple podcast/episdoe title */
    protected function getTitlePod() {
        return $this->fetchText("apple:title");
    }

    /** Primitive to fetch an Atom feed/entry Web-representation URL */
    protected function getLinkAtom() {
        // FIXME: Atom link fetching should ideally prefer links to text/html resources or the like over e.g. other-format newsfeeds, generic XML, images, etc
        $node = $this->fetchAtomRelations();
        return $node->length ? $this->resolveNodeUrl($node->item(0), "href") : null;
    }

    /** Primitive to fetch an RSS feed/entry Web-representation URL */
    protected function getLinkRss2() {
        $node = $this->fetchElement("link");
        return $node ? $this->resolveNodeUrl($node) : null;
    }

    /** Primitive to fetch an RDF feed/entry Web-representation URL */
    protected function getLinkRss1() {
        $node = $this->fetchElement("rss1:link|rss0:link");
        return $node ? $this->resolveNodeUrl($node) : null;
    }

    /** Primitive to fetch Atom feed/entry categories */
    protected function getCategoriesAtom() {
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
    protected function getCategoriesRss2() {
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
    protected function getCategoriesDC() {
        $out = new CategoryCollection;
        foreach ($this->fetchTextMulti("dc:subject") ?? [] as $text) {
            if (strlen($ctext)) {
                $c = new Category;
                $c->name = $text;
                $out[] = $c;
            }
        }
        return count($out) ? $out : null;
    }

    /** Primitive to fetch podcast/episode categories */
    protected function getCategoriesPod() {
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

    /** Primitive to fetch an Atom feed/entry identifier */
    protected function getIdAtom() {
        return $this->fetchText("atom:id");
    }

    /** Primitive to fetch an RSS feed/entry identifier 
     * 
     * Using RSS' <guid> for feed identifiers is non-standard, but harmless
    */
    protected function getIdRss2() {
        return $this->fetchText("guid");
    }

    /** Primitive to fetch a Dublin Core feed/entry identifier */
    protected function getIdDC() {
        return $this->fetchText("dc:identifier");
    }

    /** Primitive to fetch a collection of authors associated with a feed/entry via Dublin Core */
    protected function getAuthorsDC() {
        return $this->fetchPeople("dc:creator", "author");
    }

    /** Primitive to fetch a collection of contributors associated with a feed/entry via Dublin Core */
    protected function getContributorsDC() {
        return $this->fetchPeople("dc:ccontributor", "contributor");
    }

    /** Primitive to fetch a collection of authors associated with an RSS feed/entry */
    protected function getAuthorsRss2() {
        return $this->fetchPeople("author", "author");
    }

    /** Primitive to fetch a collection of editors associated with an RSS feed/entry */
    protected function getEditorsRss2() {
        return $this->fetchPeople("managingEditor", "editor");
    }

    /** Primitive to fetch a collection of authors associated with an RSS feed/entry */
    protected function getWebmastersRss2() {
        return $this->fetchPeople("webMaster", "webMaster");
    }

    /** Primitive to fetch a collection of contributors associated with an Atom feed */
    protected function getContributorsAtom() {
        return $this->fetchPeopleAtom("atom:contributor", "contributor");
    }

    /** Primitive to fetch a collection of authors associated with a podcast/episode 
     * 
     * The collection only ever contains the first author found: podcasts implicitly have only one author
    */
    protected function getAuthorsPod() {
        $out = new PersonCollection;
        $p = new Person;
        $p->name = $this->fetchText("gplay:author|apple:author") ?? "";
        $p->mail = $this->fetchText("gplay:email|apple:email") ?? "";
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
    protected function getWebmastersPod() {
        $out = new PersonCollection;
        $node = $this->fetchElement("gplay:owner|apple:owner");
        if ($node) {
            $p = new Person;
            $p->name = $this->fetchText("gplay:author|apple:author", $node) ?? "";
            $p->mail = $this->fetchText("gplay:email|apple:email", $node) ?? "";
            $p->role = "webmaster";
            if (strlen($p->name)) {
                $out[] = $p;
            }
        }
        return count($out) ? $out : null;
    }

    /** Primitive to fetch an Atom feed or entry's canonical URL */
    protected function getUrlAtom() {
        $node = $this->fetchAtomRelations("self");
        return $node->length ? $this->resolveNodeUrl($node->item(0), "href") : null;
    }

    /** Primitive to fetch the modification date of an Atom feed/entry */
    protected function getDateModifiedAtom() {
        return $this->fetchDate("atom:updated");
    }

    /** Primitive to fetch the modification date of an Atom feed/entry */
    protected function getDateModifiedDC() {
        return $this->fetchDate("dc:date");
    }

    /** Primitive to fetch the modification date of an Atom entry */
    protected function getDateCreatedAtom() {
        return $this->fetchDate("atom:published");
    }

    /** Primitive to fetch the list of entries in an Atom feed */
    protected function getEntriesAtom() {
        $out = [];
        foreach ($this->fetchElements("atom:entry") ?? [] as $node) {
            $out[] = new FeedEntry($node, $this, $this->xpath);
        }
        return count($out) ? $out : null;
    }

    /** Primitive to fetch the list of entries in an RDF feed */
    protected function getEntriesRss1() {
        $out = [];
        foreach ($this->fetchElements("rss1:item|rss0:item", $this->subject->ownerDocument->documentElement) ?? $this->fetchElements("rss1:item|rss0:item") ?? [] as $node) {
            $out[] = new FeedEntry($node, $this, $this->xpath);
        }
        return count($out) ? $out : null;
    }

    /** Primitive to fetch the list of entries in an RSS feed */
    protected function getEntriesRss2() {
        $out = [];
        foreach ($this->fetchElements("item") ?? [] as $node) {
            $out[] = new FeedEntry($node, $this, $this->xpath);
        }
        return count($out) ? $out : null;
    }
}
