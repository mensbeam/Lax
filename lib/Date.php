<?php
/** @license MIT
 * Copyright 2018 J. King et al.
 * See LICENSE and AUTHORS files for details */

declare(strict_types=1);
namespace JKingWeb\Lax;

class Date extends \DateTimeImmutable implements \JsonSerializable {
    static public $supportedFormats = [
        // RFC 3339 formats
        'Y-m-d\TH:i:s.u\Z',
        'Y-m-d\TH:i:s.u\z',
        'Y-m-d\TH:i:s.uO',
        'Y-m-d\TH:i:s.uP',
        'Y-m-d\TH:i:s\Z',
        'Y-m-d\TH:i:s\z',
        'Y-m-d\TH:i:sO',
        'Y-m-d\TH:i:sP',
        'Y-m-d\tH:i:s.u\Z',
        'Y-m-d\tH:i:s.u\z',
        'Y-m-d\tH:i:s.uO',
        'Y-m-d\tH:i:s.uP',
        'Y-m-d\tH:i:s\Z',
        'Y-m-d\tH:i:s\z',
        'Y-m-d\tH:i:sO',
        'Y-m-d\tH:i:sP',
        'Y-m-d H:i:s.u\Z',
        'Y-m-d H:i:s.u\z',
        'Y-m-d H:i:s.uO',
        'Y-m-d H:i:s.uP',
        'Y-m-d H:i:s\Z',
        'Y-m-d H:i:s\z',
        'Y-m-d H:i:sO',
        'Y-m-d H:i:sP',
        // space before timezone offset
        'Y-m-d\TH:i:s.u \Z',
        'Y-m-d\TH:i:s.u \z',
        'Y-m-d\TH:i:s.u O',
        'Y-m-d\TH:i:s.u P',
        'Y-m-d\TH:i:s \Z',
        'Y-m-d\TH:i:s \z',
        'Y-m-d\TH:i:s O',
        'Y-m-d\TH:i:s P',
        'Y-m-d\tH:i:s.u \Z',
        'Y-m-d\tH:i:s.u \z',
        'Y-m-d\tH:i:s.u O',
        'Y-m-d\tH:i:s.u P',
        'Y-m-d\tH:i:s \Z',
        'Y-m-d\tH:i:s \z',
        'Y-m-d\tH:i:s O',
        'Y-m-d\tH:i:s P',
        'Y-m-d H:i:s.u \Z',
        'Y-m-d H:i:s.u \z',
        'Y-m-d H:i:s.u O',
        'Y-m-d H:i:s.u P',
        'Y-m-d H:i:s \Z',
        'Y-m-d H:i:s \z',
        'Y-m-d H:i:s O',
        'Y-m-d H:i:s P',
        'Y-m-d H:i:s.u \Z',
        'Y-m-d H:i:s.u \z',
        'Y-m-d H:i:s.u O',
        'Y-m-d H:i:s.u P',
        'Y-m-d H:i:s \Z',
        'Y-m-d H:i:s \z',
        'Y-m-d H:i:s O',
        'Y-m-d H:i:s P',
        // HTTP format (and similar)
        'D, d M Y H:i:s.u \G\M\T',
        'D, d M Y H:i:s.u \U\T\C',
        'D, d M Y H:i:s.u \U\T',
        'D, d M Y H:i:s.u \Z',
        'D, d M Y H:i:s.u O',
        'D, d M Y H:i:s.u P',
        'D, d M Y H:i:s.u\Z',
        'D, d M Y H:i:s.uO',
        'D, d M Y H:i:s.uP',
        'D, d M Y H:i:s \G\M\T',
        'D, d M Y H:i:s \U\T\C',
        'D, d M Y H:i:s \U\T',
        'D, d M Y H:i:s \Z',
        'D, d M Y H:i:s O',
        'D, d M Y H:i:s P',
        'D, d M Y H:i:s\Z',
        'D, d M Y H:i:sO',
        'D, d M Y H:i:sP',
        // HTTP obsolete format
        'D M j H:i:s.u Y',
        'D M j H:i:s Y',
        // Assumed UTC
        'Y-m-d\TH:i:s.u',
        'Y-m-d\TH:i:s',
        'Y-m-d\tH:i:s.u',
        'Y-m-d\tH:i:s',
        'Y-m-d H:i:s.u',
        'Y-m-d H:i:s',
        'Y-m-d H:i:s.u',
        'Y-m-d H:i:s',
        'D, d M Y H:i:s.u',
        'D, d M Y H:i:s',
    ];

    protected static function create(\DateTimeInterface $temp): self {
        if (version_compare(\PHP_VERSION, "7.1", ">=")) {
            return (new self)->setTimestamp($temp->getTimestamp())->setTimezone($temp->getTimezone())->setTime((int) $temp->format("H"), (int) $temp->format("i"), (int) $temp->format("s"), (int) $temp->format("u"));
        } else {
            return (new self)->setTimestamp($temp->getTimestamp())->setTimezone($temp->getTimezone());
        }
    }

    public function __construct($time = "now", $timezone = null) {
        parent::__construct($time, $timezone);
    }

    public static function createFromFormat($format, $time, $timezone = null): ?self {
        $temp = parent::createFromFormat("!".$format, $time, $timezone);
        return $temp ? static::create($temp) : null;
    }

    public static function createFromMutable($datetime) {
        $temp = parent::createFromMutable($datetime);
        return $temp ? static::create($temp) : null;
    }

    public static function createFromImmutable($datetime) {
        $temp = \DateTime::createFromImmutable($datetime);
        return $temp ? static::create($temp) : null;
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
