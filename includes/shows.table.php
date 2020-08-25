<?php

namespace PodcastScraper;

if(!class_exists('WP_List_Table')){
    require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
}

use \WP_List_Table;

class ShowTable extends WP_List_Table {

    private $show_db;

    function __construct($screen=null) {

        global $status, $page;

        parent::__construct( array(
            'singular'  => 'podcast',
            'plural'    => 'podcasts',
            'ajax'      => false,
            'screen'    => $screen
        ) );

        $this->show_db = new ShowDb();

    }

    function column_default($item, $column_name) {

        switch($column_name) {
            case 'cb':
            case 'show_id':
            case 'scaper_handle':
            case 'feed_url':
            case 'episode_count':
                return $item[$column_name];
            default:
                return print_r($item,true); //Show the whole array for troubleshooting purposes
        }

    }

    function column_cb($item) {

        return sprintf(
            '<input type="checkbox" name="%1$s[]" value="%2$s" />',
            $this->_args['singular'],
            $item['id']
        );

    }

    function column_show_id($item) {

        $edit_url = add_query_arg(
            array(
                'page' => 'podcast_scraper_edit',
                'id' => $item['id']
            ),
            admin_url('admin.php')
        );

        $actions = array(
            'edit'      => sprintf('<a href="%s">%s</a>', $edit_url,  __('Bewerken', 'podcast-scraper')),
            'delete'    => sprintf('<a href="?page=%s&action=%s&id=%d">%s</a>', $_REQUEST['page'], 'delete', $item['id'], __('Verwijderen', 'podcast-scraper')),
            'sync'      => sprintf('<a href="?page=%s&action=%s&id=%d">%s</a>', $_REQUEST['page'], 'sync', $item['id'], __('Synchroniseren', 'podcast-scraper')),
        );

        return sprintf('<strong>%1$s</strong> %2$s',
            $item['show_id'],
            $this->row_actions($actions)
        );

    }

    function column_scraper_handle($item) {

        return sprintf('<span>%1$s</span>', $item['scraper_handle']);

    }

    function column_feed_url($item) {

        return sprintf(
            '<span><a href="%1$s" target="_blank">%2$s</a></span>',
            esc_attr(
                site_url(sprintf('feed/podcasts/%d-%s', $item['id'], $item['show_id']))
            ),
            site_url(sprintf('feed/podcasts/%d-%s', $item['id'], $item['show_id']))
        );

    }

    function column_episode_count($item) {

        if (!$item['update_time']) {
            return sprintf(
                '<span>%1$s</span>', __('Nog niet gesynchroniseerd', 'podcast-scraper')
            );
        }

        return sprintf('<span>%1$s (van %2$s)</span>', $item['episode_count'], $item['total_episodes']);

    }

    function get_columns() {

        $columns = array(
            'cb'              => '<input type="checkbox" />',
            'show_id'         => __('Podcast-id', 'podcast-scraper'),
            'scraper_handle'  => __('Scraper', 'podcast-scraper'),
            'feed_url'        => __('Feed-url', 'podcast-scraper'),
            'episode_count'   => __('Aantal afleveringen', 'podcast-scraper')
        );
        return $columns;

    }

    function get_bulk_actions() {

        $actions = array(
            'delete'    => __('Verwijderen', 'podcast-scraper')
        );
        return $actions;

    }

    function process_bulk_action() {

        if ( 'delete' === $this->current_action() ) {
            if ( isset($_GET['id']) ) {
                $id = $_GET['id'];
                $this->show_db->delete_show( $id );
            }
            if (isset($_GET['podcast'])) {
                foreach ( $_GET['podcast'] as $id ) {
                    $this->show_db->delete_show( $id );
                }
            }

            $redirect = add_query_arg(
                array(
                    'page' => 'podcast_scraper_settings',
                    'message' => 'deleted'
                ),
                'admin.php'
            );
            wp_redirect( $redirect );
        }

        if ( 'sync' === $this->current_action() ) {
            if ( isset($_GET['id']) ) {
                $id = $_GET['id'];
                $show = $this->show_db->get_show( $id );
                $feed_handler = ShowFeed::get_feed_handler($show);
                $feed_handler->sync_show();
            }

            $redirect = add_query_arg(
                array(
                    'page' => 'podcast_scraper_settings',
                    'message' => 'sync'
                ),
                'admin.php'
            );
            wp_redirect( $redirect );
        }

    }

    function prepare_items() {

        $columns = $this->get_columns();
        $hidden = array();
        $sortable = array();
        $this->_column_headers = array($columns, $hidden, $sortable);
        $this->process_bulk_action();

        $shows = $this->show_db->get_shows();
        $items = array();
        foreach ($shows as $show) {
            array_push($items, (array)$show);
        }
        $this->items = $items;

    }

}
