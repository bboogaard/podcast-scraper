<?php

namespace PodcastScraper;

use \DateTime;
use \phpQuery;

class PodcastLuisterenScraper extends BaseScraper {

    protected $base_url = 'https://podcastluisteren.nl';

    protected function get_show_url($show_id) {

        return sprintf('%s/pod/%s', $this->base_url, $show_id);

    }

    protected function get_episodes_url($show_id) {

        return sprintf('%s/pod/%s', $this->base_url, $show_id);

    }

    protected function get_episode_url($episode_id) {

        return sprintf('%s/ep/%s', $this->base_url, $episode_id);

    }

    protected function extract_show($document) {

        $show_title = pq($document->find('#content-container h1'))->html();
        $show_description = pq($document->find('#content-container p'))->html();
        $show_image = pq($document->find('#content-container img'))->attr('src');

        return array(
            'show_title' => $show_title,
            'show_description' => $show_description,
            'show_image' => $show_image
        );

    }

    protected function extract_episodes($document) {

        $links = $document->find('#episodes-container ul li a');
        $result = array();
        foreach ($links as $link) {
            $link = pq($link)->attr('href');
            if (preg_match('/\/ep\/([^\/]+)$/', $link, $matches)) {
                array_push(
                    $result,
                    array(
                        'episode_id' => $matches[1]
                    )
                );
            }
        }
        return $result;

    }

    protected function extract_episode($document) {

        $info = pq($document->find('#content-container'));

        $episode_title = pq($info->find('h1'))->html();
        $episode_description = pq($info->find('p'))->html();
        $episode_image = pq($info->find('img'))->attr('src');
        $episode_file = pq($info->find('audio'))->attr('src');
        $raw_date = pq($info->find('h4'))->text();

        $file_info = wp_parse_args(
            $this->get_file_info($episode_file),
            array(
                'episode_file_size' => -1,
                'episode_file_type' => 'audio/mpeg'
            )
        );

        $month_names = 'Jan|Feb|Mar|Apr|May|Jun|Jul|Aug|Sep|Oct|Nov|Dec';
        $date_pattern = sprintf(
            '/(%s)(?:\s+)(\d{1,2}),(?:\s+)(\d{4})$/', $month_names
        );
        if (preg_match($date_pattern, $raw_date, $matches)) {
            $episode_date = DateTime::createFromFormat(
                'M d, Y',
                sprintf('%s %s, %s', $matches[1], $matches[2], $matches[3])
            );
            $episode_date = $episode_date->format('Y-m-d');
        }
        else {
            $episode_date = null;
        }

        return array(
            'episode_title' => $episode_title,
            'episode_description' => $episode_description,
            'episode_image' => $episode_image,
            'episode_date' => $episode_date,
            'episode_file' => $episode_file,
            'episode_file_size' => $file_info['episode_file_size'],
            'episode_file_type' => $file_info['episode_file_type']
        );

    }

    private function get_file_info($episode_file) {

        $response = $this->wp_remote->head($episode_file);
        if (!is_wp_error($response)) {
            $code = wp_remote_retrieve_response_code($response);
            if ($code == 302) {
                $headers = wp_remote_retrieve_headers($response);
                $response = $this->wp_remote->head($headers['Location']);
                $code = wp_remote_retrieve_response_code($response);
                if ($code == 200) {
                    $headers = wp_remote_retrieve_headers($response);
                    return array(
                        'episode_file_size' => $headers['Content-length'],
                        'episode_file_type' => $headers['Content-type']
                    );
                }
            }
        }

        return array();

    }

}
