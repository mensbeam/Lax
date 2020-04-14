<?php
/** @license MIT
 * Copyright 2018 J. King et al.
 * See LICENSE and AUTHORS files for details */

declare(strict_types=1);
namespace MensBeam\Lax\Enclosure;

/**
 * @property \MensBeam\Lax\Url $url
 * @property \MensBeam\Lax\Text $title
 * @property string $type
 * @property bool $sample
 * @property int $height
 * @property int $width
 * @property int $duration
 * @property int $bitrate
 * @property int $size
 */
class Enclosure implements \IteratorAggregate, \ArrayAccess, \Countable {
    public $preferred;
    protected $data = [];

    private $url;
    private $title;
    private $type;
    private $sample;
    private $height;
    private $width;
    private $duration;
    private $bitrate;
    private $size;

    public function __construct(Enclosure ...$enc) {
        $this->data = $enc ?? [];
    }

    public function getIterator(): \Traversable {
        return new \ArrayIterator($this->data);
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

    public function offsetSet($offset, $value): void {
        if (is_null($offset)) {
            $this->data[] = $value;
        } else {
            $this->data[$offset] = $value;
        }
    }

    public function offsetUnset($offset): void {
        unset($this->data[$offset]);
    }

    public function __set(string $name, $value): void {
        if ($this->data) {
            $this->default()->__set($name, $value);
        } else {
            $this->$name = $value;
        }
    }

    public function __get(string $name) {
        if ($this->data) {
            return $this->default()->__get($name);
        } else {
            return $this->$name;
        }
    }

    public function __isset(string $name): bool {
        if ($this->data) {
            return $this->default()->__isset($name);
        } else {
            return isset($this->$name);
        }
    }

    public function __unset(string $name): void {
        if ($this->data) {
            $this->default()->__unset($name);
        } else {
            unset($this->$name);
        }
    }

    protected function default(): self {
        foreach ($this->data as $m) {
            if ($m->preferred) {
                return $m;
            }
        }
        return $this->data[array_keys($this->data)[0]];
    }
}
