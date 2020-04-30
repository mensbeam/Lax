<?php
/** @license MIT
 * Copyright 2018 J. King et al.
 * See LICENSE and AUTHORS files for details */

declare(strict_types=1);
namespace MensBeam\Lax;

class Metadata {
    /** @var bool */
    public $cached = false;
    /** @var \MensBeam\Lax\Url */
    public $url;
    /** @var \MensBeam\Lax\MimeType */
    public $type;
    /** @var \MensBeam\Lax\Date */
    public $date;
    /** @var \MensBeam\Lax\Date */
    public $expires;
    /** @var \MensBeam\Lax\Date */
    public $lastModified;
    /** @var string */
    public $etag;
    /** @var \DateInterval */
    public $maxAge;
    /** @var \DateInterval */
    public $age;
}
