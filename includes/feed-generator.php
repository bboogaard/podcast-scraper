<?php

namespace PodcastScraper;

use \DateTime;
use \FeedWriter\RSS2;

class FeedGenerator {

    private $title, $link, $description, $image, $language, $self_link;

    public function __construct($title, $link, $description, $image=null,
                                $language=null, $self_link=null) {

        $this->title = $title;
        $this->link = $link;
        $this->description = $description;
        $this->image = $image;
        $this->language = $language;
        $this->self_link = $self_link;

    }

    public function generate(DateTime $pub_date, $items) {

        $feed = new RSS2();

        $feed->setTitle($this->title);
        $feed->setLink($this->link);
        $feed->setDescription($this->description);
        if ($this->image) {
            $feed->setImage($this->image, $this->title, $this->link);
        }
        if ($this->language) {
            $feed->setChannelElement('language', $this->language);
        }
        $feed->setChannelElement('pubDate', $pub_date->format('r'));
        if ($this->self_link) {
            $feed->setSelfLink($this->self_link);
        }

        $feed->addGenerator();

        foreach ($items as $item) {
            $feed_item = $feed->createNewItem();
            $item_attributes = $this->get_item_attributes($item);
            $feed_item->setTitle($item_attributes['title']);
            $feed_item->setLink($item_attributes['link']);
            $feed_item->setDescription($item_attributes['description']);
            $feed_item->setDate($item_attributes['date']->format('r'));
            if (!empty($item_attributes['enclosures'])) {
                foreach ($item_attributes['enclosures'] as $enclosure) {
                    $feed_item->addEnclosure(
                        $enclosure['url'],
                        $enclosure['size'],
                        $enclosure['type']
                    );
                }
            }
            if ($item_attributes['author']) {
                $feed_item->setAuthor($item_attributes['author']);
            }
            if ($item_attributes['url']) {
                $feed_item->setId($item_attributes['url'], true);
            }
            $feed->addItem($feed_item);
        }

        return $feed->generateFeed();

    }

    protected function get_item_attributes($item) {

        $attributes = wp_parse_args($item, array(
            'title' => '',
            'link' => '',
            'description' => '',
            'date' => null,
            'enclosures' => array(),
            'author' => '',
            'url' => ''
        ));
        $attributes['enclosures'] = array_map(function($enclosure) {
            return wp_parse_args($enclosure, array(
                'url' => '',
                'size' => '',
                'type' => ''
            ));
        }, $attributes['enclosures']);

        return $attributes;

    }

}
