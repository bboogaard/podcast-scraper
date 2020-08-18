<?php

namespace PodcastScraper;

use \WP_Error;
use WP\WP_Remote;

class Show {

    private $episodes_table, $shows_table, $wp_remote, $wpdb;

    public function __construct(WP_Remote $wp_remote) {

        global $wpdb;

        $this->wp_remote = $wp_remote;

        $this->wpdb = $wpdb;
        $this->shows_table = $this->wpdb->prefix . "podcast_scraper_shows";
        $this->episodes_table = $this->wpdb->prefix . "podcast_scraper_episodes";

    }

    public function get_shows() {

        return $this->wpdb->get_results(
            "SELECT * FROM " . $this->shows_table . " " .
            "ORDER BY show_id"
        );

    }

    public function add_show($args) {

        $this->wpdb->insert(
            $this->shows_table,
            $args
        );

    }

    public function get_show($id) {

        return $this->wpdb->get_row(
            $this->wpdb->prepare(
                "SELECT * FROM " . $this->shows_table . " " .
                "WHERE id = %d",
                $id
            )
        );

    }

    public function get_show_by_show_id($show_id) {

        return $this->wpdb->get_row(
            $this->wpdb->prepare(
                "SELECT * FROM " . $this->shows_table . " " .
                "WHERE show_id = %s",
                $show_id
            )
        );

    }

    public function update_show($id, $args) {

        $this->wpdb->update(
            $this->shows_table,
            $args,
            array(
                'id' => $id
            )
        );

    }

    public function delete_show($id) {

        return $this->wpdb->query(
            $this->wpdb->prepare(
                "DELETE FROM " . $this->shows_table . " " .
                "WHERE id = %d",
                $id
            )
        );

    }

    public function get_episodes($show_id) {

        return $this->wpdb->get_results(
            $this->wpdb->prepare(
                "SELECT * FROM " . $this->episodes_table . " " .
                "WHERE show_id = %s",
                $show_id
            )
        );

    }

    public function sync_show($id, $force=false) {

        set_time_limit(0);

        $show = $this->get_show($id);
        if (!$show) {
            return new WP_Error( 'no_such_podcast', __('Podcast not found', 'podcast-scraper'), array( 'status' => 404 ) );
        }

        $current_time = time();
        if ($current_time - $show->update_time <= 86400 && !$force) {
            return array();
        }

        $scraper_class = podcast_scraper_get_scraper_class($show->scraper_handle);
        if (!$scraper_class) {
            return new WP_Error('no_scraper', __('No scraper found for podcast', 'podcast-scraper'), array( 'status' => 400 ));
        }

        $scraper = new $scraper_class($this->wp_remote);
        $result = $scraper->scrape($show->show_id, $show->max_episodes);
        if (!$result['show']['show_title']) {
            return new WP_Error('no_show_title', __('No show title found', 'podcast-scraper'), array( 'status' => 409 ));
        }

        $this->update_show(
            $id,
            array(
                'show_title' => $result['show']['show_title'],
                'show_description' => $result['show']['show_description'],
                'show_image' => $result['show']['show_image'],
                'update_time' => $current_time
            )
        );

        $rows = array();
        $this->wpdb->query(
            $this->wpdb->prepare(
                "DELETE FROM " . $this->episodes_table . " " .
                "WHERE show_id = %s",
                $show->show_id
            )
        );
        foreach ($result['episodes'] as $episode) {
            if (!$episode['episode_id'] || !$episode['episode_title'] ||
                    !$episode['episode_file'] || !$episode['episode_date']) {
                return new WP_Error('incomplete_episode', __('Incomplete episode', 'podcast-scraper'), array( 'status' => 409 ));
            }
            array_push(
                $rows,
                array(
                    'show_id' => $show->show_id,
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
        podcast_scraper_wpdb_bulk_insert($this->episodes_table, $rows);

        return $result;

    }

    public function migrate() {

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');

        $charset_collate = '';

        if ( ! empty($this->wpdb->charset) ) {
            $charset_collate = "DEFAULT CHARACTER SET ".$this->wpdb->charset;
        }
        if ( ! empty($this->wpdb->collate) ) {
            $charset_collate .= " COLLATE ".$this->wpdb->collate;
        }

        dbDelta(
            "CREATE TABLE ".$this->shows_table." (
                id bigint(20) unsigned NOT NULL auto_increment,
                show_id varchar(255) NOT NULL,
                scraper_handle varchar(100) NOT NULL,
                show_title varchar(100) DEFAULT '',
                show_description text DEFAULT '',
                show_image varchar(255) DEFAULT '',
                update_time bigint(20) unsigned DEFAULT 0,
                max_episodes int(10) unsigned DEFAULT 30,
                PRIMARY KEY  (id)
            )".$charset_collate.";
            CREATE TABLE ".$this->episodes_table." (
                id bigint(20) unsigned NOT NULL auto_increment,
                show_id varchar(100) NOT NULL,
                episode_id varchar(255) NOT NULL,
                episode_title varchar(100) NOT NULL,
                episode_description text DEFAULT '',
                episode_image varchar(255) DEFAULT '',
                episode_file varchar(255) NOT NULL,
                episode_file_size bigint(20) NOT NULL,
                episode_file_type varchar(100) NOT NULL,
                episode_date date NOT NULL,
                PRIMARY KEY  (id),
                KEY si_show_id (show_id)
            )".$charset_collate.";"
        );

    }

}

class ShowFactory {

    public static function create() {

        return new Show(new WP_Remote());

    }

}
