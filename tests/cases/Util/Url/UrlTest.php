<?php
/** @license MIT
 * Copyright 2018 J. King
 * See LICENSE and AUTHORS files for details */

declare(strict_types=1);
namespace MensBeam\Lax\TestCase\Util\Url;

use MensBeam\Lax\Url;

/** @covers MensBeam\Lax\Url<extended> */
class UrlTest extends Psr7TestCase {
    protected function createUri($uri = '') {
        return new Url($uri);
    }
}
