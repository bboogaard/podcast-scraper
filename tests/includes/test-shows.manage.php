<?php

use PodcastScraper\ShowManager;

/**
 * Class TestShowManager
 *
 * @package PodcastScraper
 */

/**
 * Tests for the ShowManager class
 */
class TestShowManager extends PodcastScraperTestCase {

    function setUp() {

        global $wpdb;

        parent::setUp();

        $this->wpdb = $wpdb;

        $this->wp_redirect = Mockery::mock('WP\WP_Redirect');
        $this->wp_referer = Mockery::mock('WP\WP_Referer');
        $this->manager = new ShowManager(
            $this->wp_referer, $this->wp_redirect, 'foo'
        );

    }

    function tearDown() {

        parent::tearDown();

        Mockery::close();

    }

    public function test_render_manager() {

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
        $show_id = $this->wpdb->insert_id;

        $_REQUEST['page'] = '';

        ob_start();
        $this->manager->render_manager();
        $output = ob_get_clean();

        $this->assertOutputContains('the-talk-show', $output);
        $this->assertOutputContains('podcastluisteren', $output);

    }

    public function test_render_new() {

        ob_start();
        $this->manager->render_new();
        $output = ob_get_clean();

        $this->assertOutputContains(__('Nieuwe podcast', 'podcast-scraper'), $output);

    }

    public function test_render_new_add() {

        $_POST = array(
            'show_id' => 'the-talk-show',
            'scraper_handle' => 'podcastluisteren',
            'num_episodes' => 10
        );

        $this->wp_referer->shouldReceive('check_admin_referer')
                         ->with('podcast-scraper', 'podcast-scraper')
                         ->times(1)
                         ->andReturn(true);

        $this->wp_redirect->shouldReceive('redirect')->times(1);

        ob_start();
        $this->manager->render_new();
        ob_end_clean();

        $rows = $this->wpdb->get_results(
            "SELECT * FROM " . $this->wpdb->prefix . "podcast_scraper_shows"
        );
        $this->assertEquals(1, count($rows));

        $row = $rows[0];
        $actual = $row->show_id;
        $expected = 'the-talk-show';
        $this->assertEquals($expected, $actual);

        $actual = $row->scraper_handle;
        $expected = 'podcastluisteren';
        $this->assertEquals($expected, $actual);

        $actual = $row->num_episodes;
        $expected = 10;
        $this->assertEquals($expected, $actual);

    }

    public function test_render_new_add_with_max_episodes() {

        $_POST = array(
            'show_id' => 'the-talk-show',
            'scraper_handle' => 'podcastluisteren',
            'num_episodes' => 10,
            'max_episodes' => 30
        );

        $this->wp_referer->shouldReceive('check_admin_referer')
                         ->with('podcast-scraper', 'podcast-scraper')
                         ->times(1)
                         ->andReturn(true);

        $this->wp_redirect->shouldReceive('redirect')->times(1);

        ob_start();
        $this->manager->render_new();
        ob_end_clean();

        $rows = $this->wpdb->get_results(
            "SELECT * FROM " . $this->wpdb->prefix . "podcast_scraper_shows"
        );
        $this->assertEquals(1, count($rows));

        $row = $rows[0];
        $actual = $row->show_id;
        $expected = 'the-talk-show';
        $this->assertEquals($expected, $actual);

        $actual = $row->scraper_handle;
        $expected = 'podcastluisteren';
        $this->assertEquals($expected, $actual);

        $actual = $row->num_episodes;
        $expected = 10;
        $this->assertEquals($expected, $actual);

        $actual = $row->max_episodes;
        $expected = 30;
        $this->assertEquals($expected, $actual);

    }

