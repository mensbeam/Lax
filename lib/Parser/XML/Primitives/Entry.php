<?php
/** @license MIT
 * Copyright 2018 J. King et al.
 * See LICENSE and AUTHORS files for details */

declare(strict_types=1);
namespace JKingWeb\Lax\Parser\XML\Primitives;

use JKingWeb\Lax\Parser\XML\XPath;

trait Entry {
    /** Primitive to fetch a collection of authors associated with an Atom entry
     *
     * This differs from feeds in that an entry's <source> element (which possibly contains metadata for the source feed) is checked for authors if the entry itself has none
     */
    protected function getAuthorsAtom() {
        return $this->fetchPeopleAtom("atom:author", "author") ?? $this->fetchPeopleAtom("atom:source[1]/atom:author", "author");
    }

    /** Primitive to fetch an RDF entry's canonical URL */
    protected function getUrlRss1() {
        // XPath doesn't seem to like the query we'd need for this, so it must be done the hard way.
        $node = $this->subject;
        if ($node->localName === "item" && ($node->namespaceURI === XPath::NS['rss1'] || $node->namespaceURI == XPath::NS['rss0']) && $node->hasAttributeNS(XPath::NS['rdf'], "about")) {
            return $this->resolveNodeUrl($node, "about", XPath::NS['rdf']);
        } else {
            return null;
        }
    }

    /** Primitive to fetch the modification date of an RSS feed */
    protected function getDateModifiedRss2() {
        return $this->fetchDate("pubDate");
    }
}
