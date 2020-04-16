<?php
/** @license MIT
 * Copyright 2018 J. King et al.
 * See LICENSE and AUTHORS files for details */

declare(strict_types=1);
namespace MensBeam\Lax\Parser;

use function PHPSTORM_META\type;

/** @property-read string $essence */
class MimeType {
    protected const TYPE_PATTERN = <<<'PATTERN'
        <^
            [\t\r\n ]*                              # optional leading whitespace
            ([^/]+)                                 # type  
            /                                       # type/subtype delimiter
            ([^;]+)                                 # subtype (possibly with trailing whitespace)
            (;.*)?                                  # optional parameters, to be parsed separately
            [\t\r\n ]*                              # optional trailing whitespace
        $>sx
PATTERN;
    protected const PARAM_PATTERN = <<<'PATTERN'
        <
            [;\t\r\n ]*                             # parameter delimiter and leading whitespace, all optional
            ([^=;]*)                                # parameter name; may be empty
            (?:=                                    # parameter name/value delimiter
                (
                    "(?:\\"|[^"])*(?:"|$)[^;]*      # quoted parameter value and optional garbage
                    |[^;]*                          # unquoted parameter value (possibly with trailing whitespace)
                )
            )?
            ;?                                      # optional trailing parameter delimiter
            [\t\r\n ]*                              # optional trailing whitespace
        >sx
PATTERN;
    protected const TOKEN_PATTERN = '<^[A-Za-z0-9!#$%&\'*+\-\.\^_`|~]+$>s';
    protected const BARE_VALUE_PATTERN = '<^[\t\x{20}-\x{7E}\x{80}-\x{FF}]+$>su';
    protected const QUOTED_VALUE_PATTERN = '<^"((?:\\\"|[\t !\x{23}-\x{7E}\x{80}-\x{FF}])*)(?:"|$)>su';
    protected const ESCAPE_PATTERN = '<\\\(.)>s';

    public $type = "";
    public $subtype = "";
    public $params = [];
    private $essence;

    public function __construct(string $type = "", string $subtype = "", array $params = []) {
        $this->type = $type;
        $this->subtype = $subtype;
        $this->params = $params;
    }

    public function __get(string $name) {
        if ($name === "essence") {
            return $this->type."/".$this->subtype;
        }
        return $this->$name ?? null;
    }

    public function __toString(): string {
        $out = $this->__get("essence");
        if (is_array($this->params) && sizeof($this->params)) {
            foreach ($this->params as $name => $value) {
                $out .= ";$name=".(preg_match(self::TOKEN_PATTERN, $value) ? $value : '"'.str_replace(["\\", '"'], ["\\\\", "\\\""], $value).'"');
            }
        }
        return $out;
    }

    public static function parse(string $mimeType): ?self {
        if (preg_match(self::TYPE_PATTERN, $mimeType, $match)) {
            [$mimeType, $type, $subtype, $params] = array_pad($match, 4, "");
            if (strlen($type = static::parseHttpToken($type)) && strlen($subtype = static::parseHttpToken(rtrim($subtype, "\t\r\n ")))) {
                return new static(strtolower($type), strtolower($subtype), static::parseParams($params));
            }
        }
        return null;
    }

    protected static function parseParams(string $params): array {
        $out = [];
        if (preg_match_all(self::PARAM_PATTERN, $params, $matches, \PREG_SET_ORDER)) {
            foreach ($matches as $match) {
                [$param, $name, $value] = array_pad($match, 3, "");
                $name = strtolower(static::parseHttpToken($name));
                if (!strlen($name) || isset($out[$name])) {
                    continue;
                } elseif (strlen($value) && $value[0] === '"') {
                    $value = static::parseHttpQuotedValue($value);
                    if (is_null($value)) {
                        continue;
                    }
                } else {
                    $value = static::parseHttpBareValue($value);
                    if (!strlen($value)) {
                        continue;
                    }
                }
                $out[$name] = $value;
            }
        }
        return $out;
    }

    protected static function parseHttpToken(string $token): string {
        if (preg_match(self::TOKEN_PATTERN, $token, $match)) {
            return $token;
        }
        return "";
    }

    protected static function parseHttpBareValue(string $value): string {
        $value = rtrim($value, "\t\r\n ");
        if (preg_match(self::BARE_VALUE_PATTERN, $value, $match)) {
            return $value;
        }
        return "";
    }

    protected static function parseHttpQuotedValue(string $value): ?string {
        if (preg_match(self::QUOTED_VALUE_PATTERN, $value, $match)) {
            return preg_replace(self::ESCAPE_PATTERN, '$1', $match[1]);
        }
        return null;
    }
}
