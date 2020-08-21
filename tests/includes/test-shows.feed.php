<?php

use PodcastScraper\ShowFeedHandler;

/**
 * Class TestShowFeedHandler
 *
 * @package PodcastScraper
 */

/**
 * Tests for the ShowFeedHandler class
 */
class TestShowFeedHandler extends WP_UnitTestCase {

    function setUp() {

        global $wpdb;

        parent::setUp();

        $this->wpdb = $wpdb;

        $this->http = Mockery::mock('PodcastScraper\Lib\Http');
        $this->scraper = Mockery::mock('PodcastScraper\PodcastLuisterenScraper');

        $this->setUpTestData();

    }

    function tearDown() {

        parent::tearDown();

        Mockery::close();

        delete_option('podcast-scraper-feed-updated');
        delete_option('podcast-scraper-feed-content');

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

    }

    public function test_render_feed() {

        $show = $this->wpdb->get_row(
            $this->wpdb->prepare(
                "SELECT * FROM " . $this->wpdb->prefix . "podcast_scraper_shows " .
                "WHERE id = %d",
                $this->show_id
            )
        );

        $this->scraper->shouldReceive('get_episodes')
                      ->with($show->show_id, $show->max_episodes)
                      ->andReturn(array(
                          array(
                              'episode_id' => 'we-talk-about-stuff'
                          ),
                          array(
                              'episode_id' => 'the-buzz'
                          )
                      ));

        $this->scraper->shouldReceive('scrape')
                      ->with($show->show_id, $show->max_episodes)
                      ->andReturn(
                          array(
                              'show' => array(
                                  'show_title' => 'The talk show',
                                  'show_description' => 'Lorem ipsum dolor sit amet...',
                                  'show_image' => '/path/to/image.jpg'
                              ),
                              'episodes' => array(
                                  array(
                                      'episode_id' => 'we-talk-about-stuff',
                                      'episode_title' => 'We talk about stuff',
                                      'episode_description' => 'Foo bar baz qux...',
                                      'episode_image' => '/path/to/image.jpg',
                                      'episode_file' => '/path/to/audio.mp3',
                                      'episode_date' => '2020-08-15',
                                      'episode_file_size' => 60000000,
                                      'episode_file_type' => 'audio/mp3'
                                  ),
                                  array(
                                      'episode_id' => 'the-buzz',
                                      'episode_title' => 'The buzz',
                                      'episode_description' => 'Buzz...',
                                      'episode_image' => '/path/to/image.jpg',
                                      'episode_file' => '/path/to/buzz.mp3',
                                      'episode_date' => '2020-08-16',
                                      'episode_file_size' => 50000000,
                                      'episode_file_type' => 'audio/mp3'
                                  )
                              )
                      ));

        $this->http->shouldReceive('send_header')->times(1);

        $feed_handler = new ShowFeedHandler($this->http, $show, $this->scraper);
        ob_start();
        $feed_handler->render_feed();
        $output = ob_get_clean();

        $this->assertOutputContains('<title>The Talk Show</title>', $output);
        $this->assertOutputContains(
            sprintf(
                '<link>http://example.org/feed/podcasts/%d-the-talk-show</link>',
                $this->show_id
            ),
            $output
        );
        $this->assertOutputContains('<url>/path/to/image.jpg</url>', $output);

        $this->assertOutputContains('<title>The buzz</title>', $output);
        $this->assertOutputContains(
            '<pubDate>Sun, 16 Aug 2020 00:00:00 +0000</pubDate>',
            $output
        );
        $this->assertOutputContains(
            '<enclosure length="50000000" type="audio/mp3" url="/path/to/buzz.mp3"></enclosure>',
            $output
        );

        $this->assertOutputContains('<title>We talk about stuff</title>', $output);
        $this->assertOutputContains(
            '<pubDate>Sat, 15 Aug 2020 00:00:00 +0000</pubDate>',
            $output
        );
        $this->assertOutputContains(
            '<enclosure length="60000000" type="audio/mp3" url="/path/to/audio.mp3"></enclosure>',
            $output
        );

        $show = $this->wpdb->get_row(
            $this->wpdb->prepare(
                "SELECT * FROM " . $this->wpdb->prefix . "podcast_scraper_shows " .
                "WHERE id = %d",
                $this->show_id
            )
        );
        $actual = $show->update_time;
        $expected = 0;
        $this->assertNotEquals($expected, $actual);

        $actual = $this->wpdb->get_var(
            $this->wpdb->prepare(
                "SELECT COUNT(id) FROM " . $this->wpdb->prefix . "podcast_scraper_episodes " .
                "WHERE show_id = %d",
                $this->show_id
            )
        );
        $expected = 2;
        $this->assertEquals($expected, $actual);

    }

