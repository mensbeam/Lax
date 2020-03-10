<?php
/** @license MIT
 * Copyright 2018 J. King et al.
 * See LICENSE and AUTHORS files for details */

declare(strict_types=1);
namespace MensBeam\Lax\Person;

class Person {
    public $name = null;
    public $mail = null;
    public $url = null;
    public $role = null;
    public $avatar = null;

    public function __toString() {
        $name = strlen((string) $this->name) > 0;
        $mail = strlen((string) $this->mail) > 0;
        if ($name && $mail) {
            return "{$this->name} <{$this->mail}>";
        } elseif ($name) {
            return $this->name;
        } elseif ($mail) {
            return $this->mail;
        } else {
            return "";
        }
    }
}
