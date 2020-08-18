<?php

namespace PodcastScraper\Lib;

/*
 * File name: cache.php
 *
 * A simple no expiry cache base on wp_options;
 *
 */

class Cache {

    private $prefix;

    public function __construct($prefix) {

        $this->prefix = $prefix;

    }

    public function get($key, $default=false) {

        return get_option($this->make_key($key), $default);

    }

    public function set($key, $value) {

        update_option($this->make_key($key), $value);

    }

    public function delete($key) {

        delete_option($this->make_key($key));

    }

    private function make_key($key) {

        return $this->prefix . $key;

    }

}
