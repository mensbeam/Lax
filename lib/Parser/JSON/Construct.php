<?php
/** @license MIT
 * Copyright 2018 J. King et al.
 * See LICENSE and AUTHORS files for details */

declare(strict_types=1);
namespace MensBeam\Lax\Parser\JSON;

use MensBeam\Lax\Date;
use MensBeam\Lax\MimeType;
use MensBeam\Lax\Text;
use MensBeam\Lax\Person\Collection as PersonCollection;
use MensBeam\Lax\Person\Person;
use MensBeam\Lax\Url;

abstract class Construct {
    use \MensBeam\Lax\Parser\Construct;

    /** Returns an object member if the member exists and is of the expected type
     *
     * Returns null otherwise
     */
    protected function fetchMember(string $key, string $type, ?\stdClass $obj = null) {
        $obj = $obj ?? $this->data;
        if (!isset($obj->$key)) {
            return null;
        }
        $type = strtolower($type);
        $type = ['bool' => "boolean", 'int' => "integer", 'float' => "double", 'str' => "string"][$type] ?? $type;
        if (strtolower(gettype($obj->$key)) === $type) {
            return $obj->$key;
        } else {
            return null;
        }
    }

    /** Returns an object member as a resolved and normalized URL */
    protected function fetchUrl(string $key, ?\stdClass $obj = null): ?Url {
        $url = $this->fetchMember($key, "str", $obj);
        try {
            return (!is_null($url)) ? new Url($url, $this->url) : null;
        } catch (\InvalidArgumentException $e) {
            return null;
        }
    }

    /** Returns a media type from an object member or from a URL's file name when possible */
    protected function fetchType(string $key, ?Url $url, ?\stdClass $obj = null): ?MimeType {
        $type = $this->fetchMember($key, "str", $obj) ?? "";
        return MimeType::parseLoose($type, $url);
    }

    /** Returns an object member as a parsed date */
    protected function fetchDate(string $key, ?\stdClass $obj = null): ?Date {
        return Date::createFromString($this->fetchMember($key, "str", $obj) ?? "");
    }

    /** Returns a plain-text string object member wrapped in a Text object */
    protected function fetchText(string $key, ?\stdClass $obj = null): ?Text {
        $t = $this->fetchMember($key, "str", $obj);
        if (!is_null($t)) {
            return new Text($t);
        }
        return null;
    }

    /** Retrieves the collection of authors as provided by version 1.1 of JSON Feed */
    protected function getAuthorsV1(): ?PersonCollection {
        $arr = $this->fetchMember("authors", "array");
        if (!is_null($arr)) {
            $out = new PersonCollection;
            foreach ($arr as $o) {
                if (is_object($o) && $p = $this->parseAuthor($o)) {
                    $out[] = $p;
                }
            }
            return sizeof($out) ? $out : null;
        }
        return null;
    }

    /** Retrieves a collection containing a single author as provided by Version 1 of JSON Feed */
    protected function getAuthorV1(): ?PersonCollection {
        $o = $this->fetchMember("author", "object");
        if ($o) {
            $p = $this->parseAuthor($o);
            if ($p) {
                $out = new PersonCollection;
                $out[] = $p;
                return $out;
            }
        }
        return null;
    }

    protected function parseAuthor(\stdClass $o): ?Person {
        $p = new Person;
        $p->name = $this->fetchMember("name", "str", $o);
        $p->url = $this->fetchUrl("url", $o);
        $p->avatar = $this->fetchUrl("avatar", $o);
        if (!$this->empty($p)) {
            // if any keys are set the person is valid
            $p->role = "author";
            return $p;
        }
        return null;
    }
}