    public function test_render_feed_not_changed() {

        $update_time = time();

        $this->wpdb->update(
            $this->wpdb->prefix . "podcast_scraper_shows",
            array(
                'update_time' => $update_time
            ),
            array(
                'id' => $this->show_id
            )
        );

        $show = $this->wpdb->get_row(
            $this->wpdb->prepare(
                "SELECT * FROM " . $this->wpdb->prefix . "podcast_scraper_shows " .
                "WHERE id = %d",
                $this->show_id
            )
        );

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
                'episode_date' => '2020-08-15'
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
                'episode_date' => '2020-08-16'
            )
        );

        $this->scraper->shouldReceive('get_episodes')
                      ->with($show->show_id, $show->max_episodes)
                      ->andReturn(array(
                          array(
                              'episode_id' => 'we-talk-about-stuff'
                          ),
                          array(
                              'episode_id' => 'the-buzz'
                          )
                      ));

        $this->scraper->shouldReceive('scrape')
                      ->times(0);

        $this->http->shouldReceive('send_header')->times(1);

        update_option('podcast-scraper-feed-updated', $update_time);
        update_option('podcast-scraper-feed-content', 'asdf');

        $feed_handler = new ShowFeedHandler($this->http, $show, $this->scraper);
        ob_start();
        $feed_handler->render_feed();
        $output = ob_get_clean();

        $this->assertEquals('asdf', $output);

    }

    public function test_render_feed_delete_obsolete_items() {

        $update_time = time() - 60;

        $this->wpdb->update(
            $this->wpdb->prefix . "podcast_scraper_shows",
            array(
                'update_time' => $update_time
            ),
            array(
                'id' => $this->show_id
            )
        );

        $show = $this->wpdb->get_row(
            $this->wpdb->prepare(
                "SELECT * FROM " . $this->wpdb->prefix . "podcast_scraper_shows " .
                "WHERE id = %d",
                $this->show_id
            )
        );

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
                'episode_date' => '2020-08-15'
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
                'episode_date' => '2020-08-16'
            )
        );

        $this->scraper->shouldReceive('get_episodes')
                      ->with($show->show_id, $show->max_episodes)
                      ->andReturn(array(
                          array(
                              'episode_id' => 'we-talk-about-stuff'
                          )
                      ));

        $this->scraper->shouldReceive('scrape')
                      ->with($show->show_id, $show->max_episodes)
                      ->andReturn(
                          array(
                              'show' => array(
                                  'show_title' => 'The talk show',
                                  'show_description' => 'Lorem ipsum dolor sit amet...',
                                  'show_image' => '/path/to/image.jpg'
                              ),
                              'episodes' => array(
                                  array(
                                      'episode_id' => 'we-talk-about-stuff',
                                      'episode_title' => 'We talk about stuff',
                                      'episode_description' => 'Foo bar baz qux...',
                                      'episode_image' => '/path/to/image.jpg',
                                      'episode_file' => '/path/to/audio.mp3',
                                      'episode_date' => '2020-08-15',
                                      'episode_file_size' => 60000000,
                                      'episode_file_type' => 'audio/mp3'
                                  )
                              )
                      ));

        $this->http->shouldReceive('send_header')->times(1);

        update_option('podcast-scraper-feed-updated', $update_time);

        $feed_handler = new ShowFeedHandler($this->http, $show, $this->scraper);
        ob_start();
        $feed_handler->render_feed();
        $output = ob_get_clean();

        $this->assertOutputContains('<title>The Talk Show</title>', $output);
        $this->assertOutputContains(
            sprintf(
                '<link>http://example.org/feed/podcasts/%d-the-talk-show</link>',
                $this->show_id
            ),
            $output
        );
        $this->assertOutputContains('<url>/path/to/image.jpg</url>', $output);

        $this->assertOutputNotContains('<title>The buzz</title>', $output);
        $this->assertOutputNotContains(
            '<pubDate>Sun, 16 Aug 2020 00:00:00 +0000</pubDate>',
            $output
        );
        $this->assertOutputNotContains(
            '<enclosure length="50000000" type="audio/mp3" url="/path/to/buzz.mp3"></enclosure>',
            $output
        );

        $this->assertOutputContains('<title>We talk about stuff</title>', $output);
        $this->assertOutputContains(
            '<pubDate>Sat, 15 Aug 2020 00:00:00 +0000</pubDate>',
            $output
        );
        $this->assertOutputContains(
            '<enclosure length="60000000" type="audio/mp3" url="/path/to/audio.mp3"></enclosure>',
            $output
        );

        $show = $this->wpdb->get_row(
            $this->wpdb->prepare(
                "SELECT * FROM " . $this->wpdb->prefix . "podcast_scraper_shows " .
                "WHERE id = %d",
                $this->show_id
            )
        );
        $actual = $show->update_time;
        $this->assertNotEquals($update_time, $actual);

        $actual = $this->wpdb->get_var(
            $this->wpdb->prepare(
                "SELECT COUNT(id) FROM " . $this->wpdb->prefix . "podcast_scraper_episodes " .
                "WHERE show_id = %d",
                $this->show_id
            )
        );
        $expected = 1;
        $this->assertEquals($expected, $actual);

    }

    public function test_render_feed_with_error() {

        $show = $this->wpdb->get_row(
            $this->wpdb->prepare(
                "SELECT * FROM " . $this->wpdb->prefix . "podcast_scraper_shows " .
                "WHERE id = %d",
                $this->show_id
            )
        );

        $this->scraper->shouldReceive('get_episodes')
                      ->with($show->show_id, $show->max_episodes)
                      ->andReturn(array(
                          array(
                              'episode_id' => 'we-talk-about-stuff'
                          ),
                          array(
                              'episode_id' => 'the-buzz'
                          )
                      ));

        $this->scraper->shouldReceive('scrape')
                      ->with($show->show_id, $show->max_episodes)
                      ->andReturn(
                          array(
                              'show' => array(
                                  'show_title' => null,
                                  'show_description' => 'Lorem ipsum dolor sit amet...',
                                  'show_image' => '/path/to/image.jpg'
                              ),
                              'episodes' => array(
                                  array(
                                      'episode_id' => 'we-talk-about-stuff',
                                      'episode_title' => 'We talk about stuff',
                                      'episode_description' => 'Foo bar baz qux...',
                                      'episode_image' => '/path/to/image.jpg',
                                      'episode_file' => '/path/to/audio.mp3',
                                      'episode_date' => '2020-08-15',
                                      'episode_file_size' => 60000000,
                                      'episode_file_type' => 'audio/mp3'
                                  ),
                                  array(
                                      'episode_id' => 'the-buzz',
                                      'episode_title' => 'The buzz',
                                      'episode_description' => 'Buzz...',
                                      'episode_image' => '/path/to/image.jpg',
                                      'episode_file' => '/path/to/buzz.mp3',
                                      'episode_date' => '2020-08-16',
                                      'episode_file_size' => 50000000,
                                      'episode_file_type' => 'audio/mp3'
                                  )
                              )
                      ));

        $this->http->shouldReceive('send_header')->times(1);

        $feed_handler = new ShowFeedHandler($this->http, $show, $this->scraper);
        ob_start();
        $feed_handler->render_feed();
        $output = ob_get_clean();

        $this->assertEquals('', $output);

    }

    function assertOutputContains($value, $output) {

        $this->assertTrue(false !== strpos($output, $value));

    }

    function assertOutputNotContains($value, $output) {

        $this->assertTrue(false === strpos($output, $value));

    }

}
