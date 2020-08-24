<?php

namespace PodcastScraper;

use \DateTime;
use \DateTimeZone;
use PodcastScraper\Lib\Cache;
use PodcastScraper\Lib\Http;
use WP\WP_Remote;

class ShowFeedHandler {

    private $cache, $feed_generator, $feed_id, $http, $show, $show_db, $scraper,
    $wpdb;

    public function __construct(Http $http, $show, $scraper) {

        global $wpdb;

        $this->http = $http;
        $this->show = $show;
        $this->scraper = $scraper;

        $this->wpdb = $wpdb;
        $this->show_db = new ShowDb();

        $this->feed_id = sprintf(
            '%d-%s', $this->show->id, $this->show->show_id
        );

        $this->cache = new Cache(sprintf('podcast-scraper-%d-', $this->show->id));

        $this->feed_generator = new FeedGenerator(
            $this->show->show_title,
            site_url(sprintf('feed/podcasts/%s', $this->feed_id)),
            $this->show->show_description,
            $this->show->show_image,
            null,
            site_url(sprintf('feed/podcasts/%s', $this->feed_id))
        );

        add_action('init', array($this, 'add_feed'));

        add_action('podcast-scraper-show-delete', array($this, 'delete_show'));

    }

    public function add_feed() {

        add_feed('podcasts/' . $this->feed_id, array($this, 'render_feed'));

    }

    public function delete_show($show) {

        if ($show->id == $this->show->id) {
            $this->cache->delete('feed-updated');
            $this->cache->delete('feed-content');
            $this->cache->delete('episode-offset');
        }

    }

    public function render_feed() {

        if (false === $update_time = $this->sync_show()) {
            $this->http->send_header('Content-Type: text/plain');
            echo '';
            return;
        }

        $feed_updated = $this->cache->get('feed-updated', 0);
        $feed_content = $this->cache->get('feed-content');
        if ($feed_updated == $update_time && $feed_content) {
            $this->http->send_header('Content-Type: '.feed_content_type('rss-http').'; charset='.
            get_option('blog_charset'));
            echo $feed_content;
            return;
        }

        $dt = new DateTime();
        $dt->setTimezone(new DateTimeZone('Europe/Amsterdam'));

        $items = $this->get_feed_items();

        $feed_content = $this->feed_generator->generate($dt, $items);
        $this->cache->set('feed-updated', $update_time);
        $this->cache->set('feed-content', $feed_content);
        $this->http->send_header('Content-Type: '.feed_content_type('rss-http').'; charset='.
        get_option('blog_charset'));
        echo $feed_content;

    }

    public function sync_show() {

        $episodes = $this->scraper->get_episodes(
            $this->show->show_id, $this->show->max_episodes);

        $new_episodes = array();
        foreach ($episodes as $episode) {
            array_push($new_episodes, $episode['episode_id']);
        }

        $db_episodes = $this->show_db->get_episodes($this->show->id);
        $existing_episodes = array();
        foreach ($db_episodes as $episode) {
            array_push($existing_episodes, $episode->episode_id);
        }

        sort($new_episodes);
        sort($existing_episodes);
        $diff_new = array_diff($new_episodes, $existing_episodes);
        $diff_old = array_diff($existing_episodes, $new_episodes);
        if (empty($diff_new) && empty($diff_old)) {
            return $this->show->update_time;
        }

        $offset = $this->cache->get('episode-offset', 0);
        if ($offset >= count($new_episodes)) {
            $offset = 0;
        }

        $result = $this->scraper->scrape(
            $this->show->show_id, $this->show->max_episodes,
            $this->show->num_episodes, $offset);
        if (!$result['show']['show_title']) {
            error_log('Incomplete show info.');
            return false;
        }

        $current_time = time();

        $this->show_db->update_show(
            $this->show->id,
            array(
                'show_title' => $result['show']['show_title'],
                'show_description' => $result['show']['show_description'],
                'show_image' => $result['show']['show_image'],
                'update_time' => $current_time,
                'total_episodes' => count($new_episodes)
            )
        );

        $insert_rows = array();
        foreach ($result['episodes'] as $episode) {
            if (!$episode['episode_id'] || !$episode['episode_title'] ||
                    !$episode['episode_file'] || !$episode['episode_date']) {
                error_log(sprintf('Incomplete episode info for episode %s', print_r($episode, true)));
                return false;
            }
            if (!in_array($episode['episode_id'], $existing_episodes)) {
                array_push(
                    $insert_rows,
                    array(
                        'show_id' => $this->show->id,
                        'episode_id' => $episode['episode_id'],
                        'episode_title' => $episode['episode_title'],
                        'episode_description' => $episode['episode_description'],
                        'episode_image' => $episode['episode_image'],
                        'episode_file' => $episode['episode_file'],
                        'episode_file_size' => $episode['episode_file_size'],
                        'episode_file_type' => $episode['episode_file_type'],
                        'episode_date' => $episode['episode_date']
                    )
                );
            }
        }
        if (!empty($insert_rows)) {
            podcast_scraper_wpdb_bulk_insert(
                $this->show_db->episodes_table, $insert_rows
            );
        }

        if (!empty($diff_old)) {
            $this->wpdb->query(
                $this->wpdb->prepare(
                    podcast_scraper_wpdb_in_format(
                        "DELETE FROM " . $this->show_db->episodes_table . " " .
                        "WHERE show_id = %d AND episode_id IN %s",
                        $diff_old
                    ),
                    array_merge(array($this->show->id), $diff_old)
                )
            );
        }

        $this->cache->set('episode-offset', $offset + $this->show->num_episodes);

        return $current_time;

    }

    private function get_feed_items() {

        $items = array();
        $episodes = $this->show_db->get_episodes($this->show->id);
        foreach ($episodes as $episode) {
            $dt = DateTime::createFromFormat('Y-m-d', $episode->episode_date);
            $dt->setTime(0, 0, 0);
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

    public static function get_feed_handler($show) {

        $scraper_class = podcast_scraper_get_scraper_class(
            $show->scraper_handle);
        if (!$scraper_class) {
            return null;
        }
        return new ShowFeedHandler(
            new Http(), $show, new $scraper_class(new WP_Remote())
        );

    }

    public static function register() {

        $show_db = new ShowDb();
        $shows = $show_db->get_shows();
        foreach ($shows as $show) {
            self::get_feed_handler($show);
        }

    }

}
