<?php

//  Pass arguments from command line like this
//  php stats.php domain=anchor.host

parse_str(implode('&', array_slice($argv, 1)), $_GET);

$domain = $_GET['domain'];


// Connects to WordPress.com and pulls blog ids from anchorhost username
$access_key = '***REMOVED***';


if ($domain) {
	
	// Define vars
	$count = 0;
	$total = 0;
	$months = "";

	// Pull stats from WordPress API
	$curl = curl_init( 'https://public-api.***REMOVED***.com/rest/v1/sites/'.$domain.'/stats/visits?unit=month&quantity=12' );
	curl_setopt( $curl, CURLOPT_HTTPHEADER, array( 'Authorization: Bearer ' . $access_key ) );
	curl_setopt( $curl, CURLOPT_RETURNTRANSFER, 1);
	$response = curl_exec( $curl );
	$stats = json_decode($response, true);

	if (isset($stats["error"]) and $stats["error"] == "unknown_blog") {
		// Attempt to load www version

		// Pull stats from WordPress API
		$curl = curl_init( 'https://public-api.***REMOVED***.com/rest/v1/sites/www.'.$domain.'/stats/visits?unit=month&quantity=12' );
		curl_setopt( $curl, CURLOPT_HTTPHEADER, array( 'Authorization: Bearer ' . $access_key ) );
		curl_setopt( $curl, CURLOPT_RETURNTRANSFER, 1);
		$response = curl_exec( $curl );
		$stats = json_decode($response, true);

	}

	if (isset($stats["data"])) {
	
		// Preps views for last 12 months for html output while calculating usage. 
		foreach ($stats["data"] as $stat) {
			if ($stat[0]) {
				$total = $total + 1;
				$months .= $stat[0] . " - " . $stat[1] . "<br />";
			}
			$count = $count + $stat[1];
		}

		if ($total == 0) {
			$monthly_average = 0;
		} else {
			$monthly_average = round($count / $total);
		}

		$yearly_estimate = $monthly_average * 12;
		echo $yearly_estimate;

	} else {
		// Error so return 0. For debug info see print_r($stats);
		echo "0";
	}

}

?>