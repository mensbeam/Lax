<?php
/** @license MIT
 * Copyright 2018 J. King et al.
 * See LICENSE and AUTHORS files for details */

declare(strict_types=1);
namespace MensBeam\Lax\Parser\HTTP;

use MensBeam\Lax\Date;
use MensBeam\Lax\MimeType;
use MensBeam\Lax\Url;
use MensBeam\Lax\Metadata as MetaStruct;
use MensBeam\Lax\Link\Link;
use MensBeam\Lax\Link\Collection as LinkCollection;
use Psr\Http\Message\MessageInterface;

class Message {
    protected const TYPE_PATTERN = '/^[A-Za-z0-9!#$%&\'*+\-\.\^_`|~]+\/[A-Za-z0-9!#$%&\'*+\-\.\^_`|~]+\s*(;.*)?$/s';
    protected const DATE_PATTERN = '/^(?|(Mon|Tue|Wed|Thu|Fri|Sat|Sun), \d\d (?:Jan|Feb|Mar|Apr|May|Jun|Jul|Aug|Sep|Oct|Nov|Dec) \d{4} \d\d:\d\d:\d\d GMT|((?:Mon|Tues|Wednes|Thurs|Fri|Satur|Sun)day), \d\d-(?:Jan|Feb|Mar|Apr|May|Jun|Jul|Aug|Sep|Oct|Nov|Dec)-\d\d \d\d:\d\d:\d\d GMT|(Mon|Tue|Wed|Thu|Fri|Sat|Sun) (?:Jan|Feb|Mar|Apr|May|Jun|Jul|Aug|Sep|Oct|Nov|Dec) (?:\d\d| \d) \d\d:\d\d:\d\d \d{4})$/';
    protected const CCON_PATTERN = '/[,\s]*[^=,]*(?:=(?:"(?:\\\"|[^"])*(?:"|$)[^,]*|[^,]*))?/';
    protected const DSEC_PATTERN = '/^\d+$/';
    protected const ETAG_PATTERN = '/^.+$/';
    protected const DTOK_PATTERN = '/^(?|(\d+)|"((?:\\\(?=\d)|\d)+)".*)$/';
    protected const LINK_PATTERN = '/[,\s]*<([^>]*)>((?:;\s*[^=,;]*(?:=(?:"(?:\\\"|[^"])*(?:"|$)[^,;]*|[^,;]*))?)*)/';
    protected const LPRM_PATTERN = '/[;\s]*([^=;]*)(?:=("(?:\\\"|[^"])*(?:"|$)[^;]*|[^;]*))?/';
    protected const NAME_PATTERN = '/^[A-Za-z0-9!#$%&\'*+\-\.\^_`|~]+$/s';
    protected const BVAL_PATTERN = '/^[\t\x{20}-\x{7E}\x{80}-\x{FF}]+$/su';
    protected const QVAL_PATTERN = '/^"((?:\\\"|[\t !\x{23}-\x{7E}\x{80}-\x{FF}])*)(?:"|$)/su';
    protected const VESC_PATTERN = '/\\\(.)/s';
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

    public function parse(MetaStruct $meta = null): MetaStruct {
        $meta = $meta ?? new MetaStruct;
        $meta->url = strlen($this->url ?? "") ? new Url($this->url) : null;
        $meta->type = $this->getContentType();
        $meta->date = $this->getDate();
        $meta->expires = $this->getExpires();
        $meta->lastModified = $this->getLastModified();
        $meta->etag = $this->getEtag();
        $meta->age = $this->getAge();
        $meta->maxAge = $this->getMaxAge();
        $meta->links = $this->getLinks();
        return $meta;
    }

