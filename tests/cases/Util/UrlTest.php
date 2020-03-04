<?php
/** @license MIT
 * Copyright 2018 J. King
 * See LICENSE and AUTHORS files for details */

declare(strict_types=1);
namespace JKingWeb\Lax\TestCase\Util;

use JKingWeb\Lax\Url;
use JKingWeb\Lax\TestCase\Util\Url\AbstractUriTestCase;

/** @covers JKingWeb\Lax\Url<extended> */
class UrlTest extends AbstractUriTestCase {
    protected function createUri($uri = '') {
        return new Url($uri);
    }
}