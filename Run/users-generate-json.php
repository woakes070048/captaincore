<?php
##
##		Generates a json file based on an API call to anchor.host containing list of users to preload
##
## 		Pass arguments from command line like this
##		php users-generate-json.php token=random customers=1294,1245 website=anchorhost
## 
##		assign command line arguments to varibles 
## 		userlist=~/Documents/Scripts/userlist.csv becomes $_GET['userlist']
##

if (isset($argv)) {
    parse_str(implode('&', array_slice($argv, 1)), $_GET);
}

$website = $_GET['website'];
$token = $_GET['token'];
$customers = explode(",", $_GET['customers'] );

$preloaded_users = array();

foreach ($customers as $customer) {

    $curl = curl_init( "https://anchor.host/wp-json/wp/v2/customer/$customer/?token=$token" );
    curl_setopt( $curl, CURLOPT_RETURNTRANSFER, 1);
    $response = curl_exec( $curl );
    $json_results_website = json_decode($response, true);
    $users = $json_results_website["preloaded_users"];

    foreach ($users as $user) {
        $preloaded_users[] = $user;
    }  
}

$content = "{";

foreach ($preloaded_users as $preloaded_user) {
	$username = $preloaded_user["username"];
	$email = $preloaded_user["email"];
	$firstname = $preloaded_user["first_name"];
	$lastname = $preloaded_user["last_name"];
	$role = $preloaded_user["role"];
    if(end($preloaded_users) !== $preloaded_user){
        $content .= <<<EOT

        "$username": {
            "email": "$email",
            "firstname": "$firstname",
            "lastname": "$lastname",
            "role": "$role"
        },

EOT;
    } else {
        $content .= <<<EOT

        "$username": {
            "email": "$email",
            "firstname": "$firstname",
            "lastname": "$lastname",
            "role": "$role"
        }

EOT;
    }

}

$content .= "}";

$output = file_put_contents($_SERVER['HOME'] . "/Tmp/userlist-$website.json", $content);
