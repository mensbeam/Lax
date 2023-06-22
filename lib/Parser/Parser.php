<?php
/** @license MIT
 * Copyright 2018 J. King et al.
 * See LICENSE and AUTHORS files for details */

declare(strict_types=1);
namespace MensBeam\Lax\Parser;

use MensBeam\Lax\Feed;
use MensBeam\Lax\Metadata;
use MensBeam\Lax\MimeType;
use MensBeam\Lax\Parser\JSON\Feed as JSONParser;
use MensBeam\Lax\Parser\XML\Feed as XMLParser;
use Psr\Http\Message\MessageInterface;

abstract class Parser {
    public static function findParserForType(string $type): ?string {
        $normalized = MimeType::parse($type);
        if ($normalized) {
            $normalized = $normalized->essence;
            foreach ([XMLParser::class, JSONParser::class] as $class) {
                if (in_array($normalized, $class::MIME_TYPES)) {
                    return $class;
                }
            }
            throw new Exception("notSupportedType");
        }
        return null;
    }

    public static function findTypeForContent(string $data): string {
        if (preg_match('/^\s*\{\s*"./s', $data)) {
            return "application/json";
        } elseif (preg_match('/^\s*<\?xml/s', $data)) {
            return "application/xml";
        } elseif (preg_match('/^\s*</s', $data)) {
            // distinguish between XML feeds and HTML; first skip any comments before the root element
            $offset = preg_match('/^\s*(<!(?=--)([^-]|-(?!->))*-->\s*)*/s', $data, $match) ? strlen($match[0]) : 0;
            $prefix = substr($data, $offset, 100);
            if (preg_match('/^<(?:!DOCTYPE\s+html|html|body|head|table|div|title|p|link|meta)[\s>]/si', $prefix)) {
                return "text/html";
            } elseif (preg_match('/^<rss[\s>\/]/', $prefix)) {
                return "application/rss+xml";
            } elseif (preg_match('/^<(?:[A-Za-z0-9\-\._]+:)?(feed|RDF)\s/', $prefix, $match)) {
                if ($match[1] === "feed") {
                    return "application/atom+xml";
                } else {
                    return "application/rdf+xml";
                }
            } else {
                // FIIXME: Is there a better fallback that could used here?
                return "application/xml";
            }
        } else {
            return "application/octet-stream";
        }
    }

    /** Parses a string into a supplied feed, irrespective of format
     *
     * @param \MensBeam\Lax\Feed $feed The newfeed object to populate
     * @param string $data The newsfeed to parse
     * @param string|null $contentType The HTTP Content-Type of the document; it is considered authoritative if supplied
     * @param string|null $url The URL used to retrieve the newsfeed, if applicable
     */
    public static function parseDataIntoFeed(Feed $feed, string $data, ?string $contentType = null, ?string $url = null): Feed {
        $type = $contentType ?? $feed->meta->type ?? self::findTypeForContent($data);
        $url = $url ?? $feed->meta->url;
        $class = self::findParserForType($type);
        $parser = new $class($data, (string) $contentType, (string) $url);
        return $parser->parse($feed);
    }

    /** Parses an HTTP message's metadata into a metadata object
     *
     * @param \MensBeam\Lax\Feed $feed The newfeed object to populate
     * @param \Psr\Http\Message\MessageInterface $data The HTTP message to parse
     * @param string|null $url The URL used to retrieve the newsfeed, if applicable
     */
    public static function parseMessageIntoMeta(Metadata $meta, MessageInterface $data, ?string $url = null): Metadata {
        $parser = new HTTP\Message($data, $url);
        $parser->parse($meta);
        return $meta;
    }
}
