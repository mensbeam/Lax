<?php
/** @license MIT
 * Copyright 2018 J. King et al.
 * See LICENSE and AUTHORS files for details */

declare(strict_types=1);
namespace JKingWeb\Lax;

class Feed {
    /** @var string $type The type of feed, one of the following:
     * 
     * - `rss` for RSS 0.9x or RSS 2.0.x
     * - `rdf` for RSS 1.0
     * - `atom` for Atom feeds
     * - `json` for JSON Feed
     * - `hfeed` for a microformat h-feed
     * 
     * The feed type is largely advisory, but is used when converting between formats
     */
    public $type;
    public $version;
    public $id;
    public $url;
    public $link;
    public $title;
    public $summary;
    public $categories;
    public $people;
    public $dateModified;
    public $entries = [];

    public static function parse(string $data, ?string $contentType = null, ?string $url = null): self {
        $out = new self;
        return $out;
    }
}
