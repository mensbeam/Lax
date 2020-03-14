<?php
/** @license MIT
 * Copyright 2018 J. King et al.
 * See LICENSE and AUTHORS files for details */

declare(strict_types=1);
namespace MensBeam\Lax\Parser\XML;

use MensBeam\Lax\Parser\Exception;
use MensBeam\Lax\Person\Collection as PersonCollection;
use MensBeam\Lax\Category\Collection as CategoryCollection;
use MensBeam\Lax\Feed as FeedStruct;
use MensBeam\Lax\Date;
use MensBeam\Lax\Schedule;
use MensBeam\Lax\Text;
use MensBeam\Lax\Url;

class Feed extends Construct implements \MensBeam\Lax\Parser\Feed {
    protected const LIBXML_OPTIONS = \LIBXML_BIGLINES | \LIBXML_COMPACT | \LIBXML_HTML_NODEFDTD | \LIBXML_NOCDATA | \LIBXML_NOENT | \LIBXML_NONET | \LIBXML_NOERROR | LIBXML_NOWARNING;

    /** @var string */
    protected $data;
    /** @var string */
    protected $contentType;
    /** @var string */
    protected $url;
    /** @var \DOMElement */
    protected $subject;
    /** @var \DOMXpath */
    protected $xpath;

    /** Constructs a parsed feed */
    public function __construct(string $data, string $contentType = null, string $url = null) {
        $this->data = $data;
        $this->contentType = $contentType;
        if (strlen($url ?? "")) {
            $this->url = $url;
        }
    }

    /** Performs initialization of the instance */
    protected function init(FeedStruct $feed): FeedStruct {
        $this->document = new \DOMDocument();
        if (!$this->document->loadXML($this->data, self::LIBXML_OPTIONS)) {
            throw new Exception("notXML");
        }
        $this->document->documentURI = (string) $this->url;
        $this->xpath = new XPath($this->document);
        $this->subject = $this->document->documentElement;
        $ns = $this->subject->namespaceURI;
        $name = $this->subject->localName;
        if (is_null($ns) && $name === "rss") {
            $this->subject = $this->fetchElement("channel") ?? $this->subject;
            $feed->format = "rss";
            $feed->version = $this->document->documentElement->hasAttribute("version") ? $this->document->documentElement->getAttribute("version") : null;
        } elseif ($ns === XPath::NS['rdf'] && $name === "RDF") {
            $feed->format = "rdf";
            $channel = $this->fetchElement("rss1:channel|rss0:channel");
            if ($channel) {
                $this->subject = $channel;
                $feed->version = ($channel->namespaceURI === XPath::NS['rss1']) ? "1.0" : "0.90";
            } else {
                $element = $this->fetchElement("rss1:item|rss0:item|rss1:image|rss0:image");
                if ($element) {
                    $feed->version = ($element->namespaceURI === XPath::NS['rss1']) ? "1.0" : "0.90";
                } else {
                    throw new Exception("notXMLFeed");
                }
            }
        } elseif ($ns === XPath::NS['atom'] && $name === "feed") {
            $feed->format = "atom";
            $feed->version = "1.0";
        } else {
            throw new Exception("notXMLFeed");
        }
        $feed->meta->url = $this->url;
        return $feed;
    }

    /** Parses the feed to extract data */
    public function parse(FeedStruct $feed = null): FeedStruct {
        $feed = $this->init($feed ?? new FeedStruct);
        $feed->meta->url = strlen($this->url ?? "") ? new Url($this->url) : null;
        $feed->sched = $this->getSchedule();
        $feed->id = $this->getId();
        $feed->lang = $this->getLang();
        $feed->url = $this->getUrl();
        //$feed->link = $this->getLink();
        //$feed->title = $this->getTitle();
        //$feed->summary = $this->getSummary();
        //$feed->dateModified = $this->getDateModified();
        //$feed->icon = $this->getIcon();
        //$feed->image = $this->getImage();
        //$feed->people = $this->getPeople();
        //$feed->categories = $this->getCategories();
        //$feed->entries = $this->getEntries($feed);
        return $feed;
    }

    public function getId(): ?string {
        return $this->getIdAtom() ?? $this->getIdDC() ?? $this->getIdRss2();
    }

