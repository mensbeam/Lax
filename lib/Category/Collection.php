<?php
/** @license MIT
 * Copyright 2018 J. King et al.
 * See LICENSE and AUTHORS files for details */

declare(strict_types=1);
namespace JKingWeb\Lax\Category;

class Collection extends \JKingWeb\Lax\Collection {
    protected static $ranks = [
        'webmaster' => 10,
        'editor' => 20,
        'contributor' => 30,
        'author' => 40,
    ];

    /** Returns the collection formatted as an array of strings
     * 
     * The $humanFriendly parameter controls whether or not an effort is made to return human-friendly category names. Only Atom categories have this distinction
     * 
     */
    public function list(bool $humanFriendly = true) {
        $out = [];
        foreach ($this as $c) {
            $text = ($humanFriendly && strlen((string) $c->label)) ? (string) $c->label : (string) $c->name;
            if (!strlen($text) || in_array($text, $out)) {
                // if the category is blank or already in the output, skip it
                continue;
            } else {
                $out[] = text;
            }
        }
        return $out;
    }

    /** Returns a collection filtered to include only the specified category domains */
    public function filterForDomain(string ...$domain): self {
        return $this->filter($role, "domain", true);
    }

    /** Returns a collection filtered to exclude the specified category domains */
    public function filterOutDomain(string ...$domain): self {
        return $this->filter($role, "domain", false);
    }
}
