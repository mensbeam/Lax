<?php
/** @license MIT
 * Copyright 2018 J. King et al.
 * See LICENSE and AUTHORS files for details */

declare(strict_types=1);
namespace JKingWeb\Lax;

/** Represents a news feed, in arbitrary format
 * 
 * All properties may be null.
 */
class Feed {
    /** @var string $format The format of news feed, one of the following:
     * 
     * - `rss` for RSS 0.9x or RSS 2.0.x
     * - `rdf` for RSS 1.0
     * - `atom` for Atom feeds
     * - `json` for JSON Feed
     * - `hfeed` for a microformat h-feeds
     * 
     * The format is largely advisory, but may be used when converting between formats
     */
    public $type;
    /** @var string $version The format version of the news feed
     * 
     * The version is largely advisory, but may be used when converting between formats
     */
    public $version;
    /** @var string $id The globally unique identifier for the news feed
     * 
     * For some formats, such as RSS 2.0 and JSON Feed, this may be he same as the news feed URL
     */
    public $id;
    /** @var string $url The feed's canonical URL. This may differ from the URL used to fetch the news feed */
    public $url;
    /** @var string $link The URL  of the Web page associated with the feed */
    public $link;
    /** @var \JKingWeb\Lax\Text $title The title of the news feed */
    public $title;
    /** @var \JKingWeb\Lax\Text $summary A short description or summary of the news feed */
    public $summary;
    /** @var \JKingWeb\Lax\Date $dateModified The date at which the news feed was last modified
     * 
     * This property only records a date embedded in the news feed itself, not any dates from HTTP or the file system
     */
    public $dateModified;
    /** @var string $icon URL to a small icon for the news feed */
    public $icon;
    /** @var string $image URL to a large banner or poster image for the news feed */
    public $image;
    
    /** @var \JKingWeb\Lax\Category\Collection $categories A list of categories associated with the news feed as a whole */
    public $categories;
    /** @var \JKingWeb\Lax\Person\Collection $people A list of people (e.g. authors, contributors) associated with the news feed as a whole */
    public $people;
    /** @var \JKingWeb\Lax\Entry[] $entries An array of the news feed's entries */
    public $entries;

    /** @var \JKingWeb\Lax\Metadata $meta A collection of metadata not contained in the feed itself, usually from HTTP */
    public $meta;

    /** Parses a string to produce a Feed object
     * 
     * Most users will probably rather want the Feed::fetch() method
     * 
     * @param string $data The news feed to parse
     * @param string|null $contentType The HTTP Content-Type of the document, if available
     * @param string|null $url The URL used to retrieve the newsfeed, if applicable 
     */
    public static function parse(string $data, ?string $contentType = null, ?string $url = null): self {
        $out = new self;
        return $out;
    }
}
