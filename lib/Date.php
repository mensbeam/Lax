<?php
/** @license MIT
 * Copyright 2018 J. King et al.
 * See LICENSE and AUTHORS files for details */

declare(strict_types=1);
namespace MensBeam\Lax;

class Date extends \DateTimeImmutable implements \JsonSerializable {
    protected const PATTERN_RFC3339 = '/^(\d{4}-\d\d-\d\d)[Tt ](\d\d:\d\d(?::\d\d(?:\.\d+)?)?)\s*([Zz]|[+\-]\d\d:?\d\d)?$/';
    protected const PATTERN_RFC822 = '/^(?:Mon|Tue|Wed|Thu|Fri|Sat|Sun), (\d\d) (Jan|Feb|Mar|Apr|May|Jun|Jul|Aug|Sep|Oct|Nov|Dec) (\d\d(?:\d\d)?) (\d\d:\d\d(?::\d\d(?:\.\d+)?)?)\s*([A-Z]|GMT|UTC?|[ECMP][SD]T|[+\-]\d\d:?\d\d)?$/';
    protected const PATTERN_RFC850 = '/^(?:Mon|Tues|Wednes|Thurs|Fri|Satur|Sun)day, (\d\d)-(Jan|Feb|Mar|Apr|May|Jun|Jul|Aug|Sep|Oct|Nov|Dec)-(\d\d(?:\d\d)?) (\d\d:\d\d(?::\d\d(?:\.\d+)?)?)\s*([A-Z]|GMT|UTC?|[ECMP][SD]T|[+\-]\d\d:?\d\d)?$/';
    protected const PATTERN_ASCTIME = '/^(?:Mon|Tue|Wed|Thu|Fri|Sat|Sun) (Jan|Feb|Mar|Apr|May|Jun|Jul|Aug|Sep|Oct|Nov|Dec) (\d\d?) (\d\d:\d\d(?::\d\d(?:\.\d+)?)?) (\d{4})$/';
    protected const INPUT_FORMAT = '!Y-m-d\TH:i:s.uO';
    protected const MICROTIME_PRECSISION = 6;
    protected const MONTH_MAP = ['Jan' => "01", 'Feb' => "02", 'Mar' => "03", 'Apr' => "04", 'May' => "05", 'Jun' => "06", 'Jul' => "07", 'Aug' => "08", 'Sep' => "09", 'Oct' => "10", 'Nov' => "11", 'Dec' => "12"];
    protected const TZ_MAP = [
        'A'   => "+0100",
        'B'   => "+0200",
        'C'   => "+0300",
        'D'   => "+0400",
        'E'   => "+0500",
        'F'   => "+0600",
        'G'   => "+0700",
        'H'   => "+0800",
        'I'   => "+0900",
        'J'   => "-0000",
        'K'   => "+1000",
        'L'   => "+1100",
        'M'   => "+1200",
        'N'   => "-0100",
        'O'   => "-0200",
        'P'   => "-0300",
        'Q'   => "-0400",
        'R'   => "-0500",
        'S'   => "-0600",
        'T'   => "-0700",
        'U'   => "-0800",
        'V'   => "-0900",
        'W'   => "-1000",
        'X'   => "-1100",
        'Y'   => "-1200",
        'Z'   => "+0000",
        'UT'  => "+0000",
        'UTC' => "+0000",
        'GMT' => "+0000",
        'EST' => "-0500",
        'EDT' => "-0400",
        'CST' => "-0600",
        'CDT' => "-0500",
        'MST' => "-0700",
        'MDT' => "-0600",
        'PST' => "-0800",
        'PDT' => "-0700",
    ];
    
    /*
        Important formats:

        // RFC 3339 formats
        'Y-m-d\TH:i:s.uO',
        // HTTP format (and similar)
        'D, d M Y H:i:s \G\M\T',
        // HTTP obsolete format (assumed UTC)
        'D M j H:i:s Y',
    */

    protected static function create(\DateTimeInterface $temp): self {
        return (new self)->setTimestamp($temp->getTimestamp())->setTimezone($temp->getTimezone())->setTime((int) $temp->format("H"), (int) $temp->format("i"), (int) $temp->format("s"), (int) $temp->format("u"));
    }

    public function __construct($time = "now", $timezone = null) {
        parent::__construct($time, $timezone);
    }

