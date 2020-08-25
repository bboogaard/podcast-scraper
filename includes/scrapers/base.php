<?php

namespace PodcastScraper;

use \phpQuery;
use WP\WP_Remote;

abstract class BaseScraper {

    protected $base_url = '';

    protected $wp_remote;

    public function __construct(WP_Remote $wp_remote) {

        $this->wp_remote = $wp_remote;

    }

    public function scrape($show_id, $max_episodes=0, $num_episodes=10,
                           $episode_offset=0) {

        $result = array(
            'show' => $this->get_show($show_id),
            'episodes' => array()
        );

        $episodes = array_slice(
            $this->get_episodes($show_id, $max_episodes),
            $episode_offset,
            $num_episodes
        );

        foreach ($episodes as $episode) {
            array_push(
                $result['episodes'],
                $this->get_episode($episode['episode_id'])
            );
        }

        return $result;

    }

    public function get_episodes($show_id, $max_episodes=0) {

        $response = $this->wp_remote->get($this->get_episodes_url($show_id));
        $html = wp_remote_retrieve_body($response);
        if (!$html) {
            return false;
        }

        $document = phpQuery::newDocument($html);

        if (!$max_episodes) {
            return $this->extract_episodes($document);
        }

        return array_slice(array_map(
            function($episode) {
                return wp_parse_args(
                    $episode,
                    array(
                        'episode_id' => ''
                    )
                );
            },
            $this->extract_episodes($document)
        ), 0, $max_episodes);

    }

    protected function get_show($show_id) {

        $response = $this->wp_remote->get($this->get_show_url($show_id));
        $html = wp_remote_retrieve_body($response);
        if (!$html) {
            return false;
        }

        $document = phpQuery::newDocument($html);

        return wp_parse_args(
            $this->extract_show($document),
            array(
                'show_title' => '',
                'show_description' => '',
                'show_image' => ''
            )
        );

    }

    protected function get_episode($episode_id) {

        $response = $this->wp_remote->get($this->get_episode_url($episode_id));
        $html = wp_remote_retrieve_body($response);
        if (!$html) {
            return false;
        }

        $document = phpQuery::newDocument($html);

        return wp_parse_args(
            $this->extract_episode($document),
            array(
                'episode_id' => $episode_id,
                'episode_title' => '',
                'episode_description' => '',
                'episode_image' => '',
                'episode_file' => '',
                'episode_date' => null
            )
        );

    }

    abstract protected function get_show_url($show_id);

    abstract protected function get_episodes_url($show_id);

    abstract protected function get_episode_url($episode_id);

    abstract protected function extract_show($document);

    abstract protected function extract_episodes($document);

    abstract protected function extract_episode($document);

}
