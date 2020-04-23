<?php
/** @license MIT
 * Copyright 2018 J. King et al.
 * See LICENSE and AUTHORS files for details */

declare(strict_types=1);
namespace MensBeam\Lax\Parser;

use MensBeam\Lax\Collection;
use MensBeam\Lax\Date;

trait Construct {
    /** Trims plain text and collapses whitespace */
    protected function trimText(string $text): string {
        return trim(preg_replace("<\s{2,}>s", " ", $text));
    }

    /** Tests whether a string is a valid e-mail address
     *
     * Accepts IDN hosts and Unicode localparts
     */
    protected function validateMail(string $addr): bool {
        if (!preg_match("/^(.+?)@([^@]+)$/", $addr, $match)) {
            return false;
        }
        $local = $match[1];
        $domain = $match[2];
        // PHP's filter_var does not accept IDN hosts, so we have to perform an IDNA transformation first
        $domain = idn_to_ascii($domain, \IDNA_NONTRANSITIONAL_TO_ASCII | \IDNA_CHECK_BIDI | \IDNA_CHECK_CONTEXTJ, \INTL_IDNA_VARIANT_UTS46);
        if ($domain !== false) {
            $addr = "$local@$domain";
            return (bool) filter_var($addr, \FILTER_VALIDATE_EMAIL, \FILTER_FLAG_EMAIL_UNICODE);
        }
        return false;
    }

    protected function empty($o, array $ignore = []): bool {
        return !array_filter((array) $o, function($v, $k) use ($ignore) {
            return !in_array($k, $ignore) && !is_null($v) && (!$v instanceof Collection || sizeof($v) > 0);
        }, \ARRAY_FILTER_USE_BOTH);
    }
}
