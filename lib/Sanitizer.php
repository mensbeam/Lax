<?php
/** @license MIT
 * Copyright 2018 J. King et al.
 * See LICENSE and AUTHORS files for details */

declare(strict_types=1);
namespace JKingWeb\Lax;

class Sanitizer {
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
    ];
    public $tagPurge = [
        "basefont",     // arbitrary styling
        "button",       // form element
        "canvas",       // only useful to script
        "datalist",     // form element
        "dialog",       // expected to hold only form elements
        "embed",        // inherently unsafe
        "fieldset",     // expected to hold only form elements
        "frame",        // frames
        "frameset",     // frames
        "input",        // form element
        "isindex",      // form element
        "label",        // form element
        "legend",       // form element
        "link",         // typically used to embed stylesheets
        "math",         // embedded MathML is too complicated and esoteric to support at this time
        "meter",        // form element
        "optgroup",     // form element
        "option",       // form element
        "output",       // form element
        "param",        // always associated with programmatic objects
        "progress",     // form element
        "script",       // inherently unsafe
        "select",       // form element
        "slot",         // only useful to scripts (I think)
        "style",        // arbitrary styling; potentially unsafe
        "svg",          // embedded SVG is too complicated and esoteric to support at this time
        "template",     // only useful to scripts
        "textarea",     // form element
    ];
    public $tagStrip = [
        "applet",       // inherently unsafe
        "blink",        // especially annoying styling
        "font",         // arbitrary styling
        "form",         // form element
        "marquee",      // especially annoying styling
        "noframes",     // ensure frame fallback content is actually displayed
        "object",       // usually unsafe
    ];
    public $tagKeep = [
        'a'          => ["href", "download", "hreflang", "type"], // "target", "ping", "rel", "referrerpolicy"
        'abbr'       => [],
        'acronym'    => [],
        'address'    => [],
        'area'       => ["alt", "coords", "shape", "href", "target", "download"], // "ping", "rel", "referrerpolicy"
        'article'    => [],
        'aside'      => [],
        'audio'      => ["src", "crossorigin", "preload", "loop", "muted", "controls"], // "autoplay"
        'b'          => [],
        'base'       => ["href"], // "target"
        'bdi'        => [],
        'bdo'        => [],
        'big'        => [],
        'blockquote' => ["cite"],
        'body'       => [], // "onafterprint", "onbeforeprint", "onbeforeunload", "onhashchange", "onlanguagechange", "onmessage", "onmessageerror", "onoffline", "ononline", "onpagehide", "onpageshow", "onpopstate", "onrejectionhandled", "onstorage", "onunhandledrejection", "onunload"
        'br'         => [],
        'caption'    => [],
        'center'     => [],
        'cite'       => [],
        'code'       => [],
        'col'        => ["span"],
        'colgroup'   => ["span"],
        'data'       => ["value"],
        'dd'         => [],
        'del'        => ["cite", "datetime"],
        'details'    => ["open"],
        'dfn'        => [],
        'dir'        => [],
        'div'        => [],
        'dl'         => [],
        'dt'         => [],
        'em'         => [],
        'figcaption' => [],
        'figure'     => [],
        'footer'     => [],
        'h1'         => [],
        'h2'         => [],
        'h3'         => [],
        'h4'         => [],
        'h5'         => [],
        'h6'         => [],
        'head'       => [],
        'header'     => [],
        'hgroup'     => [],
        'hr'         => [],
        'html'       => [], // "manifest"
        'i'          => [],
        'iframe'     => ["src", "srcdoc", "name", "sandbox", "allowfullscreen", "allowpaymentrequest", "allowusermedia", "width", "height", "referrerpolicy"],
        'img'        => ["alt", "src", "srcset", "crossorigin", "usemap", "ismap", "width", "height", "decoding", "referrerpolicy"],
        'ins'        => ["cite", "datetime"],
        'kbd'        => [],
        'li'         => ["value"],
        'main'       => [],
        'map'        => ["name"],
        'mark'       => [],
        'menu'       => [],
        'meta'       => ["name", "http-equiv", "content", "charset"],
        'nav'        => [],
        'noscript'   => [],
        'ol'         => ["reversed", "start", "type"],
        'p'          => [],
        'picture'    => [],
        'pre'        => [],
        'q'          => ["cite"],
        'rp'         => [],
        'rt'         => [],
        'ruby'       => [],
        's'          => [],
        'samp'       => [],
        'section'    => [],
        'small'      => [],
        'source'     => ["src", "type srcset", "sizes", "media"],
        'span'       => [],
        'strike'     => [],
        'strong'     => [],
        'sub'        => [],
        'summary'    => [],
        'sup'        => [],
        'table'      => [],
        'tbody'      => [],
        'td'         => ["colspan", "rowspan", "headers"],
        'tfoot'      => [],
        'th'         => ["colspan", "rowspan", "headers", "scope", "abbr"],
        'thead'      => [],
        'time'       => ["datetime"],
        'title'      => [],
        'tr'         => [],
        'track'      => ["default", "kind", "label", "src", "srclang"],
        'tt'         => [],
        'u'          => [],
        'ul'         => [],
        'var'        => [],
        'video'      => ["src", "crossorigin", "poster", "preload", "autoplay", "playsinline", "loop", "muted", "controls", "width", "height"],
        'wbr'        => [],
    ];
}
