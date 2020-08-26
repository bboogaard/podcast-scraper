<?php

class PodcastScraperTestCase extends WP_UnitTestCase {

    function assertOutputContains($value, $output) {

        $this->assertTrue(false !== strpos($output, $value));

    }

    function assertOutputNotContains($value, $output) {

        $this->assertTrue(false === strpos($output, $value));

    }

}
