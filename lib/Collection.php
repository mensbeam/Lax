<?php
/** @license MIT
 * Copyright 2018 J. King et al.
 * See LICENSE and AUTHORS files for details */

declare(strict_types=1);
namespace JKingWeb\Lax;

abstract class Collection implements \IteratorAggregate, \ArrayAccess, \Countable {

    protected $data = [];

    abstract public function primary();

    public function getIterator(): \Traversable {
        return ($this->data instanceof \Traversable) ? $this->data : new \ArrayIterator((array) $this->data);
    }

    public function count(): int {
        return count($this->data);
    }

    public function offsetExists($offset): bool {
        return isset($this->data[$offset]);
    }

    public function offsetGet($offset) {
        return $this->data[$offset];
    }

    public function offsetSet($offset, $value) {
        if (is_null($offset)) {
            $this->data[] = $value;
        } else {
            $this->data[$offset] = $value;
        }
    }

    public function offsetUnset($offset) {
        unset($this->data[$offset]);
    }
}
