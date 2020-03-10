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
        foreach ($this->fetchStringMulti("dc:subject") ?? [] as $text) {
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

    /** Primitive to fetch an Atom feed/entry identifier */
    protected function getIdAtom(): ?string {
        return $this->fetchString("atom:id");
    }

    /** Primitive to fetch an RSS feed/entry identifier
     *
     * Using RSS' <guid> for feed identifiers is non-standard, but harmless
     */
    protected function getIdRss2(): ?string {
        return $this->fetchString("guid");
    }

    /** Primitive to fetch a Dublin Core feed/entry identifier */
    protected function getIdDC(): ?string {
        return $this->fetchString("dc:identifier");
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