    public function getSchedule(): Schedule {
        $sched = new Schedule;
        $sched->interval = $this->getSchedIntervalRss1() ?? $this->getSchedIntervalRss2();
        $sched->skip = $this->getSchedSkipRss2();
        $sched->expired = $this->getExpiredPod();
        if (is_null($sched->expired) && (($sched->skip & Schedule::DAY_ALL) == Schedule::DAY_ALL || ($sched->skip & Schedule::HOUR_ALL) == Schedule::HOUR_ALL)) {
            $sched->expired = true;
        }
        if ($sched->interval) {
            $sched->base = $this->getSchedBaseRss1();
        }
        return $sched;
    }

    public function getLang(): ?string {
        return $this->getLangXML() ?? $this->getLangDC() ?? $this->getLangRss2();
    }

    public function getUrl(): ?Url {
        return $this->getUrlAtom() ?? $this->getUrlRss1() ?? $this->getUrlPod();
    }

    public function getTitle(): ?Text {
        return $this->getTitleAtom() ?? $this->getTitleRss1() ?? $this->getTitleRss2() ?? $this->getTitleDC() ?? $this->getTitlePod();
    }

    public function getLink(): ?Url {
        return $this->getLinkAtom() ?? $this->getLinkRss1() ?? $this->getLinkRss2();
    }

    public function getSummary(): ?Text {
        // unlike most other data, Atom is not preferred, because Atom doesn't really have feed summaries
        return $this->getSummaryDC() ?? $this->getSummaryRss1() ?? $this->getSummaryRss2() ?? $this->getSummaryPod() ?? $this->getSummaryAtom();
    }

    public function getCategories(): CategoryCollection {
        return $this->getCategoriesAtom() ?? $this->getCategoriesRss2() ?? $this->getCategoriesDC() ?? $this->getCategoriesPod() ?? new CategoryCollection;
    }

    public function getPeople(): PersonCollection {
        $authors = $this->getAuthorsAtom() ?? $this->getAuthorsDC() ?? $this->getAuthorsPod() ?? $this->getAuthorsRss2() ?? new PersonCollection;
        $contributors = $this->getContributorsAtom() ?? $this->getContributorsDC() ?? new PersonCollection;
        $editors = $this->getEditorsRss2() ?? new PersonCollection;
        $webmasters = $this->getWebmastersPod() ?? $this->getWebmastersRss2() ?? new PersonCollection;
        return $authors->merge($contributors, $editors, $webmasters);
    }

    public function getDateModified(): ?Date {
        return $this->getDateModifiedAtom() ?? $this->getDateModifiedDC() ?? $this->getDateModifiedRss2();
    }

    public function getEntries(FeedStruct $feed = null): array {
        return $this->getEntriesAtom() ?? $this->getEntriesRss1() ?? $this->getEntriesRss2() ?? [];
    }

    public function getIcon(): ?Url {
        return null;
    }

    public function getImage(): ?Url {
        return null;
    }

    /** Fetches the "complete" flag from an iTunes podcast */
    protected function getExpiredPod(): ?bool {
        return $this->fetchString("apple:complete", "(?-i:Yes)") ? true : null; // case-sensitive pattern
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
            $f = max(1, (int) $this->fetchString("sched:updateFrequency", "0*[1-9]\d*")); // a frequency of zero makes no sense
            // divide the period by the frequency
            // FIXME: we must have an integer result because PHP (incorrectly) rejects fractional intervals
            // see https://bugs.php.net/bug.php?id=53831
            $n = max(1, intdiv($n, $f)); // a frequency of zero still makes no sense, so we assume at least one subdivision
            return new \DateInterval("P".(strlen($p) === 1 ? "" : $p[0]).$n.$p[-1]);
        }
        return null;
    }

    protected function getSchedBaseRss1(): ?Date {
        return $this->fetchDate("sched:updateBase");
    }


    /** Computes the "skip-schedule" of an RSS feed, the set of days and hours during which a feed should not be fetched */
    protected function getSchedSkipRss2(): ?int {
        $out = 0;
        foreach($this->fetchString("skipHours/hour", "\d+", true) ?? [] as $h) {
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
        foreach($this->fetchString("skipDays/day", null, true) ?? [] as $d) {
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

    protected function getUrlAtom(): ?Url {
        return $this->fetchAtomRelation("self");
    }

    protected function getUrlRss1(): ?Url {
        return $this->fetchUrl("(self::rss1:channel|self::rss0:channel)/@rdf:about");
    }

    protected function getUrlPod(): ?Url {
        return $this->fetchUrl("apple:new-feed-url");
    }
}
