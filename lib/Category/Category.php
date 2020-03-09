<?php
/** @license MIT
 * Copyright 2018 J. King et al.
 * See LICENSE and AUTHORS files for details */

declare(strict_types=1);
namespace JKingWeb\Lax\Category;

class Category {
    public $name = "";
    public $label = "";
    public $domain = "";

    public function __toString() {
        return strlen(strlen((string) $this->label)) ? $this->label : $this->name;
    }
}
