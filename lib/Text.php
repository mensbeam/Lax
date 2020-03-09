<?php
/** @license MIT
 * Copyright 2018 J. King et al.
 * See LICENSE and AUTHORS files for details */

declare(strict_types=1);
namespace JKingWeb\Lax;

class Text {
    public $plain;
    public $html;
    public $xhtml;
    public $loose;

    public $htmlBase;
    public $xhtmlBase;

    public function __construct(string $data = null, string $type = "plain") {
        assert(in_array($type, ["plain", "html", "xhtml", "loose"]), new \InvalidArgumentException);
        $this->$type = $data;
    }
}
