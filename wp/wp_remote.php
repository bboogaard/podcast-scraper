<?php

namespace WP;

class WP_Remote {

    public function get($url, $args=array()) {

        return wp_remote_get($url, $args);

    }

    public function head($url, $args=null) {

        return wp_remote_head($url, $args);

    }

}
