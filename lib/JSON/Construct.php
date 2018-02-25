<?php
/** @license MIT
 * Copyright 2018 J. King et al.
 * See LICENSE and AUTHORS files for details */

declare(strict_types=1);
namespace JKingWeb\Lax\JSON;

use JKingWeb\Lax\Person\Person;
use JKingWeb\Lax\Person\Collection as PersonCollection;

trait Construct {
    use \JKingWeb\Lax\Construct;

    /** @var object */
    public    $json;

    /** Returns an object member if the member exists and is of the expected type
     * 
     * Returns null otherwise
     */
    protected function fetchMember(string $key, string $type, \stdClass $obj = null) {
        $obj = $obj ?? $this->json;
        if (!isset($obj->$key)) {
            return null;
        }
        $type = strtolower($type);
        switch ($type) {
            case "bool":
                $type = "boolean";
                break;
            case "int":
                $type = "integer";
                break;
            case "float":
                $type = "double";
                break;
            case "str":
                $type = "string";
                break;
        }
        if (strtolower(gettype($obj->$key))==$type) {
            return $obj->$key;
        } else {
            return null;
        }
    }

    protected function fetchUrl(string $key, \stdClass $obj = null) {
        $url = $this->fetchMember($key, "str", $obj);
        return (!is_null($url)) ? $this->resolveUrl($url, $this->url) : $url;
    }
}
