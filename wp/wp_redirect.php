<?php

namespace WP;

class WP_Redirect {

    public function redirect($location, $status=302, $x_redirect_by='WordPress') {

        wp_redirect($location, $status, $x_redirect_by);
        exit;

    }

}
