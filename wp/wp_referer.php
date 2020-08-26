<?php

namespace WP;

class WP_Referer {

    public function check_admin_referer($action=-1, $query_arg='_wpnonce') {

        return check_admin_referer($action, $query_arg);

    }

}
