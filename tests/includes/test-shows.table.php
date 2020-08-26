<?php

use PodcastScraper\ShowTable;

/**
 * Class TestShowTable
 *
 * @package PodcastScraper
 */

/**
 * Tests for the ShowTable class
 */
class TestShowTable extends PodcastScraperTestCase {

    function setUp() {

        global $wpdb;

        parent::setUp();

        $this->wpdb = $wpdb;

        $this->setUpTestData();

    }

    function tearDown() {

        parent::tearDown();

        Mockery::close();

    }

    function setUpTestData() {

        $this->wpdb->insert(
            $this->wpdb->prefix . "podcast_scraper_shows",
            array(
                'show_id' => 'the-talk-show',
                'scraper_handle' => 'podcastluisteren',
                'show_title' => 'The Talk Show',
                'show_description' => 'Lorem ipsum dolor sit amet...',
                'show_image' => '/path/to/image.jpg'
            )
        );
        $this->show_id = $this->wpdb->insert_id;

        $this->wpdb->insert(
            $this->wpdb->prefix . "podcast_scraper_episodes",
            array(
                'show_id' => $this->show_id,
                'episode_id' => 'we-talk-about-stuff',
                'episode_title' => 'We talk about stuff',
                'episode_description' => 'Foo bar baz qux...',
                'episode_file' => '/path/to/audio.mp3',
                'episode_file_size' => 60000000,
                'episode_file_type' => 'audio/mp3',
                'episode_date' => '2020-08-16'
            )
        );
        $this->wpdb->insert(
            $this->wpdb->prefix . "podcast_scraper_episodes",
            array(
                'show_id' => $this->show_id,
                'episode_id' => 'the-buzz',
                'episode_title' => 'The buzz',
                'episode_description' => 'Buzz...',
                'episode_file' => '/path/to/buzz.mp3',
                'episode_file_size' => 50000000,
                'episode_file_type' => 'audio/mp3',
                'episode_date' => '2020-08-15'
            )
        );

    }

    public function test_prepare_items() {

        $table = new ShowTable('foo');
        $table->prepare_items();

        $actual = $table->items;
        $expected = array(
            array(
                'id' => $this->show_id,
                'show_id' => 'the-talk-show',
                'scraper_handle' => 'podcastluisteren',
                'show_title' => 'The Talk Show',
                'show_description' => 'Lorem ipsum dolor sit amet...',
                'show_image' => '/path/to/image.jpg',
                'update_time' => 0,
                'max_episodes' => 0,
                'num_episodes' => 10,
                'total_episodes' => 0,
                'episode_count' => 2
            )
        );
        $this->assertEquals($expected, $actual);

    }

}
