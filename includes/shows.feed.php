<?php

namespace PodcastScraper;

use \DateTime;
use \DateTimeZone;
use PodcastScraper\Lib\Cache;
use PodcastScraper\Lib\Http;

class ShowFeedHandler {

    private $cache, $feed_generator, $feed_id, $http, $show, $show_object;

    public function __construct(Http $http, $show_id) {

        $this->http = $http;

        $this->show = ShowFactory::create();
        $this->show_object = $this->show->get_show_by_show_id($show_id);

        $this->feed_id = sprintf(
            '%d-%s', $this->show_object->id, $this->show_object->show_id
        );

        $this->cache = new Cache('podcast-scraper-');

        $this->feed_generator = new FeedGenerator(
            $this->show_object->show_title,
            site_url(sprintf('feed/podcasts/%s', $this->feed_id)),
            $this->show_object->show_description,
            $this->show_object->show_image,
            null,
            site_url(sprintf('feed/podcasts/%s', $this->feed_id))
        );

        add_action('init', array($this, 'add_feed'));

    }

    public function add_feed() {

        add_feed('podcasts/' . $this->feed_id, array($this, 'render_feed'));

    }

    public function render_feed() {

        $items = $this->get_feed_items();
        $checksum = md5(serialize($items));
        $saved_checksum = $this->cache->get('feed-checksum', '');
        $feed_content = $this->cache->get('feed-content');
        if ($checksum == $saved_checksum && $feed_content) {
            $this->http->send_header('Content-Type: '.feed_content_type('rss-http').'; charset='.
            get_option('blog_charset'));
            echo $feed_content;
            return;
        }

        $dt = new DateTime();
        $dt->setTimezone(new DateTimeZone('Europe/Amsterdam'));

        $feed_content = $this->feed_generator->generate($dt, $items);
        $this->cache->set('feed-checksum', $checksum);
        $this->cache->set('feed-content', $feed_content);
        $this->http->send_header('Content-Type: '.feed_content_type('rss-http').'; charset='.
        get_option('blog_charset'));
        echo $feed_content;

    }

    private function get_feed_items() {

        $items = array();
        $episodes = $this->show->get_episodes($this->show_object->show_id);
        foreach ($episodes as $episode) {
            $dt = DateTime::createFromFormat('Y-m-d', $episode->episode_date);
            array_push(
                $items,
                array(
                    'title' => $episode->episode_title,
                    'description' => $episode->episode_description,
                    'link' => site_url(
                        sprintf('feed/podcasts/%s', $this->feed_id)
                    ),
                    'date' => $dt,
                    'enclosures' => array(
                        array(
                            'url' => $episode->episode_file,
                            'size' => $episode->episode_file_size,
                            'type' => $episode->episode_file_type
                        )
                    )
                )
            );
        }
        return $items;

    }

}

class ShowFeed {

    public static function register() {

        $show = ShowFactory::create();
        $show_objects = $show->get_shows();
        foreach ($show_objects as $show_object) {
            $feed_handler = new ShowFeedHandler(
                new Http(), $show_object->show_id
            );
        }

    }

}
