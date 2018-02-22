<?php
/** @license MIT
 * Copyright 2018 J. King et al.
 * See LICENSE and AUTHORS files for details */

declare(strict_types=1);
namespace JKingWeb\Lax;

trait XMLFeedPrimitives {

    /** Primitive to fetch an Atom feed summary
     * 
     * Atom does not have a 'description' element like the RSSes, but it does have 'subtitle', which fills roughly the same function
     */
    protected function getSummaryAtom() {
        return $this->fetchTextAtom("./atom:subtitle");
    }

    /** Primitive to fetch an RSS feed summary */
    protected function getSummaryRss2() {
        return $this->fetchText("./description");
    }

    /** Primitive to fetch an RDF feed summary */
    protected function getSummaryRss1() {
        return $this->fetchText("./rss1:description|./rss0:description");
    }

    /** Primitive to fetch a Dublin Core feed summary */
    protected function getSummaryDC() {
        return $this->fetchText("./dc:description");
    }

    /** Primitive to fetch a podcast summary */
    protected function getSummaryPod() {
        return $this->fetchText("./apple:summary|./gplay:description") ?? $this->fetchText("./apple:subtitle");
    }
}
