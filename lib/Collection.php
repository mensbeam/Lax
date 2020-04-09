<?php
/** @license MIT
 * Copyright 2018 J. King et al.
 * See LICENSE and AUTHORS files for details */

declare(strict_types=1);
namespace MensBeam\Lax;

abstract class Collection implements \IteratorAggregate, \ArrayAccess, \Countable, \JsonSerializable {
    protected $data = [];

    /** Implementation for IteratorAggregate */
    public function getIterator(): \Traversable {
        return ($this->data instanceof \Traversable) ? $this->data : new \ArrayIterator((array) $this->data);
    }

    /** Implementation for JsonSerializable */
    public function jsonSerialize() {
        return $this->data;
    }

    /** Implementation for Countable */
    public function count(): int {
        return count($this->data);
    }

    /** Implementation for ArrayAccess */
    public function offsetExists($offset): bool {
        return isset($this->data[$offset]);
    }

    /** Implementation for ArrayAccess */
    public function offsetGet($offset) {
        return $this->data[$offset];
    }

    /** Implementation for ArrayAccess */
    public function offsetSet($offset, $value) {
        if (is_null($offset)) {
            $this->data[] = $value;
        } else {
            $this->data[$offset] = $value;
        }
    }

    /** Implementation for ArrayAccess */
    public function offsetUnset($offset) {
        unset($this->data[$offset]);
    }

    /** Merges one or more other collections' items into this one
     *
     * The returned collection is the original instance, modified
     */
    public function merge(self ...$coll): self {
        foreach ($coll as $c) {
            foreach ($c as $p) {
                $this[] = $p;
            }
        }
        return $this;
    }

    /** Returns a collection filtered along a given axis which includes or excludes only the specified terms
     *
     * $terms is the list of values to include or exclude in the result
     *
     * $axis is the property of each collection member which value is to be checked against the terms
     *
     * $inclusive specified whether the terms are to included in (true) or excluded from (false) the result
     */
    protected function filter(array $terms, string $axis, bool $inclusive): self {
        $out = new static;
        foreach ($this as $item) {
            if (in_array($item->$axis, $terms) == $inclusive) {
                $out[] = $item;
            }
        }
        return $out;
    }
}
