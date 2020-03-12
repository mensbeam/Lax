<?php
/** @license MIT
 * Copyright 2018 J. King et al.
 * See LICENSE and AUTHORS files for details */

declare(strict_types=1);
namespace MensBeam\Lax;

class Schedule {
    public const HOUR_0 = 1 << 0;
    public const HOUR_1 = 1 << 1;
    public const HOUR_2 = 1 << 2;
    public const HOUR_3 = 1 << 3;
    public const HOUR_4 = 1 << 4;
    public const HOUR_5 = 1 << 5;
    public const HOUR_6 = 1 << 6;
    public const HOUR_7 = 1 << 7;
    public const HOUR_8 = 1 << 8;
    public const HOUR_9 = 1 << 9;
    public const HOUR_10 = 1 << 10;
    public const HOUR_11 = 1 << 11;
    public const HOUR_12 = 1 << 12;
    public const HOUR_13 = 1 << 13;
    public const HOUR_14 = 1 << 14;
    public const HOUR_15 = 1 << 15;
    public const HOUR_16 = 1 << 16;
    public const HOUR_17 = 1 << 17;
    public const HOUR_18 = 1 << 18;
    public const HOUR_19 = 1 << 19;
    public const HOUR_20 = 1 << 20;
    public const HOUR_21 = 1 << 21;
    public const HOUR_22 = 1 << 22;
    public const HOUR_23 = 1 << 23;
    public const HOUR_ALL = 0b0000000111111111111111111111111;
    public const DAY_MON = 1 << 24;
    public const DAY_TUE = 1 << 25;
    public const DAY_WED = 1 << 26;
    public const DAY_THU = 1 << 27;
    public const DAY_FRI = 1 << 28;
    public const DAY_SAT = 1 << 29;
    public const DAY_SUN = 1 << 30;
    public const DAY_ALL = 0b1111111000000000000000000000000;

    /** @var bool $expired Whether the feed has ceased publishing. In some formats this can be an explicit flag, while in others it can be derived from scheduling data */
    public $expired;
    /** @var int $skip A bitfield representing days and hours during which a feed will not be published. 
     * 
     * The bits are arranged such that the UTC hours of the day (starting with midnight) occupy the 24 least 
     * significant bits, followed by the days of the week starting with Monday. Thus the following number:
     * 
     * `0b1100000111111100000000111111111`
     * 
     * Signifies that the feed will not be published on Saturday and Sunday, nor outside the hours 09:00-04:00 
     * UTC on other days.
     */
    public $skip;
    /** @var \DateInterval $interval The suggested interval before the feed should be fetched again */
    public $interval;
}
