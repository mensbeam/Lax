<?php
/** @license MIT
 * Copyright 2018 J. King et al.
 * See LICENSE and AUTHORS files for details */

declare(strict_types=1);
namespace JKingWeb\Lax;

class Date extends \DateTimeImmutable implements \JsonSerializable {
    const SUPPORTED_FORMATS = [
        "!Y-m-d\TH:i:s.u O",
        "!Y-m-d\TH:i:s.uO",
        "!Y-m-d\TH:i:s.u P",
        "!Y-m-d\TH:i:s.uP",
        "!Y-m-d\TH:i:s.u T",
        "!Y-m-d\TH:i:s.u\Z",
        "!Y-m-d\TH:i:s.u",
        "!Y-m-d H:i:s.u O",
        "!Y-m-d H:i:s.uO",
        "!Y-m-d H:i:s.u P",
        "!Y-m-d H:i:s.uP",
        "!Y-m-d H:i:s.u T",
        "!Y-m-d H:i:s.u\Z",
        "!Y-m-d H:i:s.u",
        "!Y-m-d\TH:i:s O",
        "!Y-m-d\TH:i:sO",
        "!Y-m-d\TH:i:s P",
        "!Y-m-d\TH:i:sP",
        "!Y-m-d\TH:i:s T",
        "!Y-m-d\TH:i:s",
        "!Y-m-d H:i:s O",
        "!Y-m-d H:i:sO",
        "!Y-m-d H:i:s P",
        "!Y-m-d H:i:sP",
        "!Y-m-d H:i:s T",
        "!Y-m-d H:i:s\Z",
        "!Y-m-d H:i:s",
        "!D, d M Y H:i:s O",
        "!D, d M Y H:i:sO",
        "!D, d M Y H:i:s P",
        "!D, d M Y H:i:sP",
        "!D, d M Y H:i:s T",
        "!D, d M Y H:i:s",
        "!D, d M y H:i:s O",
        "!D, d M y H:i:sO",
        "!D, d M y H:i:s P",
        "!D, d M y H:i:sP",
        "!D, d M y H:i:s T",
        "!D, d M y H:i:s",
    ];

    protected static function create(\DateTimeInterface $temp) : self {
        if (version_compare(\PHP_VERSION, "7.1", ">=")) {
            return (new self)->setTimestamp($temp->getTimestamp())->setTimezone($temp->getTimezone())->setTime((int) $temp->format("H"), (int) $temp->format("i"), (int) $temp->format("s"), (int) $temp->format("u"));
        } else {
            return (new self)->setTimestamp($temp->getTimestamp())->setTimezone($temp->getTimezone());
        }
    }

    public function __construct($time = "now", $timezone = null) {
        parent::__construct($time, $timezone);
    }

    public static function createFromFormat($format , $time, $timezone = null) {
        $temp = parent::createFromFormat($format, $time, $timezone);
        if (!$temp) {
            return $temp;
        }
        return static::create($temp);
    }

    public static function createFromMutable($datetime) {
        $temp = parent::createFromMutable($datetime);
        if (!$temp) {
            return $temp;
        }
        return static::create($temp);
    }

    public static function createFromImmutable($datetime) {
        $temp = \DateTime::createFromImmutable($datetime);
        if (!$temp) {
            return $temp;
        }
        return static::create($temp);
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
