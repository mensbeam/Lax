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
        :?(?=[\\/]{2})                  # scheme-relative URI
    )
    ([\\/]{1,2}[^/\?\#\\]*)?            # authority part
)?
([^\?\#]*)                              # path part
(\?[^\#]*)?                             # query part
(\#.*)?                                 # fragment part
$>six
PCRE;
    protected const HOST_PATTERN = '/^(\[[a-f0-9:\.]*\]|[^:]*)(?::([^\/]*))?$/si';
    protected const USER_PATTERN = '/^([^:]*)(?::(.*))?$/s';
    protected const SCHEME_PATTERN = '/^(?:[a-z][a-z0-9\.\-\+]*|)$/i';
    protected const IPV6_PATTERN = '/^\[[^\]]+\]$/i';
    protected const PORT_PATTERN = '/^\d*$/';
    protected const FORBIDDEN_HOST_PATTERN = '/[\x{00}\t\n\r #%\/:\?@\[\]\\\]/';
    protected const WINDOWS_AUTHORITY_PATTERN = '/^[\/\\\\]{1,2}[a-zA-Z][:|]$/';
    protected const WINDOWS_PATH_PATTERN = '/(?:^|\/)([a-zA-Z])[:|]($|[\/#\?].*)/';
    protected const WHITESPACE_CHARS = "\x00\x01\x02\x03\x04\x05\x06\x07\x08\x09\x0A\x0B\x0C\x0D\x0E\x0F\x10\x11\x12\x13\x14\x15\x16\x17\x18\x19\x1A\x1B\x1C\x1D\x1E\x1F\x20";
    protected const PERCENT_ENCODE_SETS = [
        'C0'       => "",
        'fragment' => " \"<>`",
        'path'     => " \"<>`?#{}",
        'userinfo' => " \"<>`?#{}/:;=@[\]^|",
        'query'    => " \"<>#", // single-quote as well if scheme is special
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
        $base = null;
        reprocess:
        if (preg_match(self::URI_PATTERN, $url, $match)) {
            [$url, $scheme, $authority, $path, $query, $fragment] = array_pad($match, 6, "");
            // if the URI is not unambigously a URL, parse the base URI
            if (!$base && $baseUrl && (!$scheme || substr($authority, 0, 2) !== "//")) {
                $base = new static($baseUrl);
            }
            // set the scheme; use the base scheme if necessary
            $this->setScheme($scheme ?: ($base->scheme ?? ""));
            // make various checks to see if the authority should actually be the starts of the path
            if ($authority && !in_array($authority[1] ?? "", ["/", "\\"])) {
                // the URI is something like x:/example.com/
                if ($base && $this->scheme === $base->scheme && !$base->isUrn()) {
                    // URI is a relative URL; add authority to path instead
                    $path = $authority.$path;
                    $authority = "";
                } elseif ($this->scheme === "file") {
                    // URI is an absolute file: URL; add authority to path and set the authority to the default authority
                    $path = $authority.$path;
                    $authority = "//";
                } elseif ($this->specialScheme) {
                    // URI is an absolute URL with a typo; add a slash to the authority
                    $authority = "/$authority";
                } else {
                    // URI is a URN; add authority to path instead
                    $path = $authority.$path;
                    $authority = "";
                }
            } elseif ($scheme && !$authority) {
                // the URI is something like x:example.com/
                if ($base && $this->scheme === $base->scheme && !$base->isUrn()) {
                    // URI is a relative URL; continue processing
                } elseif ($this->scheme === "file") {
                    // URI is an absolute file: URL; add the authority delimiter and default authority to the URL and reprocess
                    $url = preg_replace("/:/", ":///", $url, 1);
                    goto reprocess;
                } elseif ($this->specialScheme) {
                    // URI is an absolute URL; add the authority delimiter to the URL and reprocess
                    $url = preg_replace("/:/", "://", $url, 1);
                    goto reprocess;
                } else {
                    // URI is a URN; continue processing
                }
            } elseif ($this->scheme === "file" && preg_match(self::WINDOWS_AUTHORITY_PATTERN, $authority)) {
                $path = $authority.$path;
                $authority = "//";
            }
            if ($authority) {
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
            if (!$scheme && $base) {
                // the effective URL scheme must be known to correctly process the path
                $base = $base ?? new static($baseUrl);
                $this->setScheme($base->scheme);
            }
            $this->setPath($path);
            if ($query) {
                $this->setQuery(substr($query, 1));
            }
            if ($fragment) {
                $this->setFragment(substr($fragment, 1));
            }
            if ((!$scheme || ($this->host === null && $this->specialScheme)) && strlen($baseUrl ?? "")) {
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
        return $this->serializeScheme().$this->serializeAuthority().$this->serializePath().$this->serializeQuery().$this->serializeFragment();
    }

    protected function serializeScheme(): string {
        return $this->scheme ? $this->scheme.":" : "";
    }

    protected function serializeAuthority(): string {
        if ($this->host !== null) {
            $auth = $this->host;
            $auth .= (strlen($auth) && !is_null($this->port)) ? ":".$this->port : "";
            $user = $this->user.(strlen($this->pass) ? ":".$this->pass : "");
            $auth = (strlen($auth) && strlen($user)) ? "$user@$auth" : $auth;
            return "//$auth";
        }
        return "";
    }

    protected function serializePath(): string {
        if ($this->host !== null) {
            $out = "";
            if ((strlen($this->path) && $this->path[0] !== "/") || (!strlen($this->path) && $this->specialScheme)) {
                $out .= "/";
            }
            $out .= $this->specialScheme ? preg_replace("<^/{2,}/>", "/", $this->path) : $this->path;
            return $out;
        }
        return $this->path;
    }

    protected function serializeQuery(): string {
        return is_string($this->query) ? "?".$this->query : "";
    }

    protected function serializeFragment(): string {
        return is_string($this->fragment) ? "#".$this->fragment : "";
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
        $this->user = $this->percentEncode($value, "userinfo");
    }

    protected function setPass(string $value): void {
        $this->pass = $this->percentEncode($value, "userinfo");
    }
    
    protected function setHost(?string $value): void {
        if ($this->scheme === "file" && strtolower($value) === "localhost") {
            $this->host = "";
        } else {
            $this->host = $this->normalizeHost($value);
        }
        
    }

    protected function setPort(string $value): void {
        if (!strlen($value)) {
            $this->port = null;
        } elseif ($this->scheme === "file") {
            throw new \InvalidArgumentException("Port in file: scheme must always be null");
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
            $value = $this->collapsePath(str_replace("\\", "/", $value));
        }
        $this->path = $this->percentEncode($value, $this->isUrn() ? "C0" : "path");
    }

    protected function setQuery(?string $value): void {
        if (is_null($value)) {
            $this->query = $value;
        } else {
            $this->query = $this->percentEncode($value, "query");
        }
    }

    protected function setFragment(?string $value): void {
        if (is_null($value)) {
            $this->fragment = $value;
        } else {
            $this->fragment = $this->percentEncode($value, "fragment");
        }
    }

    protected function collapsePath(string $path): string {
        if (preg_match("<^/?$>", $path)) {
            return $path;
        }
        if ($this->scheme === "file" && preg_match(self::WINDOWS_PATH_PATTERN, $path, $match)) {
            // If a Windows drive letter is present, the host is implicitly localhost
            $this->setHost("");
            $path = "/".$match[1].":".$match[2];
        }
        $abs = $path[0] === "/";
        $dir = $path[-1] === "/";
        $term = $dir || preg_match("</(?:\.|%2E){1,2}$>i", $path);
        $path = explode("/", substr($path, (int) $abs, strlen($path) - ($abs + $dir)));
        $out = [];
        foreach ($path as $s) {
            if ($s === "" && !$out && $this->scheme === "file") {
                // empty segments before the first non-empty segment in a file: URL should be skipped
                continue;
            } elseif (preg_match('/^(?:\.|%2E)$/i', $s)) {
                // current-directory segment; these should simply be omitted
                continue;
            } elseif (preg_match('/^(?:\.|%2E){2}$/i', $s)) {
                // parent-directory segment; pop a directory off the output
                array_pop($out);
            } else {
                $out[] = $s;
            }
        }
        if (!$out) {
            return $abs ? "/" : "";
        }
        return ($abs ? "/" : "").implode("/", $out).($term ? "/" : "");
    }

    protected function percentEncode(string $data, string $type): string {
        assert(array_key_exists($type, self::PERCENT_ENCODE_SETS), "Invalid percent-encoding set");
        $out = "";
        $end = strlen($data);
        for ($p = 0; $p < $end; $p++) {
            $c = $data[$p];
            $o = ord($c);
            if ($o > 0x1F && $o < 0x7F && !strspn($c, self::PERCENT_ENCODE_SETS[$type]) && !($this->specialScheme && $type === "query" && $c === "'")) {
                $out .= $c;
            } else {
                $out .= strtoupper("%".str_pad(dechex($o), 2, "0", \STR_PAD_LEFT));
            }
        }
        return $out;
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

    protected function normalizeHost(?string $host): ?string {
        if (strlen($host ?? "")) {
            if ($host[0] === "[" && $host[-1] === "]") {
                // normalize IPv6 addresses
                $addr = $this->normalizeIPv6(substr($host, 1, strlen($host) - 2));
                if ($addr !== null) {
                    return "[".$addr."]";
                } else {
                    throw new \InvalidArgumentException("Invalid host in URL");
                }
            }
            $idn = idn_to_ascii($host, \IDNA_NONTRANSITIONAL_TO_ASCII | \IDNA_CHECK_BIDI | \IDNA_CHECK_CONTEXTJ, \INTL_IDNA_VARIANT_UTS46);
            if (
                $idn === false
                || preg_match(self::FORBIDDEN_HOST_PATTERN, $idn)
                || idn_to_utf8($idn, \IDNA_NONTRANSITIONAL_TO_UNICODE | \IDNA_USE_STD3_RULES, \INTL_IDNA_VARIANT_UTS46) === false
            ) {
                throw new \InvalidArgumentException("Invalid host in URL");
            }
            return $idn;
        } elseif ($this->specialScheme && $this->scheme !== "file") {
            throw new \InvalidArgumentException("Invalid host in URL");
        }
        return $host;
    }

    protected function normalizeIPv6(string $input): ?string {
        // first parse the address; this is a literal implementation of https://url.spec.whatwg.org/#concept-ipv6-parser
        $addr = array_fill(0, 8, 0);
        $pieceIndex = 0;
        $compress = null;
        $p = 0;
        $end = strlen($input);
        if ($end && $input[$p] === ":") {
            if (($input[$p + 1] ?? "") !== ":") {
                return null;
            }
            $p += 2;
            $compress = ++$pieceIndex;
        }
        while ($p < $end) {
            $c = $input[$p];
            if ($pieceIndex > 7) {
                return null;
            }
            if ($c === ":") {
                if (!is_null($compress)) {
                    return null;
                }
                $p++;
                $compress = ++$pieceIndex;
                continue;
            }
            $value = $length = 0;
            while ($length < 4 && strspn($c, "0123456789ABCDEFabcdef")) {
                $value = $value * 0x10 + hexdec($c);
                $c = $input[++$p] ?? "";
                $length++;
            }
            if ($c === ".") {
                if (!$length || $pieceIndex > 6) {
                    return null;
                }
                $p -= $length;
                $numbersSeen = 0;
                while ($p < $end) {
                    $ipv4Piece = null;
                    if ($numbersSeen > 0) {
                        if ($c === "." && $numbersSeen < 4) {
                            $p++;
                        } else {
                            return null;
                        }
                    }
                    if (!is_numeric($input[$p] ?? "")) {
                        return null;
                    }
                    while (strspn($c = ($input[$p] ?? ""), "0123456789")) {
                        if (is_null($ipv4Piece)) {
                            $ipv4Piece = (int) $c;
                        } elseif ($ipv4Piece === 0) {
                            return null;
                        } else {
                            $ipv4Piece = $ipv4Piece * 10 + (int) $c;
                        }
                        if ($ipv4Piece > 255) {
                            return null;
                        }
                        $p++;
                    }
                    $addr[$pieceIndex] = $addr[$pieceIndex] * 0x100 + $ipv4Piece;
                    $numbersSeen++;
                    if ($numbersSeen === 2 || $numbersSeen === 4) {
                        $pieceIndex++;
                    }
                }
                if ($numbersSeen !== 4) {
                    return null;
                }
                break;
            } elseif ($c === ":") {
                $p++;
                if ($p >= $end) {
                    return null;
                }
            } elseif ($p < $end) {
                return null;
            }
            $addr[$pieceIndex++] = $value;
        }
        if (!is_null($compress)) {
            $swaps = $pieceIndex - $compress;
            $pieceIndex = 7;
            while ($pieceIndex !== 0 && $swaps > 0) {
                $dst = $compress + $swaps - 1;
                $cur = $addr[$dst];
                $addr[$dst] = $addr[$pieceIndex];
                $addr[$pieceIndex] = $cur;
                $pieceIndex--;
                $swaps--;
            }
        } elseif (is_null($compress) && $pieceIndex !== 8) {
            return null;
        }
        // now serialize the address back; this in turn is a literal implementation of https://url.spec.whatwg.org/#concept-ipv6-serializer
        $out = "";
        // find the longest compressible span
        $compress = ['index' => null, 'span' => 0];
        $candidate = null;
        $span = 0;
        for ($a = 0; $a <= sizeof($addr); $a++) {
            if (!($addr[$a] ?? 0x10000)) {
                if (is_null($candidate)) {
                    $candidate = $a;
                }
                $span++;
            } elseif (!is_null($candidate)) {
                if ($span > $compress['span']) {
                    $compress['index'] = $candidate;
                    $compress['span'] = $span;
                }
                $candidate = null;
                $span = 0;
            }
        }
        $compress = $compress['span'] > 1 ? $compress['index'] : null;
        $ignoreZero = false;
        for ($a = 0; $a < 8; $a++) {
            if ($ignoreZero && $addr[$a] === 0) {
                continue;
            } elseif ($ignoreZero) {
                $ignoreZero = false;
            }
            if ($a === $compress) {
                $out .= !$a ? "::" : ":";
                $ignoreZero = true;
                continue;
            }
            $out .= dechex($addr[$a]);
            $out .= $a !== 7 ? ":" : "";
        }
        return $out;
    }
}
