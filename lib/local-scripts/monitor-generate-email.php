<?php

$errors   = array();
$warnings = array();
$contents = file_get_contents( $args[0] );
$lines    = explode( "\n", $contents );

foreach ( $lines as $line ) {
	$json = json_decode( $line );

	// Check if JSON valid
	if ( json_last_error() !== JSON_ERROR_NONE ) {
		continue;
	}

	$http_code  = $json->http_code;
	$url        = $json->url;
	$html_valid = $json->html_valid;

	// Check if HTML is valid
	if ( $html_valid == 'false' ) {
		$errors[] = "Response code $http_code for $url html is invalid\n";
		continue;
	}

	// Check if healthy
	if ( $json->http_code == '200' ) {
		continue;
	}

	// Check for redirects
	if ( $json->http_code == '301' ) {
		$warnings[] = "Response code $http_code for $url\n";
		continue;
	}

	// Append error to errors for email purposes
	$errors[] = "Response code $http_code for $url\n";
}

// if errors then generate html
if ( count( $errors ) > 0 ) {

	$html = '<strong>Errors</strong><br /><br />';

	foreach ( $errors as $error ) {
		$html .= trim( $error ) . "<br />\n";
	};
	
	if ( count( $warnings ) > 0 ) {
		$html .= '<br /><strong>Warnings</strong><br /><br />';
	}

	foreach ( $warnings as $warning ) {
		$html .= trim( $warning ) . "<br />\n";
	};

	echo $html;

}
