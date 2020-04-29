<?php
/** @license MIT
 * Copyright 2018 J. King et al.
 * See LICENSE and AUTHORS files for details */

declare(strict_types=1);
namespace MensBeam\Lax\Parser\HTTP;

use MensBeam\Lax\Date;
use MensBeam\Lax\MimeType;
use MensBeam\Lax\Url;
use MensBeam\Lax\Feed as FeedStruct;
use Psr\Http\Message\MessageInterface;

class Message {
    protected const TYPE_PATTERN = '/^[A-Za-z0-9!#$%&\'*+\-\.\^_`|~]+\/[A-Za-z0-9!#$%&\'*+\-\.\^_`|~]+\s*(;.*)?$/s';
    protected const DATE_PATTERN = '/^(?|(Mon|Tue|Wed|Thu|Fri|Sat|Sun), \d\d (?:Jan|Feb|Mar|Apr|May|Jun|Jul|Aug|Sep|Oct|Nov|Dec) \d{4} \d\d:\d\d:\d\d GMT|(Mon|Tues|Wednes|Thurs|Fri|Satur|Sun)day, \d\d-(?:Jan|Feb|Mar|Apr|May|Jun|Jul|Aug|Sep|Oct|Nov|Dec)-\d\d \d\d:\d\d:\d\d GMT|(Mon|Tue|Wed|Thu|Fri|Sat|Sun) (?:Jan|Feb|Mar|Apr|May|Jun|Jul|Aug|Sep|Oct|Nov|Dec) (?:\d\d| \d) \d\d:\d\d:\d\d \d{4})$/';
    protected const ETAG_PATTERN = '/^.+$/';
    protected const CCON_PATTERN = '/(?:^|,)\s*[^=,]+(?:=(?:"(?:\\"|[^"])*"[^,]*|[^,]*))?/';
    protected const SDAY_MAP = ["Sun", "Mon", "Tue", "Wed", "Thu", "Fri", "Sat"];
    protected const FDAY_MAP = ["Sunday", "Monday", "Tuesday", "Wednesday", "Thursday", "Friday", "Saturday"];

    /** @var \Psr\Http\Message\MessageInterface */
    protected $msg;
    protected $url;

    public function __construct(MessageInterface $msg, string $url = null) {
        $this->msg = $msg;
        if (strlen($url ?? "")) {
            $this->url = $url;
        }
    }

    public function parse(FeedStruct $feed = null): FeedStruct {
        $feed = $feed ?? new FeedStruct;
        $feed->meta->url = strlen($this->url ?? "") ? new Url($this->url) : null;
        $feed->meta->type = $this->getContentType();
        $feed->meta->date = $this->getDate();
        $feed->meta->expires = $this->getExpires();
        $feed->meta->lastModified = $this->getLastModified();
        $feed->meta->etag = $this->getEtag();
        $feed->meta->maxAge = $this->getMaxAge();
        return $feed;
    }

    protected function parseHeader(string $name, string $pattern, bool $multi = false): ?array {
        if ($multi) {
            if (preg_match_all($pattern, $this->msg->getHeaderLine($name), $match)) {
                return $match;
            }
        } else {
            foreach ($this->msg->getHeader($name) as $h) {
                if (preg_match($pattern, $h, $match)) {
                    return $match;
                }
            }
        }
        return null;
    }

    protected function parseDate(string $name): ?Date {
        foreach ($this->msg->getHeader($name) as $h) {
            if (preg_match(self::DATE_PATTERN, $h, $match)) {
                $out = Date::createFromString($match[0]);
                if ($out) {
                    // ensure the weekday is the correct day for the date
                    $day = $out->format("w");
                    if (self::SDAY_MAP[$day] === $match[1] || self::FDAY_MAP[$day] === $match[1]) {
                        return $out;
                    }
                }
            }
        }
        return null;
    }

    public function getContentType(): ?MimeType {
        $t = $this->parseHeader("Content-Type", self::TYPE_PATTERN);
        if ($t) {
            return MimeType::parse($t[0]);
        }
        return null;
    }

    public function getDate(): ?Date {
        return $this->parseDate("Date");
    }

    public function getExpires(): ?Date {
        return $this->parseDate("Expires");
    }

    public function getLastModified(): ?Date {
        return $this->parseDate("Last-Modified");
    }

    public function getEtag(): ?string {
        return $this->parseHeader("ETag", self::ETAG_PATTERN);
    }

    public function getMaxAge(): ?\DateInterval {
        $c = $this->parseHeader("Cache-Control", self::CCON_PATTERN, true);
        //var_export($c);
        //exit;
        return null;
    }
}