    /** Returns a date parsed from a string in any of the following formats:
     * 
     * - RFC 3339
     * - RFC 822
     * - RFC 850
     * - ANSI C asctime()
     * 
     * Subsets of RFC 822 and RFC 850 formats and asctime() format are used 
     * by RFC 7231 (HTTP), and the latter definition was consulted for
     * guidance. RFC 3339 and RFC 822 formats are both supported in full, and
     * the ambiguous century of RFC 822 and RFC 850 formats is interpreted per
     *  RFC 7231. Timezones used for RFC 822 are also accepted for RFC 850.
     * 
     * All formats additionally are accepted with subsecond precision, or with
     * minute precision. Whitespace before the timezone may be omitted or used
     * in all formats as well (RFC 3339 does not normally allow whitespace, 
     * while other formats require it). 
     * 
     * If no timezone is specified, -00:00 is used.
     * 
     * @see https://tools.ietf.org/html/rfc3339
     * @see https://tools.ietf.org/html/rfc822#section-5
     * @see https://tools.ietf.org/html/rfc850#section-2.1.4
     * @see https://tools.ietf.org/html/rfc7231#section-7.1.1.1
     */
    public static function createFromString(string $timeSpec): ?self {
        $ambiguousCentury = false;
        $now = new self;
        $timeSpec = trim(preg_replace('/\s{2,}/', " ", $timeSpec));
        if (preg_match(self::PATTERN_RFC3339, $timeSpec, $match)) {
            $date = $match[1];
            $time = self::parseTime($match[2]);
            $zone = self::parseZone($match[3] ?? "");
        } elseif (preg_match(self::PATTERN_RFC822, $timeSpec, $match)) {
            $day = $match[1];
            $month = self::MONTH_MAP[$match[2]] ?? null;
            assert(!is_null($month));
            $year = $match[3];
            $time = self::parseTime($match[4]);
            $zone = self::parseZone($match[5] ?? "");
            if (strlen($year) === 2) {
                $ambiguousCentury = true;
                // get the current century
                $century = intdiv((int) $now->format("Y"), 100);
                $year = (string) ($century + (int) $year);
            }
            $date = "$year-$month-$day";
        } elseif (preg_match(self::PATTERN_RFC850, $timeSpec, $match)) {
            $day = $match[1];
            $month = self::MONTH_MAP[$match[2]] ?? null;
            assert(!is_null($month));
            $year = $match[3];
            $time = self::parseTime($match[4]);
            $zone = self::parseZone($match[5] ?? "");
            if (strlen($year) === 2) {
                $ambiguousCentury = true;
                // get the current century
                $century = intdiv((int) $now->format("Y"), 100);
                $year = (string) ($century + (int) $year);
            }
            $date = "$year-$month-$day";
        } elseif (preg_match(self::PATTERN_ASCTIME, $timeSpec, $match)) {
            $month = self::MONTH_MAP[$match[1]] ?? null;
            assert(!is_null($month));
            $day = str_pad($match[2], 2, "0", \STR_PAD_LEFT);
            assert(strlen($day) === 2);
            $time = self::parseTime($match[3]);
            $year = $match[4];
            $zone = "-0000";
            $date = "$year-$month-$day";
        } else {
            return null;
        }
        $tz = new \DateTimeZone("UTC");
        $out = self::createFromFormat(self::INPUT_FORMAT, "{$date}T$time$zone", $tz);
        // ensure there has been no roll-over
        $cDate = $out->format("Y-m-d");
        $cTime = $out->format("H:i:s.u");
        if ($cDate !== $date || $cTime !== $time) {
            return null;
        }
        if ($ambiguousCentury && $out->normalize() > $now->add(new \DateInterval("P50Y"))->normalize()) {
            $year = (int) substr($date, 0, 4);
            $year = (string) ($year - 100);
            $date = $year.substr($date, 4);
            return self::createFromFormat(self::INPUT_FORMAT, "{$date}T$time$zone", $tz);
        }
        return $out;
    }

    protected static function parseZone(string $zone): string {
        if (!strlen($zone)) {
            return "-0000";
        }
        $out = self::TZ_MAP[strtoupper($zone)] ?? null;
        if ($out) {
            return $out;
        }
        $zone = str_replace(":", "", $zone);
        if (preg_match('/^[+\-]\d{4}$/', $zone)) {
            return $zone;
        }
        return "-0000";
    }

    protected static function parseTime(string $time): string {
        $micro = str_pad("", self::MICROTIME_PRECSISION, "0");
        if (strlen($time) === 5) {
            $time .= ":00.$micro";
        } elseif (strlen($time) === 8) {
            $time .= ".$micro";
        } else {
            $time = str_pad($time, 9 + self::MICROTIME_PRECSISION, "0");
        }
        $time = substr($time, 0, 9 + self::MICROTIME_PRECSISION);
        assert((bool) preg_match('/^\d\d:\d\d:\d\d\.\d{'.self::MICROTIME_PRECSISION.'}$/', $time));
        return $time;
    }

    public static function createFromFormat($format, $time, $timezone = null): ?self {
        $temp = parent::createFromFormat("!".$format, $time, $timezone);
        return $temp ? static::create($temp) : null;
    }

    public static function createFromMutable($datetime): ?self {
        $temp = parent::createFromMutable($datetime);
        return $temp ? static::create($temp) : null;
    }

    public static function createFromImmutable($datetime): ?self {
        $temp = \DateTime::createFromImmutable($datetime);
        return $temp ? static::create($temp) : null;
    }

    /** Returns a normalized string representation of the instance's moment in time, useful for comparisons */
    public function normalize(): string {
        return $this->setTimezone(new \DateTimeZone("UTC"))->format("Y-m-d\TH:i:s.u\Z");
    }

    public function __toString() {
        if ((int) $this->format("u")) {
            return $this->format("Y-m-d\TH:i:s.uP");
        } else {
            return $this->format("Y-m-d\TH:i:sP");
        }
    }

    public function jsonSerialize() {
        return $this->__toString();
    }
}