    protected function parseHeader(string $name, string $pattern, bool $multi = false): ?array {
        if ($multi) {
            if (preg_match_all($pattern, $this->msg->getHeaderLine($name), $match, \PREG_SET_ORDER)) {
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
        return $this->parseHeader("ETag", self::ETAG_PATTERN)[0] ?? null;
    }

    public function getAge(): ?\DateInterval {
        $a = (int) ($this->parseHeader("Age", self::DSEC_PATTERN)[0] ?? 0);
        if ($a) {
            return new \DateInterval("PT{$a}S");
        }
        return null;
    }

    public function getMaxAge(): ?\DateInterval {
        $out = 0;
        $maxAge = 0;
        $sharedMaxAge = 0;
        foreach ($this->parseHeader("Cache-Control", self::CCON_PATTERN, true) ?? [] as $t) {
            $t = explode("=", trim($t[0], ", \t"), 2);
            $k = strtolower($t[0]);
            if (($k === "max-age" || $k === "s-maxage") && isset($t[1]) && strlen($t[1])) {
                if (preg_match(self::DTOK_PATTERN, $t[1], $match)) {
                    if ($k === "max-age" && !$maxAge) {
                        $maxAge = (int) str_replace("\\", "", $match[1]);
                    } elseif ($k === "s-maxage" && !$sharedMaxAge) {
                        $sharedMaxAge = (int) str_replace("\\", "", $match[1]);
                    }
                }
            }
        }
        if ($maxAge && $sharedMaxAge) {
            $out = min($maxAge, $sharedMaxAge);
        } elseif ($maxAge || $sharedMaxAge) {
            $out = max($maxAge, $sharedMaxAge);
        }
        if ($out) {
            return new \DateInterval("PT".$out."S");
        }
        return null;
    }

    public function getLinks(): LinkCollection {
        $out = new LinkCollection;
        foreach ($this->parseHeader("Link", self::LINK_PATTERN, true) ?? [] as $h) {
            $l = new Link;
            $l->url = Url::fromString($h[1], $this->url);
            if ($l->url && $p = $this->parseParams($h[2])) {
                // normalize and deduplicate relations
                $relations = ['f' => $this->normalizeRelations($p['rel'] ?? ""), 'r' => $this->normalizeRelations($p['rev'] ?? "")];
                if (!$relations['f'] && !$relations['r']) {
                    // if there are no relations, skip this link
                    continue;
                }
                // build the link object with everything except the relation
                $l->anchor = isset($p['anchor']) ? Url::fromString($p['anchor'], $this->url) : null;
                $l->type = MimeType::parse($p['type'] ?? "");
                foreach (['title' => "title", 'media' => "media", 'hreflang' => "lang"] as $src => $dst) {
                    $l->$dst = isset($p[$src]) ? $p[$src] : null;
                }
                // clear any parameters we handle ourselves and leave the rest as unprocessed extended attributes
                foreach (["rel", "rev", "title", "media", "anchor", "type", "hreflang"] as $attr) {
                    unset($p[$attr]);
                }
                $l->attr = $p;
                // clone the link object for each forward and reverse relation and add each to the output collection
                foreach ($relations['f'] as $r) {
                    $i = clone $l;
                    $i->rel = $r;
                    $out[] = $i;
                }
                foreach ($relations['r'] as $r) {
                    $i = clone $l;
                    $i->rel = $r;
                    $i->rev = true;
                    $out[] = $i;
                }
                // TODO: deduplicate results, maybe
            }
        }
        return $out;
    }

    protected function parseParams(string $params): array {
        $out = [];
        if (preg_match_all(self::LPRM_PATTERN, $params, $matches, \PREG_SET_ORDER)) {
            foreach ($matches as $match) {
                [$param, $name, $value] = array_pad($match, 3, "");
                if (preg_match(self::NAME_PATTERN, $name)) {
                    $name = strtolower($name);
                } else {
                    continue;
                }
                if (strlen($value) && $value[0] === '"') {
                    if (preg_match(self::QVAL_PATTERN, $value, $match)) {
                        $value = preg_replace(self::VESC_PATTERN, '$1', $match[1]);
                    } else {
                        continue;
                    }
                } else {
                    $value = rtrim($value, "\t\r\n ");
                    if (!preg_match(self::BVAL_PATTERN, $value, $match)) {
                        continue;
                    }
                }
                $out[$name] = $value;
            }
        }
        return $out;
    }

    protected function normalizeRelations(string $relations): array {
        $out = [];
        $relations = trim($relations, "\t\r\n ");
        if (!strlen($relations)) {
            return $out;
        }
        foreach (preg_split('/\s+/', $relations) as $rel) {
            $u = Url::fromString($rel);
            if (!$u || !strlen($u->getScheme())) {
                $out[] = strtolower($rel);
            } else {
                $out[] = (string) $u;
            }
        }
        return array_unique($out);
    }
}
