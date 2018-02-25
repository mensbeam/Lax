<?php
/** @license MIT
 * Copyright 2018 J. King et al.
 * See LICENSE and AUTHORS files for details */

declare(strict_types=1);
namespace JKingWeb\Lax\XML\Primitives;

use JKingWeb\Lax\Person\Collection as PersonCollection;
use JKingWeb\Lax\XML\XPath;

trait Entry {

    /** Primitive to fetch a collection of people associated with an RSS entry */
    protected function getPeopleRss2() {
        $nodes = $this->fetchElements("./author");
        if (!$nodes->length) {
            return null;
        }
        $out = new PersonCollection;
        foreach ($nodes as $node) {
            $text = $this->trimText($node->textContent);
            if (strlen($text)) {
                $p = $this->parsePersonText($text);
                $p->role = $node->localName;
                $out[] = $p;
            }
        }
        return $out;
    }

    /** Primitive to fetch a collection of people associated with an Atom entry */
    protected function getPeopleAtom() {
        $nodes = $this->fetchElements("./atom:author|./atom:contributor");
        $out = new PersonCollection;
        foreach ($nodes as $node) {
            $p = $this->parsePersonAtom($node);
            if ($p) {
                $out[] = $p;
            }
        }
        $primary = $out->primary();
        // if the entry has no author, we retrieve the authors (and not contributors) from the entry's source element
        if (!$primary || $primary->role != "author") {
            $nodes = $this->fetchElements("./atom:source[1]/atom:author");
            foreach ($nodes as $node) {
                $p = $this->parsePersonAtom($node);
                if ($p) {
                    $out[] = $p;
                }
            }
            // if there are still no people, return null
            if (!$out->primary()) {
                return null;
            }
        }
        return $out;
    }

    /** Primitive to fetch an RDF entry's canonical URL */
    protected function getUrlRss1() {
        // XPath doesn't seem to like the query we'd need for this, so it must be done the hard way.
        $node = $this->subject;
        if ($node->localName=="item" && ($node->namespaceURI==XPath::NS['rss1'] || $node->namespaceURI==XPath::NS['rss0']) && $node->hasAttributeNS(XPath::NS['rdf'], "about")) {
            return $this->resolveNodeUrl($node, "about", XPath::NS['rdf']);
        } else {
            return null;
        }
    }
}
