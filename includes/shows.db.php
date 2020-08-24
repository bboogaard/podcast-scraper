<?php

namespace PodcastScraper;

use PodcastScraper\Lib\Cache;

class ShowDb {

    private $cache, $wpdb;

    public $episodes_table, $shows_table;

    public function __construct() {

        global $wpdb;

        $this->cache = new Cache('podcast-scraper-');

        $this->wpdb = $wpdb;
        $this->shows_table = $this->wpdb->prefix . "podcast_scraper_shows";
        $this->episodes_table = $this->wpdb->prefix . "podcast_scraper_episodes";

    }

    public function get_shows() {

        return $this->wpdb->get_results(
            "SELECT s.*, e.episode_count FROM " . $this->shows_table . " s " .
            "    LEFT JOIN " .
            "    (" .
            "        SELECT COUNT(id) AS episode_count, show_id " .
            "        FROM " . $this->episodes_table . " " .
            "        GROUP BY show_id " .
            "    ) e ON s.id = e.show_id " .
            "ORDER BY s.show_id"
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

        $show = $this->get_show($id);

        do_action('podcast-scraper-show-delete', $show);

        $this->wpdb->query(
            $this->wpdb->prepare(
                "DELETE FROM " . $this->shows_table . " " .
                "WHERE id = %d",
                $id
            )
        );
        $this->wpdb->query(
            $this->wpdb->prepare(
                "DELETE FROM " . $this->episodes_table . " " .
                "WHERE show_id = %d",
                $id
            )
        );

    }

    public function get_episodes($show_id) {

        return $this->wpdb->get_results(
            $this->wpdb->prepare(
                "SELECT * FROM " . $this->episodes_table . " " .
                "WHERE show_id = %d ORDER BY episode_date DESC",
                $show_id
            )
        );

    }

    public function migrate() {

        $migration = $this->cache->get('migration', 0);
        if ($migration) {
            return;
        }

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
                num_episodes int(10) unsigned DEFAULT 10,
                total_episodes int(10) unsigned DEFAULT 0,
                PRIMARY KEY  (id),
                KEY si_show_id (show_id)
            )".$charset_collate.";
            CREATE TABLE ".$this->episodes_table." (
                id bigint(20) unsigned NOT NULL auto_increment,
                show_id bigint(20) unsigned NOT NULL,
                episode_id varchar(255) NOT NULL,
                episode_title varchar(100) NOT NULL,
                episode_description text DEFAULT '',
                episode_image varchar(255) DEFAULT '',
                episode_file varchar(255) NOT NULL,
                episode_file_size bigint(20) NOT NULL,
                episode_file_type varchar(100) NOT NULL,
                episode_date date NOT NULL,
                update_time bigint(20) unsigned DEFAULT 0,
                PRIMARY KEY  (id),
                KEY si_show_id (show_id)
            )".$charset_collate.";"
        );

        $this->cache->set('migration', 1);

    }

}

class ShowDbManager {

    public static function migrate() {

        $show_db = new ShowDb();
        $show_db->migrate();

    }

}
