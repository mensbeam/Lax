<?php
/** @license MIT
 * Copyright 2018 J. King et al.
 * See LICENSE and AUTHORS files for details */

declare(strict_types=1);
namespace JKingWeb\Lax\JSON\Primitives;

use JKingWeb\Lax\Person\Person;
use JKingWeb\Lax\Person\Collection as PersonCollection;
use JKingWeb\Lax\Category\Collection as CategoryCollection;

trait Construct {

    /** Primitive function to fetch the author from v1 JSON feeds */
    protected function getPeopleV1() {
        $author = $this->fetchMember("author", "object");
        if (!isset($author)) {
            return null;
        } else {
            $out = new PersonCollection;
            $p = new Person;
            $p->name = $this->fetchMember("name", "str", $author) ?? "";
            $p->url = $this->fetchUrl("url", $author) ?? "";
            $p->avatar = $this->fetchUrl("avatar", $author) ?? "";
            $p->role = "author";
            $out[] = $p;
            return $out;
        }
    }
}
