<?php
/** @license MIT
 * Copyright 2018 J. King et al.
 * See LICENSE and AUTHORS files for details */

declare(strict_types=1);
namespace MensBeam\Lax\Parser\HTML;

use MensBeam\Lax\Date;
use MensBeam\Lax\Text;
use MensBeam\Lax\Url;
use MensBeam\Lax\MimeType;
use MensBeam\Lax\Schedule;
use MensBeam\Lax\Feed as FeedStruct;
use MensBeam\Lax\Parser\Exception;
use MensBeam\Lax\Parser\XML\XPath;
use MensBeam\Lax\Category\Collection as CategoryCollection;
use MensBeam\Lax\Person\Collection as PersonCollection;
use MensBeam\HTML\DOMParser;

class Feed extends Construct implements \MensBeam\Lax\Parser\Feed {
    use \MensBeam\Lax\Parser\AbstractFeed;

    protected const LIBXML_OPTIONS = \LIBXML_BIGLINES | \LIBXML_COMPACT | \LIBXML_HTML_NODEFDTD | \LIBXML_NOCDATA | \LIBXML_NOENT | \LIBXML_NONET | \LIBXML_NOERROR | LIBXML_NOWARNING;
    public const MIME_TYPES = [
        "text/html",
        "application/xhtml+xml",
    ];
    
    /** @var string */
    protected $data;
    /** @var string */
    protected $contentType;
    /** @var string */
    protected $url;
    /** @var \DOMElement */
    protected $subject;
    /** @var \DOMXpath */
    protected $xpath;

    /** Performs initialization of the instance */
    protected function init(FeedStruct $feed): FeedStruct {
        $type = MimeType::parse($this->contentType);
        if ($type && !in_array($type->essence, self::MIME_TYPES)) {
            throw new Exception("notHTMLType");
        }
        $parser = new DOMParser;
        if ($type && $type->essence === "application/xhtml+xml") {
            $this->document = $parser->parseFromString($this->data, $this->contentType);
            if ($this->document->documentElement->tagName === "parsererror" && $this->document->documentElement->namespaceURI === "http://www.mozilla.org/newlayout/xml/parsererror.xml") {
                // ignore XML parsing errors; we will reparse as HTML in this case
                $this->document = null;
            } elseif ($this->document->documentElement->namespaceURI !== XPath::NS['html']) {
                throw new Exception("notXHTML");
            }
        }
        if (!$this->document) {
            $this->document = $parser->parseFromString($this->data, "text/html;charset=".($type->params['charset'] ?? ""));
        }
        $this->document->documentURI = $this->url;
        $this->xpath = new \DOMXPath($this->document);
        $this->subject = $this->fetchElement("h-feed", "//*");
        if (!$this->subject) {
            throw new Exception("notHTMLFeed");
        }
        $feed->meta->url = $this->url;
        $feed->format = "h-feed";
        $feed->version = "1";
        return $feed;
    }

    /** {@inheritDoc} 
     * 
     * h-feeds do not have IDs, so this is always null.
    */
    public function getId(): ?string {
        return null;
    }

    public function getUrl(): ?Url {
        return null;
    }

    public function getTitle(): ?Text {
        return null;
    }

    public function getLink(): ?Url {
        return null;
    }

    public function getSummary(): ?Text {
        return null;
    }

    public function getDateModified(): ?Date {
        return null;
    }

    public function getIcon(): ?Url {
        return null;
    }

    public function getImage(): ?Url {
        return null;
    }

    public function getCategories(): CategoryCollection {
        return new CategoryCollection;
    }

    public function getPeople(): PersonCollection {
        return new PersonCollection;
    }

    public function getEntries(FeedStruct $feed): array {
        return [];
    }

    public function getSchedule(): Schedule {
        return new Schedule;
    }
}