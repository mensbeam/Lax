<?php
/** @license MIT
 * Copyright 2018 J. King et al.
 * See LICENSE and AUTHORS files for details */

declare(strict_types=1);
namespace JKingWeb\Lax;

class Feed {
    public $type;
    public $version;
    public $url;
    public $link;
    public $title;
    public $summary;
    public $categories;
    public $people;
    public $author;
    public $dateModified;
    public $entries;

    public static function parse(string $data, ?string $contentType = null, ?string $url = null): self {
        $out = new self;
        return $out;
    }
}
