<?php
/** @license MIT
 * Copyright 2018 J. King et al.
 * See LICENSE and AUTHORS files for details */

declare(strict_types=1);
namespace JKingWeb\Lax;

class Sanitizer {
    /** @var string[] An array of namespace URIs (the keys) and prefixes (the values). The prefixes are used in the element and attribute lists that follow */
    public $namespaces = [
        'http://www.w3.org/1999/xhtml'       => "html",
        'http://www.w3.org/2000/svg'         => "svg",
        'http://www.w3.org/1998/Math/MathML' => "math",
    ];
    /** @var string[] An array of elements to completely remove from the document, such as <script>. This should be used rather than the strip list below for empty elements, since XHTML documents can include content in usually empty elements */
    public $elemPurge = [
        "html:basefont",     // arbitrary styling
        "html:bgsound",      // audio outside the reader's control
        "html:button",       // form element
        "html:canvas",       // only useful to script
        "html:datalist",     // form element
        "html:dialog",       // expected to hold only form elements
        "html:embed",        // inherently unsafe
        "html:fieldset",     // expected to hold only form elements
        "html:frame",        // frames
        "html:frameset",     // frames
        "html:input",        // form element
        "html:isindex",      // form element
        "html:keygen",       // form element
        "html:label",        // form element
        "html:legend",       // form element
        "html:link",         // typically used to embed stylesheets
        "html:math",         // embedded MathML is too complicated and esoteric to support at this time
        "math:math",         // embedded MathML is too complicated and esoteric to support at this time
        "html:menuitem",     // form element
        "html:meter",        // form element
        "html:nextid",       // obsolete and obscure
        "html:nobr",         // potentially reader-hostile
        "html:optgroup",     // form element
        "html:option",       // form element
        "html:output",       // form element
        "html:param",        // always associated with programmatic objects
        "html:progress",     // form element
        "html:script",       // inherently unsafe
        "html:select",       // form element
        "html:slot",         // only useful to scripts (I think)
        "html:spacer",       // obsolete styling
        "html:style",        // arbitrary styling; potentially unsafe
        "html:svg",          // embedded SVG is too complicated and esoteric to support at this time
        "svg:svg",           // embedded SVG is too complicated and esoteric to support at this time
        "html:template",     // only useful to scripts
        "html:textarea",     // form element
    ];
    /** @var string[] An array of elements to remove from the document while leaving their contents behind, such as <font>. This should not be used for empty elements such as <img> as XHTML documents can include content in such elements */
    public $elemStrip = [
        "html:applet",       // inherently unsafe
        "html:blink",        // especially annoying styling
        "html:font",         // arbitrary styling
        "html:form",         // form element
        "html:marquee",      // especially annoying styling
        "html:noembed",      // ensure embed fallback content is actually displayed
        "html:noframes",     // ensure frame fallback content is actually displayed
        "html:object",       // usually unsafe
    ];
    /** @var array[] An array of elements which are allowed (the keys) and their allowed attributes (the values) */
    public $elemKeep = [
        'html:a'          => ["href", "download", "hreflang", "type", "coords", "shape"], // "target", "ping", "rel", "referrerpolicy"
        'html:abbr'       => [],
        'html:acronym'    => [],
        'html:address'    => [],
        'html:area'       => ["alt", "coords", "shape", "href", "target", "download"], // "ping", "rel", "referrerpolicy"
        'html:article'    => [],
        'html:aside'      => [],
        'html:audio'      => ["src", "crossorigin", "loop", "muted"], // "autoplay", "controls", "preload"
        'html:b'          => [],
        'html:base'       => ["href"], // "target"
        'html:bdi'        => [],
        'html:bdo'        => [],
        'html:big'        => [],
        'html:blockquote' => ["cite"],
        'html:body'       => [], // "onafterprint", "onbeforeprint", "onbeforeunload", "onhashchange", "onlanguagechange", "onmessage", "onmessageerror", "onoffline", "ononline", "onpagehide", "onpageshow", "onpopstate", "onrejectionhandled", "onstorage", "onunhandledrejection", "onunload"
        'html:br'         => [],
        'html:caption'    => [],
        'html:center'     => [],
        'html:cite'       => [],
        'html:code'       => [],
        'html:col'        => ["span"],
        'html:colgroup'   => ["span"],
        'html:data'       => ["value"],
        'html:dd'         => [],
        'html:del'        => ["cite", "datetime"],
        'html:details'    => ["open"],
        'html:dfn'        => [],
        'html:dir'        => [],
        'html:div'        => [],
        'html:dl'         => [],
        'html:dt'         => [],
        'html:em'         => [],
        'html:figcaption' => [],
        'html:figure'     => [],
        'html:footer'     => [],
        'html:h1'         => [],
        'html:h2'         => [],
        'html:h3'         => [],
        'html:h4'         => [],
        'html:h5'         => [],
        'html:h6'         => [],
        'html:head'       => [],
        'html:header'     => [],
        'html:hgroup'     => [],
        'html:hr'         => [],
        'html:html'       => [], // "manifest"
        'html:i'          => [],
        'html:iframe'     => ["src", "srcdoc", "width", "height"], // "name", "sandbox", "allowfullscreen", "allowpaymentrequest", "allowusermedia", "referrerpolicy"
        'html:img'        => ["alt", "src", "srcset", "usemap", "ismap", "width", "height"], // "crossorigin", "decoding", "referrerpolicy"
        'html:ins'        => ["cite", "datetime"],
        'html:kbd'        => [],
        'html:li'         => ["value"],
        'html:listing'    => [],
        'html:main'       => [],
        'html:map'        => ["name"],
        'html:mark'       => [],
        'html:menu'       => [],
        'html:meta'       => ["name", "http-equiv", "content", "charset"],
        'html:nav'        => [],
        'html:noscript'   => [],
        'html:ol'         => ["reversed", "start", "type"],
        'html:p'          => [],
        'html:picture'    => [],
        'html:plaintext'  => [],
        'html:pre'        => [],
        'html:q'          => ["cite"],
        'html:rb'         => [],
        'html:rp'         => [],
        'html:rt'         => [],
        'html:rtc'        => [],
        'html:ruby'       => [],
        'html:s'          => [],
        'html:samp'       => [],
        'html:section'    => [],
        'html:small'      => [],
        'html:source'     => ["src", "type", "srcset", "sizes", "media"],
        'html:span'       => [],
        'html:strike'     => [],
        'html:strong'     => [],
        'html:sub'        => [],
        'html:summary'    => [],
        'html:sup'        => [],
        'html:table'      => ["summary"],
        'html:tbody'      => [],
        'html:td'         => ["colspan", "rowspan", "headers", "scope", "abbr", "axis"],
        'html:tfoot'      => [],
        'html:th'         => ["colspan", "rowspan", "headers", "scope", "abbr", "axis"],
        'html:thead'      => [],
        'html:time'       => ["datetime"],
        'html:title'      => [],
        'html:tr'         => [],
        'html:track'      => ["default", "kind", "label", "src", "srclang"],
        'html:tt'         => [],
        'html:u'          => [],
        'html:ul'         => ["type"],
        'html:var'        => [],
        'html:video'      => ["src", "crossorigin", "poster", "loop", "width", "height"], // "preload", "autoplay", "controls", "playsinline", "muted"
        'html:wbr'        => [],
        'html:xmp'        => [],
    ];
    /** @var string[] An array of attribute names which are allowed on any element */
    public $attrKeep = [
        "accesskey",
        "align",
        //"autocapitalize",     // not useful for static content
        //"contenteditable",    // not useful for static content
        "class",
        "dir",
        //"draggable",          // not useful for static content
        "hidden",
        //"inputmode",          // not useful for static content
        //"is",                 // only used with custom elements
        "id",
        "itemid",
        "itemprop",
        "itemref",
        "itemscope",
        "itemtype",
        "lang",
        //"nonce",              // only used via scripts (I think)
        //"slot",               // only used via scripts (I think)
        //"spellcheck",         // not useful for static content
        //"style",              // arbitrary styling; potentially unsafe
        "tabindex",
        "title",
        "translate",
        // WAI-ARIA
        "aria-describedby",
        "aria-disabled",
        "aria-label",
        "role",
        // For compatibility with XHTML
        "xmlns",
    ];
    /** @var string[] An array of attribute names whose content is a URL */
    public $attrUrl = [
        "href",
        "src",
        "cite",
        "poster",
    ];

