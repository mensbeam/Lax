<?php
/** @license MIT
 * Copyright 2018 J. King et al.
 * See LICENSE and AUTHORS files for details */

declare(strict_types=1);
namespace JKingWeb\Lax;

trait XMLCommonPrimitives {

    /** Primitive to fetch an Atom feed/entry title
     * 
     * This fetches the title in plain text rather than HTML, even if HTML is provided in the feed/entry
     */
    protected function getTitleAtom() {
        return $this->fetchTextAtom("./atom:title");
    }

    /** Primitive to fetch an RSS feed/entry title */
    protected function getTitleRss2() {
        return $this->fetchText("./title");
    }

    /** Primitive to fetch an RDF feed/entry title */
    protected function getTitleRss1() {
        return $this->fetchText("./rss1:title|./rss0:title");
    }

    /** Primitive to fetch a Dublin Core feed/entry title */
    protected function getTitleDC() {
        return $this->fetchText("./dc:title");
    }

    /** Primitive to fetch an Apple podcast/episdoe title */
    protected function getTitlePod() {
        return $this->fetchText("./apple:title");
    }

    /** Primitive to fetch an Atom feed/entry Web-representation URL */
    protected function getLinkAtom() {
        // FIXME: Atom link fetching should ideally prefer links to text/html resources or the like over e.g. other-format newsfeeds, generic XML, images, etc
        $node = $this->fetchAtomRelations();
        return $node->length ? $this->resolveNodeUrl($node->item(0), "href") : null;
    }

    /** Primitive to fetch an RSS feed/entry Web-representation URL */
    protected function getLinkRss2() {
        $node = $this->fetchElement("./link");
        return $node ? $this->resolveNodeUrl($node) : null;
    }

    /** Primitive to fetch an RDF feed/entry Web-representation URL */
    protected function getLinkRss1() {
        $node = $this->fetchElement("./rss1:link|./rss0:link");
        return $node ? $this->resolveNodeUrl($node) : null;
    }

    /** Primitive to fetch Atom feed/entry categories */
    protected function getCategoriesAtom(bool $grouped = false, bool $humanFriendly = true) {
        $nodes = $this->fetchElements("./atom:category[@term]");
        $out = [];
        foreach ($nodes as $node) {
            $scheme = $node->getAttribute("scheme");
            $cat = ($humanFriendly && $node->hasAttribute("label")) ? $node->getAttribute("label") : $node->getAttribute("term");
            if (!$out[$scheme]) {
                $out[$scheme] = [];
            }
            if (!in_array($cat, $out[$scheme])) {
                $out[$scheme][] = $cat;
            }
        }
        return $out ? $out : null;
    }

    /** Primitive to fetch RSS feed/entry categories */
    protected function getCategoriesRss2(bool $grouped = false, bool $humanFriendly = true) {
        if ($grouped) {
            $nodes = $this->fetchElements("./category");
            $out = [];
            foreach ($nodes as $node) {
                $domain = $node->getAttribute("domain");
                $cat = $this->trimText($node->textContent);
                if (!$out[$domain]) {
                    $out[$domain] = [];
                }
                if (!in_array($cat, $out[$domain])) {
                    $out[$domain][] = $cat;
                }
            }
            return $out ? $out : null;
        } else {
            $out = $this->fetchTextMulti("./category");
            return $out ? array_keys(array_flip($out)) : null;
        }
    }

    /** Primitive to fetch Dublin Core feed/entry categories
     * 
     * Dublin Core doesn't have an obvious category type, so we use 'subject' as a nearest approximation
    */
    protected function getCategoriesDC(bool $grouped = false, bool $humanFriendly = true) {
        $out = $this->fetchTextMulti("./dc:subject");
        if ($out) {
            $out = array_keys(array_flip($out));
            return $grouped ? ['' => $out] : $out;
        }
        return null;
    }

    /** Primitive to fetch podcast/episode categories */
    protected function getCategoriesPod(bool $grouped = false, bool $humanFriendly = true) {
        $nodes = $this->fetchElements("./apple:category|./gplay:category");
        $out = [];
        foreach ($nodes as $node) {
            $cat = $this->trimText($node->getAttribute("text"));
            if (strlen($cat)) {
                $out[] = $cat;
            }
        }
        $out = array_keys(array_flip($out));
        return $grouped ? ['' => $out] : $out;

    }

    /** Primitive to fetch an Atom feed/entry identifier */
    protected function getIdAtom() {
        return $this->fetchText("./atom:id");
    }

    /** Primitive to fetch an RSS feed/entry identifier 
     * 
     * Using RSS' <guid> for feed identifiers is non-standard, but harmless
    */
    protected function getIdRss2() {
        return $this->fetchText("./guid");
    }

    /** Primitive to fetch a Dublin Core feed/entry identifier */
    protected function getIdDC() {
        return $this->fetchText("./dc:identifier");
    }
}
