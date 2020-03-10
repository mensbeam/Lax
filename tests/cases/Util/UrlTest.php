<?php
/** @license MIT
 * Copyright 2018 J. King
 * See LICENSE and AUTHORS files for details */

declare(strict_types=1);
namespace MensBeam\Lax\TestCase\Util;

use MensBeam\Lax\Url;
use MensBeam\Lax\TestCase\Util\Url\AbstractUriTestCase;

/** @covers MensBeam\Lax\Url<extended> */
class UrlTest extends AbstractUriTestCase {
    protected function createUri($uri = '') {
        return new Url($uri);
    }
}
