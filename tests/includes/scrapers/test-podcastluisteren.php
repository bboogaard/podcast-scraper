<?php

use PodcastScraper\PodcastLuisterenScraper;

/**
 * Class TestPodcastLuisterenScraper
 *
 * @package PodcastScraper
 */

/**
 * Tests for the PodcastLuisterenScraper class
 */
class TestPodcastLuisterenScraper extends WP_UnitTestCase {

    function setUp() {

        parent::setUp();

        $this->support_dir = path_join(dirname(__FILE__), 'support');

        $this->wp_remote = Mockery::mock('WP\WP_Remote');
        $this->scraper = new PodcastLuisterenScraper($this->wp_remote);

    }

    function tearDown() {

        parent::tearDown();

        Mockery::close();

    }

    public function test_scrape() {

        $show_html = file_get_contents(path_join($this->support_dir, 'show.html'));

        $this->wp_remote->shouldReceive('get')
                        ->with('https://podcastluisteren.nl/pod/the-talk-show')
                        ->andReturn(array(
                            'body' => $show_html
                        ));

        $episode_html = file_get_contents(path_join($this->support_dir, 'episode_1.html'));

        $this->wp_remote->shouldReceive('get')
                        ->with('https://podcastluisteren.nl/ep/we-talk-about-stuff')
                        ->andReturn(array(
                            'body' => $episode_html
                        ));

        $this->wp_remote->shouldReceive('head')
                        ->with('/path/to/audio.mp3')
                        ->andReturn(array(
                            'response' => array(
                                'code' => 302
                            ),
                            'headers' => array(
                                'Location' => '/path/to/real-audio.mp3'
                            )
                        ));

        $this->wp_remote->shouldReceive('head')
                        ->with('/path/to/real-audio.mp3')
                        ->andReturn(array(
                            'response' => array(
                                'code' => 200
                            ),
                            'headers' => array(
                                'Content-length' => 60000000,
                                'Content-type' => 'audio/mp3'
                            )
                        ));

        $episode_html = file_get_contents(path_join($this->support_dir, 'episode_2.html'));

        $this->wp_remote->shouldReceive('get')
                        ->with('https://podcastluisteren.nl/ep/the-buzz')
                        ->andReturn(array(
                            'body' => $episode_html
                        ));

        $this->wp_remote->shouldReceive('head')
                        ->with('/path/to/buzz.mp3')
                        ->andReturn(array(
                            'response' => array(
                                'code' => 302
                            ),
                            'headers' => array(
                                'Location' => '/path/to/real-buzz.mp3'
                            )
                        ));

        $this->wp_remote->shouldReceive('head')
                        ->with('/path/to/real-buzz.mp3')
                        ->andReturn(array(
                            'response' => array(
                                'code' => 200
                            ),
                            'headers' => array(
                                'Content-length' => 50000000,
                                'Content-type' => 'audio/mp3'
                            )
                        ));

        $actual = $this->scraper->scrape('the-talk-show');
        $expected = array(
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
        );
        $this->assertEquals($expected, $actual);

    }

    public function test_scrape_max_episodes() {

        $show_html = file_get_contents(path_join($this->support_dir, 'show.html'));

        $this->wp_remote->shouldReceive('get')
                        ->with('https://podcastluisteren.nl/pod/the-talk-show')
                        ->andReturn(array(
                            'body' => $show_html
                        ));

        $episode_html = file_get_contents(path_join($this->support_dir, 'episode_1.html'));

        $this->wp_remote->shouldReceive('get')
                        ->with('https://podcastluisteren.nl/ep/we-talk-about-stuff')
                        ->andReturn(array(
                            'body' => $episode_html
                        ));

        $this->wp_remote->shouldReceive('head')
                        ->with('/path/to/audio.mp3')
                        ->andReturn(array(
                            'response' => array(
                                'code' => 302
                            ),
                            'headers' => array(
                                'Location' => '/path/to/real-audio.mp3'
                            )
                        ));

        $this->wp_remote->shouldReceive('head')
                        ->with('/path/to/real-audio.mp3')
                        ->andReturn(array(
                            'response' => array(
                                'code' => 200
                            ),
                            'headers' => array(
                                'Content-length' => 60000000,
                                'Content-type' => 'audio/mp3'
                            )
                        ));

        $episode_html = file_get_contents(path_join($this->support_dir, 'episode_2.html'));

        $this->wp_remote->shouldReceive('get')
                        ->with('https://podcastluisteren.nl/ep/the-buzz')
                        ->times(0);

        $this->wp_remote->shouldReceive('head')
                        ->with('/path/to/buzz.mp3')
                        ->times(0);

        $this->wp_remote->shouldReceive('head')
                        ->with('/path/to/real-buzz.mp3')
                        ->times(0);

        $actual = $this->scraper->scrape('the-talk-show', 1);
        $expected = array(
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
        );
        $this->assertEquals($expected, $actual);

    }

}
