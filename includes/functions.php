<?php

use PodcastScraper\PodcastLuisterenScraper;

function podcast_scraper_get_scraper_class($scraper_handle) {

    $scraper_map = array(
        'podcastluisteren' => PodcastLuisterenScraper::class
    );

    return isset($scraper_map[$scraper_handle]) ? $scraper_map[$scraper_handle] : null;

}

function podcast_scraper_get_scrapers() {

    return array(
        'podcastluisteren' => 'Podcast Luisteren'
    );

}

function podcast_scraper_wpdb_placeholder($param) {

    switch (gettype($param)) {
        case 'integer':
            $placeholder = '%d';
            break;
        case "double":
            $placeholder = '%f';
            break;
        default:
            $placeholder = '%s';
            break;
    }

    return $placeholder;

}

function podcast_scraper_wpdb_in_format($sql, $in_params) {

    global $wpdb;

    $num_params = count($in_params);
    $placeholder = podcast_scraper_wpdb_placeholder($in_params[0]);

    $placeholders = array_fill(0, $num_params, $placeholder);
    $format = implode(', ', $placeholders);

    return preg_replace('/IN(?:[\s|\n]+)%s/', sprintf('IN (%s)', $format), $sql);

}

function podcast_scraper_wpdb_bulk_insert($table, $rows) {

    global $wpdb;

	// Extract column list from first row of data
	$columns = array_keys($rows[0]);
	asort($columns);
	$columnList = '`' . implode('`, `', $columns) . '`';

	// Start building SQL, initialise data and placeholder arrays
	$sql = "INSERT INTO `$table` ($columnList) VALUES\n";
	$placeholders = array();
	$data = array();

	// Build placeholders for each row, and add values to data array
	foreach ($rows as $row) {
		ksort($row);
		$rowPlaceholders = array();

		foreach ($row as $key => $value) {
			$data[] = $value;
			$rowPlaceholders[] = is_numeric($value) ? '%d' : '%s';
		}

		$placeholders[] = '(' . implode(', ', $rowPlaceholders) . ')';
	}

	// Stitch all rows together
	$sql .= implode(",\n", $placeholders);

	// Run the query.  Returns number of affected rows.
	return $wpdb->query($wpdb->prepare($sql, $data));

}

function podcast_scraper_url($path) {

    return plugins_url($path, PODCAST_SCRAPER_PATH . '/podcast-scraper.php');

}
