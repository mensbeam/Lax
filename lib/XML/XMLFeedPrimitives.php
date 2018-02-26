<?php
/** @license MIT
 * Copyright 2018 J. King et al.
 * See LICENSE and AUTHORS files for details */

declare(strict_types=1);
namespace JKingWeb\Lax\XML;

trait XMLFeedPrimitives {

    /** Primitive to fetch an Atom feed summary
     * 
     * Atom does not have a 'description' element like the RSSes, but it does have 'subtitle', which fills roughly the same function
     */
    protected function getSummaryAtom() {
        return $this->fetchTextAtom("atom:subtitle");
    }

    /** Primitive to fetch an RSS feed summary */
    protected function getSummaryRss2() {
        return $this->fetchText("description");
    }

    /** Primitive to fetch an RDF feed summary */
    protected function getSummaryRss1() {
        return $this->fetchText("rss1:description|rss0:description");
    }

    /** Primitive to fetch a Dublin Core feed summary */
    protected function getSummaryDC() {
        return $this->fetchText("dc:description");
    }

    /** Primitive to fetch a podcast summary */
    protected function getSummaryPod() {
        return $this->fetchText("apple:summary|gplay:description") ?? $this->fetchText("apple:subtitle");
    }

    /** Primitive to fetch a collection of people associated with an RSS feed */
    protected function getPeopleRss2() {
        $nodes = $this->fetchElements("managingEditor|webMaster|author");
        if (!$nodes->length) {
            return null;
        }
        $out = new PersonCollection;
        $roles = [
            'managingEditor' => "editor",
            'webMaster'      => "webmaster",
            'author'         => "author",
        ];
        foreach ($nodes as $node) {
            $text = $this->trimText($node->textContent);
            if (strlen($text)) {
                $p = $this->parsePersonText($text);
                $p->role = $roles[$node->localName];
                $out[] = $p;
            }
        }
        return $out;
    }

    /** Primitive to fetch a collection of people associated with an Atom feed */
    protected function getPeopleAtom() {
        $nodes = $this->fetchElements("atom:author|atom:contributor");
        if (!$nodes->length) {
            return null;
        }
        $out = new PersonCollection;
        foreach ($nodes as $node) {
            $p = $this->parsePersonAtom($node);
            if ($p) {
                $out[] = $p;
            }
        }
        return $out;
    }
}
