<?php
/** @license MIT
 * Copyright 2018 J. King et al.
 * See LICENSE and AUTHORS files for details */

declare(strict_types=1);
namespace JKingWeb\Lax\Person;

class Collection extends \JKingWeb\Lax\Collection {
    protected static $ranks = [
        'webmaster' => 10,
        'editor' => 20,
        'contributor' => 30,
        'author' => 40,
    ];
    
    /** Returns the primary person of the collection
     * 
     * The primary is the first member of the highest-weight role
     * 
     * Roles are ranked thus:
     * author > contributor > editor > webmaster > (anything else)
     * 
     */
    public function primary() {
        $out = null;
        foreach ($this as $p) {
            if (!$out) {
                $out = $p;
            } elseif (!isset(static::ranks[$p->role])) {
                continue;
            } elseif (static::ranks[$p->role] > static::ranks[$out->role]) {
                $out = $p;
            }
        }
        return $out;
    }

    /** Returns a collection filtered to include only the specified roles */
    public function filterForRole(string ...$role): self {
        return $this->filter($role, "role", true);
    }

    /** Returns a collection filtered to exclude the specified roles */
    public function filterOutRole(string ...$role): self {
        return $this->filter($role, "role", false);
    }
}
