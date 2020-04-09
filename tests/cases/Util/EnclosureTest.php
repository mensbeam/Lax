<?php
/** @license MIT
 * Copyright 2018 J. King
 * See LICENSE and AUTHORS files for details */

declare(strict_types=1);
namespace MensBeam\Lax\TestCase\Util;

use MensBeam\Lax\Enclosure\Enclosure;
use MensBeam\Lax\Url;

/** @covers \MensBeam\Lax\Enclosure\Enclosure */
class EnclosureTest extends \PHPUnit\Framework\TestCase {
    public function testCreateAnEnclosure(): void {
        $url = new Url("http://example.com");
        $enc = new Enclosure;
        $enc->preferred = true;
        $enc->url = $url;
        $this->assertTrue($enc->preferred);
        $this->assertSame($url, $enc->url);
    }

    public function testCreateAnEnclosureSet(): void {
        // create an empty enclosure
        $enc = new Enclosure;
        $this->assertSame(0, count($enc));
        $this->assertFalse(isset($enc->title));
        // create two more enclosures; the second is preferred
        $enc1 = new Enclosure;
        $enc1->title = "Ook";
        $enc2 = new Enclosure;
        $enc2->title = "Eek";
        $enc2->preferred = true;
        // add the first sub-enclosure
        $enc[] = $enc1;
        $this->assertTrue(isset($enc->title));
        $this->assertSame(1, count($enc));
        $this->assertSame("Ook", $enc->title);
        // add the second sub-enclosure
        $enc[] = $enc2;
        $this->assertSame(2, count($enc));
        $this->assertSame("Eek", $enc->title);
        // make the second enclosure non-preferred
        $enc2->preferred = false;
        $this->assertSame("Ook", $enc->title);
    }

    public function testIterateOverAnEnclosureSet(): void {
        // create four enclosures
        $enc1 = new Enclosure;
        $enc2 = new Enclosure;
        $enc3 = new Enclosure;
        $enc4 = new Enclosure;
        // create a set with two of the enclosures
        $enc = new Enclosure($enc1, $enc2);
        // add the other two with arbitrary keys
        $enc['ook'] = $enc3;
        $enc[] = $enc4;
        // iterate over the sub-enclosures
        $act = [];
        foreach ($enc as $k => $v) {
            $act[$k] = $v;
        }
        $exp = [0 => $enc1, 1 => $enc2, 'ook' => $enc3, 2 => $enc4];
        $this->assertSame($exp, $act);
    }

    public function testManipulateMagicEnclosureProperties(): void {
        // create two enclosures
        $enc1 = new Enclosure;
        $enc2 = new Enclosure;
        $enc1->title = "Ook";
        $enc2->title = "Eek";
        // create a set with the two enclosures
        $enc = new Enclosure($enc1, $enc2);
        // check titles
        $this->assertTrue(isset($enc[0]));
        $this->assertTrue(isset($enc[1]));
        $this->assertSame("Ook", $enc[0]->title);
        $this->assertSame("Eek", $enc[1]->title);
        $this->assertSame("Ook", $enc->title);
        // unset the title of the first sub-enclosure via the parent
        unset($enc->title);
        $this->assertFalse(isset($enc[0]->title));
        $this->assertFalse(isset($enc->title));
        // remove the first sub-enclosure
        unset($enc[0]);
        $this->assertFalse(isset($enc[0]));
        $this->assertTrue(isset($enc[1]));
        $this->assertSame("Eek", $enc->title);
        // set the title of the second enclosure via the parent
        $enc->title = "Ack";
        $this->assertSame("Ack", $enc[1]->title);
        $this->assertSame("Ack", $enc->title);
    }
}
