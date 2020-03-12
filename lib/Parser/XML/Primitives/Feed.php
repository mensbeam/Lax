<?php
/** @license MIT
 * Copyright 2018 J. King et al.
 * See LICENSE and AUTHORS files for details */

declare(strict_types=1);
namespace MensBeam\Lax\Parser\XML\Primitives;

use MensBeam\Lax\Parser\XML\XPath;

trait Feed {
    /** Primitive to fetch an Atom feed summary
     *
     * Atom does not have a 'description' element like the RSSes, but it does have 'subtitle', which fills roughly the same function
     */
    protected function getSummaryAtom() {
        return $this->fetchStringAtom("atom:subtitle");
    }

    /** Primitive to fetch an RSS feed summary */
    protected function getSummaryRss2() {
        return $this->fetchString("description");
    }

    /** Primitive to fetch an RDF feed summary */
    protected function getSummaryRss1() {
        return $this->fetchString("rss1:description|rss0:description");
    }

    /** Primitive to fetch a Dublin Core feed summary */
    protected function getSummaryDC() {
        return $this->fetchString("dc:description");
    }

    /** Primitive to fetch a podcast summary */
    protected function getSummaryPod() {
        return $this->fetchString("apple:summary|gplay:description") ?? $this->fetchString("apple:subtitle");
    }

    /** Primitive to fetch a collection of authors associated with an Atom feed */
    protected function getAuthorsAtom() {
        return $this->fetchPeopleAtom("atom:author", "author");
    }

    /** Primitive to fetch an RDF feed's canonical URL */
    protected function getUrlRss1() {
        // XPath doesn't seem to like the query we'd need for this, so it must be done the hard way.
        $node = $this->subject;
        if ($node->hasAttributeNS(XPath::NS['rdf'], "about")) {
            if (
                ($node->localName === "channel" && ($node->namespaceURI === XPath::NS['rss1'] || $node->namespaceURI === XPath::NS['rss0'])) ||
                ($node === $node->ownerDocument->documentElement && $node->localName === "RDF" && $node->namespaceURI === XPath::NS['rdf'])
            ) {
                return $this->resolveNodeUrl($node, "about", XPath::NS['rdf']);
            }
        }
        return null;
    }

    /** Primitive to fetch a podcast's canonical URL */
    protected function getUrlPod() {
        return $this->fetchUrl("apple:new-feed-url");
    }

    /** Primitive to fetch the modification date of an RSS feed */
    protected function getDateModifiedRss2() {
        return $this->fetchDate("lastBuildDate") ?? $this->fetchDate("pubDate");
    }

    protected function getExpiredPod(): ?bool {
        $complete = $this->fetchString("apple:complete");
        if ($complete === "Yes") {
            return true;
        }
        return null;
    }
}
