<?php
##
##		Output array of domains from the logins.sh
##
## 		Pass arguments from command line like this
##		php Scripts/Get/domains.php
##

parse_str(implode('&', array_slice($argv, 1)), $_GET);

if ($_GET && $_GET['file']) {
	$file = $_GET['file'];
} else {
	$file =  $_SERVER["HOME"] . "/Scripts/logins.sh";
}

if (file_exists($file)) {
$file = file_get_contents($file);
	// Matches the format: installname)\n\n\t   See: http://regexr.com/3de13
	$pattern = '/domain=(.+)/';
	preg_match_all($pattern, $file, $matches);

	// print_r($matches);

	$space_separated = implode(" ", $matches[1]);

	echo $space_separated;
}