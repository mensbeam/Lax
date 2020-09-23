<?php
/** @license MIT
 * Copyright 2018 J. King et al.
 * See LICENSE and AUTHORS files for details */

declare(strict_types=1);
namespace MensBeam\Lax\Parser\HTML;

abstract class Construct {
    use \MensBeam\Lax\Parser\Construct;

    /** @var \DOMDocument */
    protected $document;
    /** @var \DOMXPath */
    protected $xpath;
    /** @var \DOMElement */
    protected $subject;

    /** Retrieves an element node based on class name and optionally XPath query */
    protected function fetchElement(string $class, string $query = "/*", \DOMNode $context = null): ?\DOMElement {
        $el = $this->xpath->query($query."[contains(concat(' ', normalize-space(@class), ' '), ' $class ')][1]", $context ?? $this->subject);
        assert($el !== false, "Invalid XPath query: \"$query\"");
        return $el->length ? $el->item(0) : null;
    }

    public function getLang(): ?string {
        // walk up the tree looking for the nearest language tag, preferring XML over HTML when appropriate
        $el = $this->subject;
        $out = null;
        $xhtml = (bool) $el->ownerDocument->documentElement->namespaceURI;
        do {
            if ($xhtml) {
                $out = trim($el->getAttributeNS("http://www.w3.org/XML/1998/namespace", "lang") ?? "");
                $out = strlen($out) ? $out : null;
            }
            if (is_null($out)) {
                $out = trim($el->getAttribute("lang") ?? "");
                $out = strlen($out) ? $out : null;
            }
            $el = $el->parentNode;
        } while (is_null($out) && $el);
        return $out;
    }
}
