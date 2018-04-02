<?php
/** @license MIT
 * Copyright 2018 J. King et al.
 * See LICENSE and AUTHORS files for details */

declare(strict_types=1);
namespace JKingWeb\Lax;

class Sanitizer {
    public $namespaces = [
        'http://www.w3.org/1999/xhtml'       => "html",
        'http://www.w3.org/2000/svg'         => "svg",
        'http://www.w3.org/1998/Math/MathML' => "math",
    ];

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
    public $tagPurge = [
        "html:basefont",     // arbitrary styling
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
        "html:label",        // form element
        "html:legend",       // form element
        "html:link",         // typically used to embed stylesheets
        "html:math",         // embedded MathML is too complicated and esoteric to support at this time
        "math:math",         // embedded MathML is too complicated and esoteric to support at this time
        "html:meter",        // form element
        "html:optgroup",     // form element
        "html:option",       // form element
        "html:output",       // form element
        "html:param",        // always associated with programmatic objects
        "html:progress",     // form element
        "html:script",       // inherently unsafe
        "html:select",       // form element
        "html:slot",         // only useful to scripts (I think)
        "html:style",        // arbitrary styling; potentially unsafe
        "html:svg",          // embedded SVG is too complicated and esoteric to support at this time
        "svg:svg",           // embedded SVG is too complicated and esoteric to support at this time
        "html:template",     // only useful to scripts
        "html:textarea",     // form element
    ];
    public $tagStrip = [
        "html:applet",       // inherently unsafe
        "html:blink",        // especially annoying styling
        "html:font",         // arbitrary styling
        "html:form",         // form element
        "html:marquee",      // especially annoying styling
        "html:noframes",     // ensure frame fallback content is actually displayed
        "html:object",       // usually unsafe
    ];
    public $tagKeep = [
        'html:a'          => ["href", "download", "hreflang", "type"], // "target", "ping", "rel", "referrerpolicy"
        'html:abbr'       => [],
        'html:acronym'    => [],
        'html:address'    => [],
        'html:area'       => ["alt", "coords", "shape", "href", "target", "download"], // "ping", "rel", "referrerpolicy"
        'html:article'    => [],
        'html:aside'      => [],
        'html:audio'      => ["src", "crossorigin", "preload", "loop", "muted", "controls"], // "autoplay"
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
        'html:iframe'     => ["src", "srcdoc", "name", "sandbox", "allowfullscreen", "allowpaymentrequest", "allowusermedia", "width", "height", "referrerpolicy"],
        'html:img'        => ["alt", "src", "srcset", "crossorigin", "usemap", "ismap", "width", "height", "decoding", "referrerpolicy"],
        'html:ins'        => ["cite", "datetime"],
        'html:kbd'        => [],
        'html:li'         => ["value"],
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
        'html:pre'        => [],
        'html:q'          => ["cite"],
        'html:rp'         => [],
        'html:rt'         => [],
        'html:ruby'       => [],
        'html:s'          => [],
        'html:samp'       => [],
        'html:section'    => [],
        'html:small'      => [],
        'html:source'     => ["src", "type srcset", "sizes", "media"],
        'html:span'       => [],
        'html:strike'     => [],
        'html:strong'     => [],
        'html:sub'        => [],
        'html:summary'    => [],
        'html:sup'        => [],
        'html:table'      => [],
        'html:tbody'      => [],
        'html:td'         => ["colspan", "rowspan", "headers"],
        'html:tfoot'      => [],
        'html:th'         => ["colspan", "rowspan", "headers", "scope", "abbr"],
        'html:thead'      => [],
        'html:time'       => ["datetime"],
        'html:title'      => [],
        'html:tr'         => [],
        'html:track'      => ["default", "kind", "label", "src", "srclang"],
        'html:tt'         => [],
        'html:u'          => [],
        'html:ul'         => [],
        'html:var'        => [],
        'html:video'      => ["src", "crossorigin", "poster", "preload", "autoplay", "playsinline", "loop", "muted", "controls", "width", "height"],
        'html:wbr'        => [],
    ];

    public function processDocument(\DOMDocument $doc, string $url): \DOMDocument {
        echo $doc->saveHTML();
        // determine if the document is non-XML HTML
        $isHtml = ($doc->documentElement->tagName=="html" && $doc->documentElement->namespaceURI=="");
        // loop through each element in the document
        foreach ((new \DOMXPath($doc))->query("//*") as $node) {
            // resolve a qualified name for the element
            if (($isHtml && $node->namespaceURI=="") || $node->namespaceURI=="http://www.w3.org/1999/xhtml") {
                $qName = "html:".$node->tagName;
            } elseif ($node->namespaceURI=="") {
                $qName = $node->tagName;
            } elseif (isset($this->namespaces[$node->namespaceURI])) {
                $qName = $this->namespaces[$node->namespaceURI].":".$node->tagName;
            } else {
                $qName = $node->namespaceURI.":".$node->tagName;
            }
            if (in_array($qName, $this->tagPurge)) {
                // if the element is in the purge list, delete it from the document along with its children
                $node->parentNode->removeChild($node);
            } elseif (in_array($qName, $this->tagStrip) || !isset($this->tagKeep[$qName])) {
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
                // if the element is in the keep list, do nothing (for now)
            }
        }
        // return the result
        return $doc;
    }
}
