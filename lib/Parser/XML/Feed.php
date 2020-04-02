<?php
/** @license MIT
 * Copyright 2018 J. King et al.
 * See LICENSE and AUTHORS files for details */

declare(strict_types=1);
namespace MensBeam\Lax\Parser\XML;

use MensBeam\Lax\Parser\XML\Entry as EntryParser;
use MensBeam\Lax\Parser\Exception;
use MensBeam\Lax\Person\Person;
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
        $this->document->documentURI = $this->url;
        $this->xpath = new XPath($this->document);
        $this->subject = $this->document->documentElement;
        $ns = $this->subject->namespaceURI;
        $name = $this->subject->localName;
        if (is_null($ns) && $name === "rss") {
            $this->subject = $this->fetchElement("channel") ?? $this->subject;
            $feed->format = "rss";
            $feed->version = $this->document->documentElement->hasAttribute("version") ? $this->document->documentElement->getAttribute("version") : null;
            $this->xpath->rss2 = true;
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
        $feed->link = $this->getLink();
        $feed->title = $this->getTitle();
        $feed->summary = $this->getSummary();
        $feed->dateModified = $this->getDateModified();
        $feed->icon = $this->getIcon();
        $feed->image = $this->getImage();
        $feed->people = $this->getPeople();
        $feed->categories = $this->getCategories();
        $feed->entries = $this->getEntries($feed);
        return $feed;
    }

    public function getId(): ?string {
        return $this->getIdAtom()   // Atom ID
            ?? $this->getIdDC()     // Dublin Core ID
            ?? $this->getIdRss2();  // RSS GUID
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
            $sched->base = $this->fetchDate("sched:updateBase", self::DATE_ANY);
        }
        return $sched;
    }

    public function getLang(): ?string {
        return $this->getLangXML()      // xml:lang attribute
            ?? $this->getLangDC()       // Dublin Core language
            ?? $this->getLangRss2();    // RSS language
    }

    public function getUrl(): ?Url {
        return $this->fetchAtomRelation("self", ["application/atom+xml"])   // Atom 'self' relation URL
            ?? $this->fetchUrl("self::rss1:channel/@rdf:about")             // RDF-about URL from RSS 0.90 or RSS 1.0
            ?? $this->fetchUrl("apple:new-feed-url");                       // iTunes podcast canonical URL
    }

    public function getLink(): ?Url {
        return $this->getLinkAtom()     // Atom link
            ?? $this->getLinkRss1()     // RSS 0.90 or RSS 1.0 link
            ?? $this->getLinkRss2();    // RSS 2.0 link
    }

    public function getTitle(): ?Text {
        return $this->getTitleAtom()    // Atom title
            ?? $this->getTitleRss1()    // RSS 0.90 or RSS 1.0 title
            ?? $this->getTitleRss2()    // RSS 2.0 title
            ?? $this->getTitleDC()      // Dublin Core title
            ?? $this->getTitlePod();    // iTunes podcast title
    }

    public function getSummary(): ?Text {
        return $this->fetchAtomText("atom:summary")                                     // Atom summary (non-standard)
            ?? $this->fetchAtomText("atom:subtitle")                                    // Atom subtitle
            ?? $this->fetchText("dc:abstract|dct:abstract", self::TEXT_PLAIN)           // Dublin Core abstract
            ?? $this->fetchText("dc:description|dct:description", self::TEXT_PLAIN)     // Dublin Core description
            ?? $this->fetchText("rss1:description", self::TEXT_LOOSE)                   // RSS 1.0 description
            ?? $this->fetchText("rss0:description", self::TEXT_LOOSE)                   // RSS 0.90 description
            ?? $this->fetchText("rss2:description", self::TEXT_LOOSE)                   // RSS 2.0 description
            ?? $this->fetchText("gplay:description", self::TEXT_PLAIN)                  // Google Play podcast description
            ?? $this->fetchText("apple:summary", self::TEXT_PLAIN)                      // iTunes podcast summary
            ?? $this->fetchText("apple:subtitle", self::TEXT_PLAIN);                    // iTunes podcast subtitle
    }

    public function getDateModified(): ?Date {
        /*  fetching a date works differently from other data as only Atom has
            well-defined semantics here. Thus the semantics of all the other
            formats are equal, and we want the latest date, whatever it is.
        */
        return $this->fetchDate("atom:updated", self::DATE_LATEST)
            ?? $this->fetchDate(self::QUERY_AMBIGUOUS_DATES, self::DATE_LATEST);
    }

    public function getIcon(): ?Url {
        return $this->fetchUrl("atom:icon")                 // Atom icon URL
            ?? $this->fetchAtomRelation("icon")             // Atom icon relation URL
            ?? $this->fetchAtomRelation("shortcut icon")    // Atom icon relation URL (non-standard Internet Explorewr usage)
            ?? $this->fetchAtomRelation("icon shortcut");   // Atom icon relation URL (non-standard Internet Explorewr usage, reversed)
    }

    public function getImage(): ?Url {
        return $this->fetchUrl("atom:logo")                             // Atom logo URL
            ?? $this->fetchUrl("rss1:image/@rdf:resource")              // RSS 1.0 channel image RDF resource
            ?? $this->fetchUrl("rss1:image/rss1:url")                   // RSS 1.0 channel image
            ?? $this->fetchUrl("rss1:image/@rdf:about")                 // RSS 1.0 channel image about-URL
            ?? $this->fetchUrl("/rdf:RDF/rss1:image/@rdf:resource")     // RSS 1.0 root image RDF resource
            ?? $this->fetchUrl("/rdf:RDF/rss1:image/rss1:url")          // RSS 1.0 root image
            ?? $this->fetchUrl("/rdf:RDF/rss1:image/@rdf:about")        // RSS 1.0 root image about-URL
            ?? $this->fetchUrl("rss0:image/rss0:url")                   // RSS 0.90 channel image
            ?? $this->fetchUrl("/rdf:RDF/rss0:image/rss0:url")          // RSS 0.90 root image
            ?? $this->fetchUrl("rss2:image/rss2:url")                   // RSS 2.0 channel image
            ?? $this->fetchUrl("gplay:image/@href")                     // Google Play podcast image
            ?? $this->fetchUrl("apple:image/@href");                    // iTunes podcast image
    }

    public function getCategories(): CategoryCollection {
            return $this->getCategoriesFromNode($this->subject) ?? new CategoryCollection;
    }

    public function getPeople(): PersonCollection {
        $authors =
            $this->fetchAtomPeople("atom:author", "author")             // Atom authors
            ?? $this->fetchPeople("dc:creator|dct:creator", "author")   // Dublin Core creators
            ?? $this->fetchPeople("rss2:author", "author")              // RSS 2.0 authors
            ?? $this->fetchPeople("gplay:author", "author")             // Google Play authors
            ?? $this->fetchPeople("apple:author", "author")             // iTunes authors
            ?? new PersonCollection;
        $contributors =
            $this->fetchAtomPeople("atom:contributor", "contributor")               // Atom contributors
            ?? $this->fetchPeople("dc:contributor|dct:contributor", "contributor")  // Dublin Core contributors
            ?? new PersonCollection;
        $editors =
            $this->fetchPeople("rss2:managingEditor", "editor")             // RSS 2.0 editors
            ?? $this->fetchPeople("dc:publisher|dct:publisher", "editor")   // Dublin Core publishers
            ?? new PersonCollection;
        $webmasters =
            $this->fetchPeople("rss2:webMaster", "webmaster")   // RSS 2.0 authors
            ?? $this->getOwnersTunes()                          // iTunes webmaster
            ?? $this->fetchPeople("gplay:email", "webmaster")   // Google Play webmaster
            ?? new PersonCollection;
        return $authors->merge($contributors, $editors, $webmasters);
    }

    public function getEntries(FeedStruct $feed): array {
        $out = [];
        foreach ($this->xpath->query("atom:entry|rss2:item|rss0:item|rss1:item|/rdf:RDF/rss0:item|/rdf:RDF/rss1:item", $this->subject) as $node) {
            $entry = (new EntryParser($node, $this->xpath, $feed))->parse();
            if (!$this->empty($entry, ["lang"])) {
                $out[] = $entry;
            }
        }
        return $out;
    }

    /** Fetches the "complete" flag from an iTunes podcast */
    protected function getExpiredPod(): ?bool {
        return $this->fetchString("apple:complete", "(?-i:Yes)") ? true : null; // case-sensitive pattern
    }

    /** Fetches the "time-to-live" value (a number of minutes before the feed should be re-fetched) from an RSS 2.0 feed */
    protected function getSchedIntervalRss2(): ?\DateInterval {
        $ttl = (int) $this->fetchString("rss2:ttl", "\d+");
        if ($ttl) {
            return new \DateInterval("PT{$ttl}M");
        }
        return null;
    }

    /** Fetches the schedule interval from an RSS feed; this is necessarily approximate:
     *
     * The interval is defined in the syndication RSS extension as fractions of a period, but PHP only supports integer intervals, so we perform integer divison on the nearest subdivision of a period, returning at least one.
     *
     * For example, "four times monthly" first assumes a month is 30 days, and divides this by four to yield seven days.
     */
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

    /** Computes the "skip-schedule" of an RSS feed, the set of days and hours during which a feed should not be fetched */
    protected function getSchedSkipRss2(): ?int {
        $out = 0;
        foreach ($this->fetchString("rss2:skipHours/rss2:hour", "\d+", true) ?? [] as $h) {
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
        foreach ($this->fetchString("rss2:skipDays/rss2:day", null, true) ?? [] as $d) {
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

    /** Returns at most a single person: podcasts implicitly have only one author or webmaster */
    protected function getOwnersTunes(): ?PersonCollection {
        $out = new PersonCollection;
        foreach ($this->xpath->query("apple:owner", $this->subject) as $node) {
            $p = new Person;
            $mail = $this->fetchString("apple:email", null, null, $node) ?? "";
            $p->mail = $this->validateMail($mail) ? $mail : null;
            $p->name = $this->fetchString("apple:name", ".+", null, $node) ?? $mail;
            $p->role = "webmaster";
            if (strlen($p->name)) {
                $out[] = $p;
            }
        }
        return count($out) ? $out : null;
    }
}
