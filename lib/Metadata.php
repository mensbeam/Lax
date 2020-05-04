<?php
/** @license MIT
 * Copyright 2018 J. King et al.
 * See LICENSE and AUTHORS files for details */

declare(strict_types=1);
namespace MensBeam\Lax;

use MensBeam\Lax\Link\Collection as LinkCollection;

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

    public $links;

    public function __construct() {
        $this->links = new LinkCollection;
    }
}
