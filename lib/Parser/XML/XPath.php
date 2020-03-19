<?php
/** @license MIT
 * Copyright 2018 J. King et al.
 * See LICENSE and AUTHORS files for details */

declare(strict_types=1);
namespace MensBeam\Lax\Parser\XML;

class XPath extends \DOMXpath {
    public const NS = [
        'atom'  => "http://www.w3.org/2005/Atom",                       // Atom syndication format                  https://tools.ietf.org/html/rfc4287
        'rss2'  => "", // RSS 2.0 does not have a namespace             // Really Simple Syndication 2.0.11         http://www.rssboard.org/rss-specification
        'rss1'  => "http://purl.org/rss/1.0/",                          // RDF site summary 1.0                     http://purl.org/rss/1.0/spec
        'rss0'  => "http://channel.netscape.com/rdf/simple/0.9/",       // RDF Site Summary 0.90                    http://www.rssboard.org/rss-0-9-0
        'dc'    => "http://purl.org/dc/elements/1.1/",                  // Dublin Core metadata                     http://purl.org/rss/1.0/modules/dc/
        'sched' => "http://purl.org/rss/1.0/modules/syndication/",      // Syndication schedule extension           http://purl.org/rss/1.0/modules/syndication/
        'enc'   => "http://purl.org/rss/1.0/modules/content/",          // Explicitly encoded content extension     http://purl.org/rss/1.0/modules/content/
        'media' => "http://search.yahoo.com/mrss/",                     // Embedded media extension                 http://www.rssboard.org/media-rss
        'rdf'   => "http://www.w3.org/1999/02/22-rdf-syntax-ns#",       // Resource Description Framework
        'xhtml' => "http://www.w3.org/1999/xhtml",                      // XHTML
        'apple' => "http://www.itunes.com/dtds/podcast-1.0.dtd",        // iTunes podcasts                          https://help.apple.com/itc/podcasts_connect/#/itcb54353390
        'gplay' => "http://www.google.com/schemas/play-podcasts/1.0",   // Google Play podcasts                     https://support.google.com/googleplay/podcasts/answer/6260341
    ];

    public $rss2 = false;

    /** Returns an XPath processor with various necessary namespace prefixes defined */
    public function __construct(\DOMDocument $doc) {
        parent::__construct($doc);
        foreach (self::NS as $prefix => $url) {
            $this->registerNamespace($prefix, $url);
        }
    }

    /** {@inheritDoc} */
    public function query($expression, $contextnode = null, $registerNS = true) {
        $expression = $this->rss2 ? str_replace("rss2:", "", $expression) : $expression;
        return parent::query($expression, $contextnode, $registerNS);
    }
}