    public function test_render_new_add_with_check_failed() {

        $_POST = array(
            'show_id' => 'the-talk-show',
            'scraper_handle' => 'podcastluisteren',
            'num_episodes' => 10,
            'max_episodes' => 30
        );

        $this->wp_referer->shouldReceive('check_admin_referer')
                         ->with('podcast-scraper', 'podcast-scraper')
                         ->times(1)
                         ->andReturn(false);

        $this->wp_redirect->shouldReceive('redirect')->times(1);

        ob_start();
        $this->manager->render_new();
        ob_end_clean();

        $rows = $this->wpdb->get_results(
            "SELECT * FROM " . $this->wpdb->prefix . "podcast_scraper_shows"
        );
        $this->assertEquals(0, count($rows));

    }

    public function test_render_update() {

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
        $show_id = $this->wpdb->insert_id;

        $_GET['id'] = $show_id;

        ob_start();
        $this->manager->render_update();
        $output = ob_get_clean();

        $this->assertOutputContains(__('Bewerk podcast', 'podcast-scraper'), $output);

        $this->assertOutputContains(
            '<input name="show_id" id="show_id" type="text" required value="the-talk-show" class="regular-text" />',
            $output
        );
        $this->assertOutputContains(
            '<option value="podcastluisteren" selected>Podcast Luisteren</option>',
            $output
        );

    }

    public function test_render_update_edit() {

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
        $show_id = $this->wpdb->insert_id;

        $_GET['id'] = $show_id;

        $_POST = array(
            'show_id' => 'the-talk-show',
            'scraper_handle' => 'podcastluisteren',
            'num_episodes' => 20,
            'max_episodes' => 30
        );

        $this->wp_referer->shouldReceive('check_admin_referer')
                         ->with('podcast-scraper', 'podcast-scraper')
                         ->times(1)
                         ->andReturn(true);

        $this->wp_redirect->shouldReceive('redirect')->times(1);

        ob_start();
        $this->manager->render_update();
        ob_end_clean();

        $row = $this->wpdb->get_row(
            $this->wpdb->prepare(
                "SELECT * FROM " . $this->wpdb->prefix . "podcast_scraper_shows " .
                "WHERE id = %d",
                $show_id
            )
        );

        $actual = $row->show_id;
        $expected = 'the-talk-show';
        $this->assertEquals($expected, $actual);

        $actual = $row->scraper_handle;
        $expected = 'podcastluisteren';
        $this->assertEquals($expected, $actual);

        $actual = $row->num_episodes;
        $expected = 20;
        $this->assertEquals($expected, $actual);

        $actual = $row->max_episodes;
        $expected = 30;
        $this->assertEquals($expected, $actual);

    }

    public function test_render_update_edit_with_check_failed() {

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
        $show_id = $this->wpdb->insert_id;

        $_GET['id'] = $show_id;

        $_POST = array(
            'show_id' => 'the-talk-show',
            'scraper_handle' => 'podcastluisteren',
            'num_episodes' => 20,
            'max_episodes' => 30
        );

        $this->wp_referer->shouldReceive('check_admin_referer')
                         ->with('podcast-scraper', 'podcast-scraper')
                         ->times(1)
                         ->andReturn(false);

        $this->wp_redirect->shouldReceive('redirect')->times(1);

        ob_start();
        $this->manager->render_update();
        ob_end_clean();

        $row = $this->wpdb->get_row(
            $this->wpdb->prepare(
                "SELECT * FROM " . $this->wpdb->prefix . "podcast_scraper_shows " .
                "WHERE id = %d",
                $show_id
            )
        );

        $actual = $row->show_id;
        $expected = 'the-talk-show';
        $this->assertEquals($expected, $actual);

        $actual = $row->scraper_handle;
        $expected = 'podcastluisteren';
        $this->assertEquals($expected, $actual);

        $actual = $row->num_episodes;
        $expected = 20;
        $this->assertNotEquals($expected, $actual);

        $actual = $row->max_episodes;
        $expected = 30;
        $this->assertNotEquals($expected, $actual);

    }

    public function test_render_update_with_show_not_found() {

        $_GET['id'] = 1;

        $this->wp_redirect->shouldReceive('redirect')->times(1);

        ob_start();
        $this->manager->render_update();
        ob_end_clean();

        $this->assertTrue(true);

    }

}
