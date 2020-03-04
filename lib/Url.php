<?php
/** @license MIT
 * Copyright 2018 J. King et al.
 * See LICENSE and AUTHORS files for details */

declare(strict_types=1);
namespace JKingWeb\Lax;

use Psr\Http\Message\UriInterface;

/** Normalized URI representation, compatible with the PSR-7 URI interface
 * 
 * The following features are implemented:
 * 
 * - The full PSR-7 `UriInterface` interface
 * - Correct handling of both URLs and URNs
 * - Relative URL resolution
 * - Encoding normalization
 * - Scheme normalization
 * - IDNA normalization
 * - IPv6 address normalization
 * - Empty query and fragment removal
 * 
 * Some things this class does not do:
 * 
 * - Handle non-standard schemes (e.g. ed2k)
 * - Collapse paths
 * - Drop default ports
 * 
 * This class should not be used with XML namespace URIs,
 * as the normalizations performed will change the values
 * of some namespaces.
 */
class Url implements UriInterface {
    protected const URI_PATTERN = <<<'PCRE'
<^
(?:
    (?:
        ([a-z][a-z0-9\.\-\+]*): |       # absolute URI
        :?(?=//)                        # scheme-relative URI
    )
    (//[^/\?\#]*)?                      # authority part
)?
([^\?\#]*)                              # path part
(\?[^\#]*)?                             # query part
(\#.*)?                                 # fragment part
$>six
PCRE;
    protected const AUTHORITY_PATTERN = <<<'PCRE'
<^
//
(?:
    ([^@:]*)            # username part
    (?::([^@]*))?       # password part
    @
)?
(
    \[[a-f0-9:]*\] |    # IPv6 address
    [^:]+               # domain or IPv4 address
)
(?:
    :(\d*)              # port part
)?
$>six
PCRE;
    protected const SCHEME_PATTERN = "<^(?:[a-z][a-z0-9\.\-\+]*|)$>i";
    protected const IPV6_PATTERN = "<^\[[a-f0-9:]+\]$>i";
    protected const PORT_PATTERN = "<^\d*$>";
    protected const ESCAPE_CHARS = [
        'user'  => [":", "@", "/", "?", "#"],
        'pass'  => ["@", "/", "?", "#"],
        'path'  => ["?", "#"],
        'query' => ["#"],
    ];

    protected $scheme = null;
    protected $host = null;
    protected $port = null;
    protected $user = "";
    protected $pass = "";
    protected $path = null;
    protected $query = null;
    protected $fragment = null;

    public static function fromUri(UriInterface $uri): self {
        return ($uri instanceof self) ? $uri : new self((string) $uri);
    }

    public function __construct(string $url, ?UriInterface $baseUrl = null) {
        if (preg_match(self::URI_PATTERN, $url, $match)) {
            [$url, $scheme, $authority, $path, $query, $fragment] = array_pad($match, 6, "");
            foreach (["scheme", "path", "query", "fragment"] as $part) {
                if (strlen($$part)) {
                    if ($part === "query" || $part === "fragment") {
                        $$part = substr($$part, 1);
                    }
                    $this->set($part, $$part);
                }
            }
            if (strlen($authority)) {
                if (preg_match(self::AUTHORITY_PATTERN, $authority, $match)) {
                    [$authority, $user, $pass, $host, $port] = array_pad($match, 5, "");
                    foreach (["user", "pass", "host", "port"] as $part) {
                        $this->set($part, $$part);
                    }
                }
            }
            if ($baseUrl && !strlen($this->scheme)) {
                $this->resolve(self::fromUri($baseUrl));
            }
            foreach (["scheme", "path", "query", "fragment"] as $part) {
                $this->$part = $this->$part ?? "";
            }
        } else {
            throw new \InvalidArgumentException("String is not a valid URI");
        }
    }

    public function getAuthority() {
        $host = $this->getHost();
        if (strlen($host) > 0) {
            $userInfo = $this->getUserInfo();
            $port = $this->getPort();
            return (strlen($userInfo) ? $userInfo."@" : "").$host.(!is_null($port) ? ":".$port : "");
        }
        return "";
    }

    public function getFragment() {
        return $this->fragment ?? "";
    }

    public function getHost() {
        return $this->host ?? "";
    }

    public function getPath() {
        return $this->path ?? "";
    }

    public function getPort() {
        return $this->port;
    }

    public function getQuery() {
        return $this->query ?? "";
    }

    public function getScheme() {
        return $this->scheme ?? "";
    }

    public function getUserInfo() {
        if (strlen($this->user ?? "")) {
            return $this->user.(strlen($this->pass ?? "") ? ":".$this->pass : "");
        }
        return "";
    }

    public function withFragment($fragment) {
        $out = clone $this;
        $out->set("fragment", $fragment);
        return $out;
    }

    public function withHost($host) {
        if ($host === "") {
            $host = null;
        }
        $out = clone $this;
        $out->set("host", $host);
        return $out;
    }

    public function withPath($path) {
        $out = clone $this;
        $out->set("path", $path);
        return $out;
    }

    public function withPort($port) {
        $out = clone $this;
        $out->set("port", $port);
        return $out;
    }

    public function withQuery($query) {
        $out = clone $this;
        $out->set("query", $query);
        return $out;
    }

    public function withScheme($scheme) {
        $out = clone $this;
        $out->set("scheme", $scheme);
        return $out;
    }

    public function withUserInfo($user, $password = null) {
        $out = clone $this;
        $out->set("user", $user);
        $out->set("pass", $password);
        return $out;
    }

    public function __toString() {
        $out = "";
        $out .= strlen($this->scheme) ? $this->scheme.":" : "";
        if (is_null($this->host)) {
            $out .= $this->path;
        } else {
            $out .= "//";
            $out .= $this->getAuthority();
            $out .= ($this->path[0] ?? "") === "/" ? "" : "/";
            $out .= preg_replace("<^/{2,}/>", "/", $this->path);
        }
        $out .= strlen($this->query) ? "?".$this->query : "";
        $out .= strlen($this->fragment) ? "#".$this->fragment : "";
        return $out;
    }

    public function __get(string $name) {
        return $this->$name;
    }

    protected function set(string $name, $value): void {
        switch ($name) {
            case "host":
                $this->host = $this->normalizeHost($value);
                break;
            case "port":
                if (preg_match(self::PORT_PATTERN, (string) $value, $match)) {
                    $this->port = strlen($match[0]) ? (int) $value : null;
                } else {
                    throw new \InvalidArgumentException("Port must be an integer or null");
                }
                break;
            case "scheme":
                if (preg_match(self::SCHEME_PATTERN, $value)) {
                    $this->scheme = strtolower($value);
                } else {
                    throw new \InvalidArgumentException("Invalid scheme specified");
                }
                break;
            default:
                $this->$name = $this->normalizeEncoding((string) $value, $name);    
        }
    }

    protected function resolve(self $base): void {
        [$scheme, $host, $user, $pass, $port, $path, $query, $fragment] = [$base->scheme, $base->host, $base->user, $base->pass, $base->port, $base->path, $base->query, $base->fragment];
        if (strlen($scheme) && is_null($host)) {
            throw new \InvalidArgumentException("URL base must not be a Uniform Resource Name");
        }
        $this->scheme = $this->scheme ?? $scheme;
        if (is_null($this->host)) {
            $this->host = $host;
            $this->user = $user;
            $this->pass = $pass;
            $this->port = $port;
            if (is_null($this->path)) {
                $this->path = $path;
                if (is_null($this->query)) {
                    $this->query = $query;
                    if (is_null($this->fragment)) {
                        $this->fragment = $fragment;
                    }
                }
            } elseif(strlen($path)) {
                if ($this->path[0] !== "/") {
                    if ($path[-1] === "/") {
                        $this->path = $path.$this->path;
                    } else {
                        $this->path = substr($path, 0, (int) strrpos($path, "/")).$this->path;
                    }
                }
            }
        }
    }

    protected function normalizeEncoding(string $data, string $part = null): string {
        $pos = 0;
        $end = strlen($data);
        $out = "";
        $esc = self::ESCAPE_CHARS[$part] ?? [];
        // process each character in sequence
        while ($pos < $end) {
            $c = $data[$pos];
            if ($c === "%") {
                // the % character signals an encoded character...
                $d = substr($data, $pos + 1, 2);
                if (!preg_match("/^[0-9a-fA-F]{2}$/", $d)) {
                    // unless there are fewer than two characters left in the string or the two characters are not hex digits
                    $d = ord($c);
                } else {
                    $d = hexdec($d);
                    $pos += 2;
                }
            } else {
                $d = ord($c);
            }
            $dc = chr($d);
            if ($d < 0x21 || $d > 0x7E || $d == 0x25) {
                // these characters are always encoded
                $out .= "%".strtoupper(dechex($d));
            } elseif (preg_match("/[a-zA-Z0-9\._~-]/", $dc)) {
                // these characters are never encoded
                $out .= $dc;
            } else {
                // these characters are passed through as-is...
                if ($c === "%") {
                    $out .= "%".strtoupper(dechex($d));
                } else {
                    // unless the part we're processing has delimiters which must be escaped
                    if (in_array($dc, $esc)) {
                        $out .= "%".strtoupper(dechex($d));
                    } else {
                        $out .= $c;
                    }
                }
            }
            $pos++;
        }
        return $out;
    }

    /** Normalizes a hostname per IDNA:2008 */
    protected function normalizeHost(?string $host): ?string {
        if (!is_null($host) && strlen($host)) {
            if (preg_match(self::IPV6_PATTERN, $host)) {
                // normalize IPv6 addresses
                $addr = @inet_pton(substr($host, 1, strlen($host) - 2));
                if ($addr !== false) {
                    return "[".inet_ntop($addr)."]";
                }
            }
            $idn = idn_to_ascii($host, \IDNA_NONTRANSITIONAL_TO_ASCII | \IDNA_CHECK_BIDI | \IDNA_CHECK_CONTEXTJ, \INTL_IDNA_VARIANT_UTS46);
            if ($idn === false) {
                throw new \InvalidArgumentException("Invalid host in URL");
            }
            $host = idn_to_utf8($idn, \IDNA_NONTRANSITIONAL_TO_UNICODE | \IDNA_USE_STD3_RULES, \INTL_IDNA_VARIANT_UTS46);
            if ($host === false) {
                throw new \InvalidArgumentException("Invalid host in URL");
            }
        }
        return $host;
    }
}