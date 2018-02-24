<?php
/** @license MIT
 * Copyright 2018 J. King et al.
 * See LICENSE and AUTHORS files for details */

declare(strict_types=1);
namespace JKingWeb\Lax;

class PersonCollection extends Collection {
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
    public function filterRole(string ...$role): self {
        $out = new static;
        foreach ($this as $p) {
            if (in_array($p->role, $role)) {
                $out[] = $p;
            }
        }
        return $out;
    }

    /** Returns a collection filtered to exclude the specified roles */
    public function filterOutRole(string ...$role): self {
        $out = new static;
        foreach ($this as $p) {
            if (!in_array($p->role, $role)) {
                $out[] = $p;
            }
        }
        return $out;
    }

    /** Merges one or more other collections' items into this one 
     * 
     * The returned collection is the original instance, modified
    */
    public function merge(PersonCollection ...$coll): self {
        foreach ($coll as $c) {
            foreach ($c as $p) {
                $this[] = $p;
            }
        }
        return $this;
    }
}
