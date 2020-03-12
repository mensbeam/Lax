<?php
/** @license MIT
 * Copyright 2018 J. King et al.
 * See LICENSE and AUTHORS files for details */

declare(strict_types=1);
namespace MensBeam\Lax\Parser;

use MensBeam\Lax\Collection;
use MensBeam\Lax\Date;
use MensBeam\Lax\Url;

trait Construct {
    /** Trims plain text and collapses whitespace */
    protected function trimText(string $text): string {
        return trim(preg_replace("<\s{2,}>s", " ", $text));
    }

    /** Takes an HTML string as input and returns a sanitized version of that string
     *
     * The $outputHtml parameter, when false, outputs only the plain-text content of the sanitized HTML
     */
    protected function sanitizeString(string $markup, bool $outputHtml = true): string {
        if (!preg_match("/<\S/", $markup)) {
            // if the string does not appear to actually contain markup besides entities, we can skip most of the sanitization
            return $outputHtml ? $markup : $this->trimText(html_entity_decode($markup, \ENT_QUOTES | \ENT_HTML5, "UTF-8"));
        } else {
            return "OOK!";
        }
    }

    /** Tests whether a string is a valid e-mail address
     *
     * Accepts IDN hosts and Unicode localparts
     */
    protected function validateMail(string $addr): bool {
        $out = preg_match("/^(.+?)@([^@]+)$/", $addr, $match);
        if (!$out) {
            return false;
        }
        $local = $match[1];
        $domain = $match[2];
        // PHP's filter_var does not accept IDN hosts, so we have to perform an IDNA transformation first
        $domain = idn_to_ascii($domain, \IDNA_NONTRANSITIONAL_TO_ASCII | \IDNA_CHECK_BIDI | \IDNA_CHECK_CONTEXTJ, \INTL_IDNA_VARIANT_UTS46); // settings for IDNA2008 algorithm (I think)
        if ($domain !== false) {
            $addr = "$local@$domain";
            return (bool) filter_var($addr, \FILTER_VALIDATE_EMAIL, \FILTER_FLAG_EMAIL_UNICODE);
        }
        return false;
    }                

    protected function parseDate(string $date): ?Date {
        $out = null;
        $date = $this->trimText($date);
        if (strlen($date)) {
            $tz = new \DateTimeZone("UTC");
            foreach (Date::$supportedFormats as $format) {
                $out = Date::createFromFormat($format, $date, $tz);
                if ($out) {
                    break;
                }
            }
        }
        return $out ?: null;
    }

    protected function parseMediaType(string $type, ?Url $url = null): ?string {
        if (preg_match('<^\s*([0-9a-z]+(?:/[!#$%&\'\*\+\-\.^_`|~0-9a-z]+)?)(?:\s|;|,|$)>i', $type, $match)) {
            /* NOTE: The pattern used here is a subset of what is
                technically allowed by RFC 7231: the "type" portion
                is supposed to be as general as the "subtype" portion,
                but in practice only alphabetic types have ever been
                registered, making a more specific pattern more
                practically useful for detecting media types.

                See:
                <https://tools.ietf.org/html/rfc7231#section-3.1.1.1>
                <https://tools.ietf.org/html/rfc7230#section-3.2.6>

                Additionally, types without subtypes are accepted as
                we foresee the general type still being useful to
                feed processors.
            */
            return strtolower($match[1]);
        }
        if ($url && (strlen($url->getScheme()) && $url->host !== null)) {
            $file = substr($url->getPath(), (int) strrpos($url->getPath(), "/"));
            $ext = strrpos($file, ".");
            if ($ext !== false) {
                $ext = substr($file, $ext + 1);
                if (strlen($ext)) {
                    return ($this->mime ?? ($this->mime = new \Mimey\MimeTypes))->getMimeType($ext);
                }
            }
        } elseif ($url && $url->getScheme() === "data") {
            return $this->parseMediaType($url->getPath()) ?? "text/plain";
        }
        return null;
    }

    protected function empty($o): bool {
        return !array_filter((array) $o, function($v) {
            return !is_null($v) && (!$v instanceof Collection || sizeof($v) > 0);
        });
    }
}
