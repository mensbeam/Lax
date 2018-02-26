<?php
/** @license MIT
 * Copyright 2018 J. King et al.
 * See LICENSE and AUTHORS files for details */

declare(strict_types=1);
namespace JKingWeb\Lax;

use JKingWeb\Lax\Person\Person;
use JKingWeb\Lax\Date;

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

    /** Resolves a relative URL against a base URL */
    protected function resolveUrl(string $url, string $base = null): string {
        $base = $base ?? "";
        return \Sabre\Uri\resolve($base, $url);
    }

    /** Parses an RSS person-text and returns a Person object with a name, e-mail address, or both
     * 
     * The following forms will yield both a name and address:
     *  
     * - user@example.com (Full Name)
     * - Full Name <user@example.com>
     */
    protected function parsePersonText(string $person): Person {
        $person = $this->trimText($person);
        $out = new Person;
        if (!strlen($person)) {
            return $out;
        } elseif (preg_match("/^([^@\s]+@\S+) \((.+?)\)$/", $person, $match)) { // tests "user@example.com (Full Name)" form
            if ($this->validateMail($match[1])) {
                $out->name = trim($match[2]);
                $out->mail = $match[1];
            } else {
                $out->name = $person;
            }
        } elseif (preg_match("/^((?:\S|\s(?!<))+) <([^>]+)>$/", $person, $match)) { // tests "Full Name <user@example.com>" form
            if ($this->validateMail($match[2])) {
                $out->name = trim($match[1]);
                $out->mail = $match[2];
            } else {
                $out->name = $person;
            }
        } elseif ($this->validateMail($person)) {
            $out->name = $person;
            $out->mail = $person;
        } else {
            $out->name = $person;
        }
        return $out;
    }

    /** Tests whether a string is a valid e-mail address
     * 
     * Accepts IDN hosts and (with PHP 7.1 and above) Unicode localparts
     */
    protected function validateMail(string $addr): bool {
        $out = preg_match("/^(.+?)@([^@]+)$/", $addr, $match);
        if (!$out) {
            return false;
        }
        $local = $match[1];
        $domain = $match[2];
        // PHP's filter_var does not accept IDN hosts, so we have to perform an IDNA transformat first
        $domain = idn_to_ascii($domain, \IDNA_NONTRANSITIONAL_TO_ASCII, \INTL_IDNA_VARIANT_UTS46); // settings for IDNA2008 algorithm (I think)
        if ($domain===false) {
            return false;
        }
        $addr = "$local@$domain";
         // PHP 7.1 and above have the constant defined FIXME: Review if removing support for PHP 7.0
        $flags = defined("\FILTER_FLAG_EMAIL_UNICODE") ?  \FILTER_FLAG_EMAIL_UNICODE : 0;
        return (bool) filter_var($addr, \FILTER_VALIDATE_EMAIL, $flags);
    }

    protected function parseDate(string $date) {
        $out = null;
        $date = $this->trimText($date);
        if (!strlen($date)) {
            return $out;
        }
        $tz = new \DateTimeZone("UTC");
        foreach (Date::SUPPORTED_FORMATS as $format) {
            $out = Date::createFromFormat($format, $date, $tz);
            if ($out) {
                break;
            }
        }
        return $out ?: null;
    }
}
