<?php
/** @license MIT
 * Copyright 2018 J. King et al.
 * See LICENSE and AUTHORS files for details */

declare(strict_types=1);
namespace JKingWeb\Lax\Parser\JSON;

use JKingWeb\Lax\Date;
use JKingWeb\Lax\Text;

trait Construct {
    use \JKingWeb\Lax\Parser\Construct;

    /** Returns an object member if the member exists and is of the expected type
     * 
     * Returns null otherwise
     */
    protected function fetchMember(string $key, string $type, \stdClass $obj = null) {
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

    /** Returns an object member as a resolved URL */
    protected function fetchUrl(string $key, \stdClass $obj = null): ?string {
        $url = $this->fetchMember($key, "str", $obj);
        return (!is_null($url)) ? $this->resolveUrl($url, $this->url) : null;
    }

    /** Returns an object member as a parsed date */
    protected function fetchDate(string $key, \stdClass $obj = null): ?Date {
        return $this->parseDate($this->fetchMember($key, "str", $obj) ?? "");
    }

    protected function fetchText(string $key, \stdClass $obj = null): ?Text {
        $t = $this->fetchMember($key, "str", $obj);
        if (!is_null($t)) {
            return new Text($t);
        }
        return null;
    }
}
