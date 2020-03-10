<?php
/** @license MIT
 * Copyright 2018 J. King et al.
 * See LICENSE and AUTHORS files for details */

declare(strict_types=1);
namespace MensBeam\Lax\Enclosure;

class Collection extends \MensBeam\Lax\Collection {

    /** Returns the primary ("best") enclosure of the collection
     *
     * Videos are preferred over audios, which are preferred over images, which are preferred over anything else
     *
     * Videos are first ranked by length, then resolution (total pixels), then bitrate, then size; audios are ranked by length, then bitrate, then size; images are ranked by resolution (total pixels), then size
     *
     */
    public function primary(): ?Enclosure {
        # stub
        return null;
    }
}
