<?php
/** @license MIT
 * Copyright 2018 J. King et al.
 * See LICENSE and AUTHORS files for details */

declare(strict_types=1);
namespace JKingWeb\Lax\JSON;

use JKingWeb\Lax\Person\Person;
use JKingWeb\Lax\Person\Collection as PersonCollection;

class Feed extends \JKingWeb\Lax\Feed {
    use Construct;

    protected $reqUrl;

    /** Constructs a parsed feed */
    public function __construct(string $data, string $contentType = "", string $url = "") {
        $this->reqUrl = $url;
        $this->init($data, $contentType);
        $this->parse();
    }

    /** Performs initialization of the instance */
    protected function init(string $data, string $contentType = "") {
        $this->json = json_decode($data);
        $this->url = $this->reqUrl;
        $this->type = "json";
        $this->version = $this->fetchMember("version", "str") ?? "";
    }
    
    /** General function to fetch the feed title */
    public function getTitle(): string {
        return $this->fetchMember("title", "str") ?? "";
    }

    /** General function to fetch the feed's Web-representation URL */
    public function getLink(): string {
        return $this->fetchUrl("home_page_url") ?? "";
    }

    /** General function to fetch the description of a feed */
    public function getSummary(): string {
        return $this->fetchMember("description", "str") ?? "";
    }

    /** General function to fetch the categories of a feed 
     * 
     * JSON Feed does not have categories at the feed level, so this always returns an empty array
    */
    public function getCategories(bool $grouped = false, bool $humanFriendly = true): array {
        return [];
    }

    /** General function to fetch the feed identifier 
     * 
     * For JSON feeds this is always the feed URL specified in the feed
    */
    public function getId(): string {
        //return $this->getUrl();
        return "";
    }

    /** General function to fetch a collection of people associated with a feed */
    public function getPeople(): PersonCollection {
        $out = new PersonCollection;
        $author = $this->fetchMember("author", "object");
        if (!isset($author)) {
            return $out;
        } else {
            $p = new Person;
            $p->name = $this->fetchMember("name", "str", $author) ?? "";
            $p->url = $this->fetchUrl("url", $author) ?? "";
            $p->avatar = $this->fetchUrl("avatar", $author) ?? "";
            $p->role = "author";
            $out[] = $p;
            return $out;
        }
    }
}