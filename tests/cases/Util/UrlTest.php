<?php
/** @license MIT
 * Copyright 2018 J. King
 * See LICENSE and AUTHORS files for details */

declare(strict_types=1);
namespace JKingWeb\Lax\TestCase\Util;

use JKingWeb\Lax\Url;

/** @covers JKingWeb\Lax\Url<extended> */
class UrlTest extends \PHPUnit\Framework\TestCase {
    public function testTemp(): void {
        $url = "https://me:secret@example.com:443/file?question#bit";
        $this->assertSame((string) new Url("https://me:secret@example.com:443/file?question#bit"), $url);
    }
}