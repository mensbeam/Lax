<?php
/** @license MIT
 * Copyright 2018 J. King et al.
 * See LICENSE and AUTHORS files for details */

declare(strict_types=1);
namespace JKingWeb\Lax;

trait XMLEntryPrimitives {

    /** Primitive to fetch a collection of people associated with an RSS entry */
    protected function getPeopleRss2() {
        $nodes = $this->fetchElements("./author");
        if (!$nodes->length) {
            return null;
        }
        $out = new PersonCollection;
        foreach ($nodes as $node) {
            $text = $this->trimText($node->textContent);
            if (strlen($text)) {
                $p = $this->parsePersonText($text);
                $p->role = $node->localName;
                $out[] = $p;
            }
        }
        return $out;
    }
}
