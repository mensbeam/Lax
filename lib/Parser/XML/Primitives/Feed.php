<?php
/** @license MIT
 * Copyright 2018 J. King et al.
 * See LICENSE and AUTHORS files for details */

declare(strict_types=1);
namespace MensBeam\Lax\Parser\XML\Primitives;

use MensBeam\Lax\Parser\XML\XPath;
use MensBeam\Lax\Schedule;

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

    protected function getSchedSkipRss2(): ?int {
        $out = 0;
        $hours = $this->fetchStringMulti("skipHours/hour") ?? [];
        foreach($hours as $h) {
            $out |= [
                "0"  => Schedule::HOUR_0,
                "1"  => Schedule::HOUR_1,
                "2"  => Schedule::HOUR_2,
                "3"  => Schedule::HOUR_3,
                "4"  => Schedule::HOUR_4,
                "5"  => Schedule::HOUR_5,
                "6"  => Schedule::HOUR_6,
                "7"  => Schedule::HOUR_7,
                "8"  => Schedule::HOUR_8,
                "9"  => Schedule::HOUR_9,
                "00" => Schedule::HOUR_0,
                "01" => Schedule::HOUR_1,
                "02" => Schedule::HOUR_2,
                "03" => Schedule::HOUR_3,
                "04" => Schedule::HOUR_4,
                "05" => Schedule::HOUR_5,
                "06" => Schedule::HOUR_6,
                "07" => Schedule::HOUR_7,
                "08" => Schedule::HOUR_8,
                "09" => Schedule::HOUR_9,
                "10" => Schedule::HOUR_10,
                "11" => Schedule::HOUR_11,
                "12" => Schedule::HOUR_12,
                "13" => Schedule::HOUR_13,
                "14" => Schedule::HOUR_14,
                "15" => Schedule::HOUR_15,
                "16" => Schedule::HOUR_16,
                "17" => Schedule::HOUR_17,
                "18" => Schedule::HOUR_18,
                "19" => Schedule::HOUR_19,
                "20" => Schedule::HOUR_20,
                "21" => Schedule::HOUR_21,
                "22" => Schedule::HOUR_22,
                "23" => Schedule::HOUR_23,
                "24" => Schedule::HOUR_0,
            ][$h] ?? 0;
        }
        $days = $this->fetchStringMulti("skipDays/day") ?? [];
        foreach($days as $d) {
            $out |= [
                "monday"    => Schedule::DAY_MON,
                "tuesday"   => Schedule::DAY_TUE,
                "wednesday" => Schedule::DAY_WED,
                "thursday"  => Schedule::DAY_THU,
                "friday"    => Schedule::DAY_FRI,
                "saturday"  => Schedule::DAY_SAT,
                "sunday"    => Schedule::DAY_SUN,
                "mon"       => Schedule::DAY_MON,
                "tue"       => Schedule::DAY_TUE,
                "wed"       => Schedule::DAY_WED,
                "thu"       => Schedule::DAY_THU,
                "fri"       => Schedule::DAY_FRI,
                "sat"       => Schedule::DAY_SAT,
                "sun"       => Schedule::DAY_SUN,
            ][strtolower($d)] ?? 0;
        }
        return $out ?: null;
    }
}
