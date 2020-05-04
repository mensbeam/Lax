<?php
/** @license MIT
 * Copyright 2018 J. King et al.
 * See LICENSE and AUTHORS files for details */

declare(strict_types=1);
namespace MensBeam\Lax\Link;

class Link {
    /** @var bool $rev Whether the relation is a reverse one */
    public $rev = false;
    /** @var string $rel The link relation */
    public $rel;
    /** @var \MensBeam\Lax\Url $url The target URL the link points to */
    public $url;
    /** @var \MensBeam\Lax\Url $anchor The subject URL of the link*/
    public $anchor;
    /** @var \MensBeam\Lax\MimeType $type The media (content) type of the linked-to resource */
    public $type;
    /** @var string $title The title of the linked-to resource */
    public $title;
    /** @var string $lang The language of the linked-to resource */
    public $lang;
    /** @var string $media Media queries applicable to the linked resource */
    public $media;
    /** @var array $attr Extended attributes, if any */
    public $attr = [];
}
