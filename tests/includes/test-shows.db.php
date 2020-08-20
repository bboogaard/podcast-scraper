<?php

use PodcastScraper\ShowDb;

/**
 * Class TestShowDb
 *
 * @package PodcastScraper
 */

/**
 * Tests for the ShowDb class
 */
class TestShowDb extends WP_UnitTestCase {

    function setUp() {

        global $wpdb;

        parent::setUp();

        $this->wpdb = $wpdb;

        $this->show_db = new ShowDb();

        $this->setUpTestData();

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

    public function test_get_shows() {

        $shows = $this->show_db->get_shows();
        $actual = array();
        foreach ($shows as $show) {
            array_push($actual, $show->show_id);
        }
        $expected = array('the-talk-show');
        $this->assertEquals($expected, $actual);

    }

    public function test_add_show() {

        $this->show_db->add_show(array(
            'show_id' => 'lorem',
            'show_title' => 'Lorem',
            'scraper_handle' => 'podcastluisteren',
            'show_description' => 'Lorem...',
            'show_image' => '/path/to/lorem.jpg'
        ));

        $show = $this->wpdb->get_row(
            "SELECT * FROM " . $this->wpdb->prefix . "podcast_scraper_shows WHERE show_id = 'lorem'"
        );
        $actual = $show->show_title;
        $expected = 'Lorem';
        $this->assertEquals($expected, $actual);

        $actual = $show->scraper_handle;
        $expected = 'podcastluisteren';
        $this->assertEquals($expected, $actual);

        $actual = $show->show_description;
        $expected = 'Lorem...';
        $this->assertEquals($expected, $actual);

        $actual = $show->show_image;
        $expected = '/path/to/lorem.jpg';
        $this->assertEquals($expected, $actual);

    }

    public function test_get_show() {

        $show = $this->show_db->get_show($this->show_id);
        $actual = $show->show_title;
        $expected = 'The Talk Show';
        $this->assertEquals($expected, $actual);

        $actual = $show->scraper_handle;
        $expected = 'podcastluisteren';
        $this->assertEquals($expected, $actual);

        $actual = $show->show_description;
        $expected = 'Lorem ipsum dolor sit amet...';
        $this->assertEquals($expected, $actual);

        $actual = $show->show_image;
        $expected = '/path/to/image.jpg';
        $this->assertEquals($expected, $actual);

    }

    public function test_get_show_by_show_id() {

        $show = $this->show_db->get_show_by_show_id('the-talk-show');
        $actual = $show->show_title;
        $expected = 'The Talk Show';
        $this->assertEquals($expected, $actual);

        $actual = $show->scraper_handle;
        $expected = 'podcastluisteren';
        $this->assertEquals($expected, $actual);

        $actual = $show->show_description;
        $expected = 'Lorem ipsum dolor sit amet...';
        $this->assertEquals($expected, $actual);

        $actual = $show->show_image;
        $expected = '/path/to/image.jpg';
        $this->assertEquals($expected, $actual);

    }

    public function test_update_show() {

        $this->show_db->update_show(
            $this->show_id,
            array(
                'show_id' => 'lorem-ipsum',
                'show_title' => 'Lorem Ipsum'
            )
        );

        $show = $this->wpdb->get_row(
            "SELECT * FROM " . $this->wpdb->prefix . "podcast_scraper_shows WHERE show_id = 'lorem-ipsum'"
        );
        $actual = $show->show_title;
        $expected = 'Lorem Ipsum';
        $this->assertEquals($expected, $actual);

    }

    public function test_delete_show() {

        $this->show_db->delete_show($this->show_id);

        $actual = $this->wpdb->get_row(
            "SELECT * FROM " . $this->wpdb->prefix . "podcast_scraper_shows WHERE show_id = 'the-talk-show'"
        );
        $this->assertNull($actual);

        $actual = $this->wpdb->get_results(
            $this->wpdb->prepare(
                "SELECT * FROM " . $this->wpdb->prefix . "podcast_scraper_episodes WHERE show_id = %d",
                $this->show_id
            )
        );
        $expected = array();
        $this->assertEquals($expected, $actual);

    }

    public function test_get_episodes() {

        $episodes = $this->show_db->get_episodes($this->show_id);
        $actual = array();
        foreach ($episodes as $episode) {
            array_push($actual, $episode->episode_id);
        }
        $expected = array('we-talk-about-stuff', 'the-buzz');
        $this->assertEquals($expected, $actual);

    }

}
