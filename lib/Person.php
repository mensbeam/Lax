<?php
/** @license MIT
 * Copyright 2018 J. King et al.
 * See LICENSE and AUTHORS files for details */

declare(strict_types=1);
namespace JKingWeb\Lax;

class Person {
    public $name = "";
    public $mail = "";
    public $url  = "";
    public $role = "";

    public function __toString() {
        return strlen($this->mail) ? $this->name."<".$this->mail.">" : $this->name;
    }
}
