<?php
/**
 * Plugin Name:     Podcast Scraper
 * Plugin URI:      https://github.com/bboogaard/podcast-scraper/
 * Description:     Scrape podcast sites
 * Author:          Bram Boogaard
 * Author URI:      https://www.wp-wikkel.nl/
 * Text Domain:     podcast-scraper
 * Domain Path:     /languages
 * Version:         1.0.0
 *
 * @package         Podcast Scraper
 */

// Your code starts here.
define('PODCAST_SCRAPER_PATH', __FILE__);

require('vendor/autoload.php');

if (!class_exists('DOMEvent')) {
    require('ext/phpQuery.php');
}

require('wp/wp_remote.php');
require('includes/functions.php');
require('includes/feed-generator.php');
require('includes/lib/cache.php');
require('includes/lib/http.php');
require('includes/scrapers/base.php');
require('includes/scrapers/podcastluisteren.php');
require('includes/shows.db.php');
require('includes/shows.feed.php');
require('includes/shows.manage.php');
require('includes/shows.table.php');

function podcast_scraper_run() {

    PodcastScraper\ShowDbManager::migrate();
    PodcastScraper\ShowManagerFactory::register();
    PodcastScraper\ShowFeed::register();

}

podcast_scraper_run();
