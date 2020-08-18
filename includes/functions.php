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
