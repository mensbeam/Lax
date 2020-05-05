<?php
/** @license MIT
 * Copyright 2018 J. King
 * See LICENSE and AUTHORS files for details */

declare(strict_types=1);
namespace MensBeam\Lax\TestCase\Util\Date;

use MensBeam\Lax\Date;
use Symfony\Component\Yaml\Yaml;
use Symfony\Component\Yaml\Parser as YamlParser;

/** @covers \MensBeam\Lax\Date */
class DateTest extends \PHPUnit\Framework\TestCase {
    public function testConstructADate(): void {
        $d = new Date("2001-05-22T14:55:23Z");
        $this->assertInstanceOf(\DateTimeImmutable::class, $d);
        $this->assertSame("2001-05-22T14:55:23.000000Z", $d->setTimezone(new \DateTimeZone("UTC"))->format("Y-m-d\TH:i:s.u\Z"));
    }

    public function testNormalizeADate(): void {
        $d = new Date("2001-05-22T14:55:23-01:00");
        $this->assertSame("2001-05-22T15:55:23.000000Z", $d->normalize());
    }

    public function testCastADateToString(): void {
        $d = new Date("2001-05-22T14:55:23-01:00");
        $this->assertSame("2001-05-22T14:55:23-01:00", (string) $d);
        $d = new Date("2001-05-22T14:55:23.55Z");
        $this->assertSame("2001-05-22T14:55:23.550000+00:00", (string) $d);
    }

    public function testSerializeADateToJson(): void {
        $d = new Date("2001-05-22T14:55:23-01:00");
        $this->assertSame('"2001-05-22T14:55:23-01:00"', json_encode($d));
        $d = new Date("2001-05-22T14:55:23.55Z");
        $this->assertSame('"2001-05-22T14:55:23.550000+00:00"', json_encode($d));
    }

    public function testCreateADateFromADatetimeInterfaceInstance(): void {
        $m = new \DateTime("2001-05-22T14:55:23-01:00");
        $d = Date::createFromMutable($m);
        $this->assertSame("2001-05-22T15:55:23.000000Z", $d->normalize());
        $i = new \DateTimeImmutable("2001-05-22T14:55:23-01:00");
        $d = Date::createFromImmutable($i);
        $this->assertSame("2001-05-22T15:55:23.000000Z", $d->normalize());
    }

    public function testCreateADateFromAFormatString(): void {
        $f = '!Y-m-d\TH:i:sP';
        $d = Date::createFromFormat($f, "2001-05-22T14:55:23-01:00");
        $this->assertSame("2001-05-22T15:55:23.000000Z", $d->normalize());
        $f = '!Y-m-d\TH:i:s';
        $d = Date::createFromFormat($f, "2001-05-22T14:55:23", new \DateTimeZone("Etc/GMT+1"));
        $this->assertSame("2001-05-22T15:55:23.000000Z", $d->normalize());
    }

    /** @dataProvider provideParsableStrings */
    public function testCreateADateFromAString(string $input, ?string $exp): void {
        $act = Date::createFromString($input);
        if (!$exp) {
            $this->assertNull($act);
        } else {
            $this->assertSame($exp, (string) $act);
        }
    }

    public function provideParsableStrings(): iterable {
        foreach (new \GlobIterator(__DIR__."/test-*.yaml", \FilesystemIterator::CURRENT_AS_PATHNAME | \FilesystemIterator::KEY_AS_FILENAME) as $file => $path) {
            foreach ((new YamlParser)->parseFile($path, Yaml::PARSE_OBJECT_FOR_MAP) as $description => $test) {
                if (!is_null($test->output) && substr($test->output, -6) === "-00:00") {
                    // PHP does not preserve the -0000 timezone
                    $test->output[-6] = "+";
                }
                if (is_array($test->input)) {
                    foreach ($test->input as $input) {
                        yield "$description ($input)" => [$input, $test->output];
                    }
                } else {
                    yield "$description" => [$test->input, $test->output];
                }
            }
        }
    }
}
