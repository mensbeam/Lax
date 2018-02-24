<?php
/** @license MIT
 * Copyright 2018 J. King et al.
 * See LICENSE and AUTHORS files for details */

declare(strict_types=1);
namespace JKingWeb\Lax;

trait XMLEntryPrimitives {

    /** Primitive to fetch a collection of people associated with an RSS entry
     * 
     * For RSS 2.0 this includes both native metadata and Dublin Core
     */
    protected function getPeopleRss2() {
        $nodes = $this->fetchElements("./author|./dc:creator|./dc:contributor");
        if (!$nodes->length) {
            return null;
        }
        $out = new PersonCollection;
        $roles = [
            'author'         => "author",
            'creator'        => "author",
            'contributor'    => "contributor",
        ];
        foreach ($nodes as $node) {
            $text = $this->trimText($node->textContent);
            if (strlen($text)) {
                $p = $this->parsePersonText($text);
                $p->role = $roles[$node->localName];
                $out[] = $p;
            }
        }
        return $out;
    }
}
