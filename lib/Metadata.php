<?php
/** @license MIT
 * Copyright 2018 J. King et al.
 * See LICENSE and AUTHORS files for details */

declare(strict_types=1);
namespace JKingWeb\Lax;

class Metadata {
    public $url;
    public $type;
    public $cached = false;
    public $lastModified;
    public $etag;
    public $expires;
    public $maxAge;
}
