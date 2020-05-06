<?php
/** @license MIT
 * Copyright 2018 J. King et al.
 * See LICENSE and AUTHORS files for details */

declare(strict_types=1);
namespace MensBeam\Lax;

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
(?|
    (?:
        (ftp|file|https?|wss?):         # special scheme
    )
    ([/\\]{2}[^/\?\#\\]*)? |            # authority part, accepting backslashes with special schemes
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
    protected const HOST_PATTERN = '/^(\[[a-f0-9:]*\]|[^:]*)(?::([^\/]*))?$/si';
    protected const USER_PATTERN = '/^([^:]*)(?::(.*))?$/s';
    protected const SCHEME_PATTERN = '/^(?:[a-z][a-z0-9\.\-\+]*|)$/i';
    protected const IPV6_PATTERN = '/^\[[a-f0-9:]+\]$/i';
    protected const PORT_PATTERN = '/^\d*$/';
    protected const FORBIDDEN_HOST_PATTERN = '/[\x{00}\t\n\r #%\/:\?@\[\]\\\]/';
    protected const WHITESPACE_CHARS = "\x00\x01\x02\x03\x04\x05\x06\x07\x08\x09\x0A\x0B\x0C\x0D\x0E\x0F\x10\x11\x12\x13\x14\x15\x16\x17\x18\x19\x1A\x1B\x1C\x1D\x1E\x1F\x20";
    protected const ESCAPE_CHARS = [
        'user'  => [":", "@", "/", "?", "#"],
        'pass'  => [":", "@", "/", "?", "#"],
        'path'  => ["?", "#"],
        'query' => ["#"],
    ];
    protected const SPECIAL_SCHEMES = [
        'ftp'   => 21,
        'file'  => null,
        'http'  => 80,
        'https' => 443,
        'ws'    => 80,
        'wss'   => 443,
    ];

    protected $scheme = "";
    protected $user = "";
    protected $pass = "";
    protected $host = null;
    protected $port = null;
    protected $path = "";
    protected $query = null;
    protected $fragment = null;
    protected $specialScheme = false;

    public static function fromUri(UriInterface $uri): self {
        return ($uri instanceof self) ? $uri : new self((string) $uri);
    }

    public static function fromString(string $url, string $baseUrl = null): ?self {
        try {
            return new static($url, $baseUrl);
        } catch (\InvalidArgumentException $e) {
            return null;
        }
    }

    public function __construct(string $url, string $baseUrl = null) {
        $url = str_replace(["\t", "\n", "\r"], "", trim($url, self::WHITESPACE_CHARS));
        if (preg_match(self::URI_PATTERN, $url, $match)) {
            [$url, $scheme, $authority, $path, $query, $fragment] = array_pad($match, 6, "");
            if (!$scheme && $baseUrl) {
                $base = new static($baseUrl);
                $scheme = $base->scheme;
            }
            $this->setScheme($scheme);
            $this->setPath($path);
            if ($query) {
                $this->setQuery(substr($query, 1));
            }
            if ($fragment) {
                $this->setFragment(substr($fragment, 1));
            }
            if (strlen($authority)) {
                $authority = substr($authority, 2);
                if (($cleft = strrpos($authority, "@")) !== false) {
                    if (preg_match(self::USER_PATTERN, substr($authority, 0, $cleft), $match)) {
                        $this->setUser($match[1]);
                        $this->setPass($match[2] ?? "");
                    }
                    if (preg_match(self::HOST_PATTERN, substr($authority, $cleft + 1), $match)) {
                        $this->setHost($match[1]);
                        $this->setPort($match[2] ?? "");
                    }
                } elseif (preg_match(self::HOST_PATTERN, $authority, $match)) {
                    $this->setHost($match[1]);
                    $this->setPort($match[2] ?? "");
                }

            }
            if ((!$this->scheme || ($this->host === null && array_key_exists($this->scheme, self::SPECIAL_SCHEMES))) && strlen($baseUrl ?? "")) {
                $this->resolve($base ?? new static($baseUrl));
            }
        } else {
            throw new \InvalidArgumentException("String is not a valid URI");
        }
    }

    public function isUrn(): bool {
        return $this->host === null && !$this->specialScheme;
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
        if (!strlen((string) $fragment)) {
            $out->fragment = null;
        } else {
            $out->setFragment((string) $fragment);
        }
        return $out;
    }

    public function withHost($host) {
        if ($host === "") {
            $host = null;
        }
        $out = clone $this;
        $out->setHost($host);
        return $out;
    }

    public function withPath($path) {
        $out = clone $this;
        $out->setPath((string) $path);
        return $out;
    }

    public function withPort($port) {
        $out = clone $this;
        $out->setPort((string) $port);
        return $out;
    }

    public function withQuery($query) {
        $out = clone $this;
        if (!strlen((string) $query)) {
            $out->query = null;
        } else {
            $out->setQuery((string) $query);
        }
        return $out;
    }

    public function withScheme($scheme) {
        $out = clone $this;
        $out->setScheme((string) $scheme);
        return $out;
    }

    public function withUserInfo($user, $password = null) {
        $out = clone $this;
        $out->setUser((string) $user);
        $out->setPass((string) $password);
        return $out;
    }

    public function __toString() {
        $out = "";
        $out .= strlen($this->scheme ?? "") ? $this->scheme.":" : "";
        if (is_null($this->host)) {
            $out .= $this->path;
        } else {
            $auth = $this->host;
            $auth .= (strlen($auth) && !is_null($this->port)) ? ":".$this->port : "";
            $user = $this->user.(strlen($this->pass) ? ":".$this->pass : "");
            $auth = (strlen($auth) && strlen($user)) ? "$user@$auth" : $auth;
            $out .= "//";
            $out .= $auth;
            $out .= ($this->path[0] ?? "") !== "/" && strlen($auth) ? "/" : "";
            $out .= $this->specialScheme ? preg_replace("<^/{2,}/>", "/", $this->path) : $this->path;
        }
        $out .= is_string($this->query) ? "?".$this->query : "";
        $out .= is_string($this->fragment) ? "#".$this->fragment : "";
        return $out;
    }

    protected function setScheme(string $value): void {
        if (preg_match(self::SCHEME_PATTERN, $value)) {
            $this->scheme = strtolower($value);
            $this->specialScheme = array_key_exists($this->scheme, self::SPECIAL_SCHEMES);
        } else {
            throw new \InvalidArgumentException("Invalid scheme specified");
        }
    }

    protected function setUser(string $value): void {
        $this->user = $this->normalizeEncoding($value, "user");
    }

    protected function setPass(string $value): void {
        $this->pass = $this->normalizeEncoding($value, "pass");
    }
    
    protected function setHost(?string $value): void {
        $this->host = $this->normalizeHost($value);
    }

    protected function setPort(string $value): void {
        if (!strlen($value)) {
            $this->port = null;
        } elseif (preg_match(self::PORT_PATTERN, (string) $value) && (int) $value <= 0xFFFF) {
            $value = (int) $value;
            if ($this->specialScheme && $value === self::SPECIAL_SCHEMES[$this->scheme]) {
                $this->port = null;
            } else {
                $this->port = $value;
            }
        } else {
            throw new \InvalidArgumentException("Port must be an integer between 0 and 65535, or null");
        }
    }

    protected function setPath(string $value): void {
        if ($this->specialScheme) {
            $value = str_replace("\\", "/", $value);
        }
        $this->path = $this->normalizeEncoding($value, "path");
    }

    protected function setQuery(?string $value): void {
        if (is_null($value)) {
            $this->query = $value;
        } else {
            $this->query = $this->normalizeEncoding($value, "query");
        }
    }

    protected function setFragment(?string $value): void {
        if (is_null($value)) {
            $this->fragment = $value;
        } else {
            $this->fragment = $this->normalizeEncoding($value, "fragment");
        }
    }

    protected function resolve(self $base): void {
        if ($base->isUrn()) {
            throw new \InvalidArgumentException("URL base must not be a Uniform Resource Name");
        }
        [$scheme, $host, $user, $pass, $port, $path, $query, $fragment] = [$base->scheme, $base->host, $base->user, $base->pass, $base->port, $base->path, $base->query, $base->fragment];
        $this->scheme = $this->scheme ?? $scheme;
        if (is_null($this->host)) {
            $this->host = $host;
            $this->user = $user;
            $this->pass = $pass;
            $this->port = $port;
            if (!strlen($this->path ?? "")) {
                $this->path = $path;
                if (is_null($this->query)) {
                    $this->query = $query;
                    if (is_null($this->fragment)) {
                        $this->fragment = $fragment;
                    }
                }
            } elseif (strlen($path)) {
                if ($this->path[0] !== "/") {
                    if ($path[-1] === "/") {
                        $this->path = $path.$this->path;
                    } else {
                        $len = strrpos($path, "/");
                        $len = ($len === false) ? 0 : $len + 1;
                        $this->path = substr($path, 0, $len).$this->path;
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
        if (strlen($host ?? "")) {
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
            } elseif (preg_match(self::FORBIDDEN_HOST_PATTERN, $idn)) {
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
