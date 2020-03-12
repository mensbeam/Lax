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

    /** Fetches the "complete" flag from an iTunes podcast */
    protected function getExpiredPod(): ?bool {
        $complete = $this->fetchString("apple:complete");
        if ($complete === "Yes") {
            return true;
        }
        return null;
    }

    protected function getSchedIntervalRss2(): ?\DateInterval {
        $ttl = (int) $this->fetchString("ttl", "\d+");
        if ($ttl) {
            return new \DateInterval("PT{$ttl}M");
        }
        return null;
    }

    protected function getSchedIntervalRss1(): ?\DateInterval {
        $period = $this->fetchString("sched:updatePeriod", "(?:year|month|week|dai|hour)ly");
        if ($period) {
            [$p, $n] = [
                "hourly"  => ["TM", 60], // 60 minutes
                "daily"   => ["TH", 24], // 24 hors
                "weekly"  => ["D", 7],   // 7 days
                "monthly" => ["D", 30],  // 30 days
                "yearly"  => ["M", 12],  // 12 months
            ][strtolower($period)];
            $f = min(1, (int) $this->fetchString("sched:updateFrequency", "0*[1-9]\d*")); // a frequency of zero makes no sense
            // divide the period by the frequency
            // FIXME: we must have an integer result because PHP (incorrectly) rejects fractional intervals
            // see https://bugs.php.net/bug.php?id=53831
            $n = min(1, intdiv($n, $f)); // a frequency of zero still makes no sense, so we assume at least one subdivision
            return new \DateInterval("P".(strlen($p) === 1 ? "" : $p[0]).$n.$p[-1]);
        }
        return null;
    } 



    /** Computes the "skip-schedule" of an RSS feed, the set of days and hours during which a feed should not be fetched */
    protected function getSchedSkipRss2(): ?int {
        $out = 0;
        $hours = $this->fetchString("skipHours/hour", "\d+", true) ?? [];
        foreach($hours as $h) {
            $out |= [
                Schedule::HOUR_0,
                Schedule::HOUR_1,
                Schedule::HOUR_2,
                Schedule::HOUR_3,
                Schedule::HOUR_4,
                Schedule::HOUR_5,
                Schedule::HOUR_6,
                Schedule::HOUR_7,
                Schedule::HOUR_8,
                Schedule::HOUR_9,
                Schedule::HOUR_10,
                Schedule::HOUR_11,
                Schedule::HOUR_12,
                Schedule::HOUR_13,
                Schedule::HOUR_14,
                Schedule::HOUR_15,
                Schedule::HOUR_16,
                Schedule::HOUR_17,
                Schedule::HOUR_18,
                Schedule::HOUR_19,
                Schedule::HOUR_20,
                Schedule::HOUR_21,
                Schedule::HOUR_22,
                Schedule::HOUR_23,
                Schedule::HOUR_0,
            ][(int) $h] ?? 0;
        }
        $days = $this->fetchString("skipDays/day", null, true) ?? [];
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