    /**
     * Sanitizes a DOMDocument object, returning the same document, modified
     *
     * The document may be an HTML document or or any partial XHTML tree, possibly mixed with other XML vocabularies
     *
     * The document's documentURI is assumed to already be set
     */
    public function processDocument(\DOMDocument $doc, string $url): \DOMDocument {
        // determine if the document is non-XML HTML
        $isHtml = ($doc->documentElement->tagName === "html" && $doc->documentElement->namespaceURI === "");
        // loop through each element in the document
        foreach ((new \DOMXPath($doc))->query("//*") as $node) {
            // resolve a qualified name for the element
            if (($isHtml && $node->namespaceURI === "") || $node->namespaceURI === "http://www.w3.org/1999/xhtml") {
                $qName = "html:".$node->tagName;
            } elseif ($node->namespaceURI === "") {
                $qName = $node->tagName;
            } elseif (isset($this->namespaces[$node->namespaceURI])) {
                $qName = $this->namespaces[$node->namespaceURI].":".$node->tagName;
            } else {
                $qName = $node->namespaceURI.":".$node->tagName;
            }
            if (in_array($qName, $this->elemPurge)) {
                // if the element is in the purge list, delete it from the document along with its children
                $node->parentNode->removeChild($node);
            } elseif (in_array($qName, $this->elemStrip) || !isset($this->elemKeep[$qName])) {
                // if the element is in the strip list or not in the keep list, delete it from the document while putting its children in its place
                if ($node->hasChildNodes()) {
                    $f = $doc->createDocumentFragment();
                    do {
                        $f->appendChild($node->firstChild);
                    } while ($node->hasChildNodes());
                    $node->parentNode->insertBefore($f, $node);
                }
                $node->parentNode->removeChild($node);
            } else {
                // if the element is in the keep list, clean up its attributes
                foreach (iterator_to_array($node->attributes) as $attr) { // we use an array
                    if (!in_array($attr->name, $this->attrKeep) && !(isset($this->elemKeep[$qName]) && in_array($attr->name, $this->elemKeep[$qName]))) {
                        // if the attribute is not allowed globally or for the element, remove it
                        $attr->ownerElement->removeAttributeNode($attr);
                    } else {
                        // otherwise normalize it if it's a URL
                    }
                }
            }
        }
        // return the result
        return $doc;
    }
}
