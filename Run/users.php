<?php
##
##		Generates a self destructing WordPress mu-plugin which will preload WordPress users
##
## 		Pass arguments from command line like this
##		php users.php install=anchorhosting
## 
##		assign command line arguments to varibles 
## 		userlist=~/Documents/Scripts/userlist.json becomes $_GET['userlist']
##

if (isset($argv)) {
	parse_str(implode('&', array_slice($argv, 1)), $_GET);
}

$install = $_GET['install'];
$token = $_GET['token'];
$customers = explode(",", $_GET['customers'] );
$userlist = $_SERVER['HOME'] . "/Tmp/userlist-$install.json";
$json = json_decode(file_get_contents($userlist), true);
$generated_plugin = $_SERVER['HOME'] ."/Tmp/anchor_load_$install.php";

## mu-plugin generate: beginning of file
$content = <<<EOT
<?php

### Patch fix email notifications in WordPress 4.6
add_filter( 'wp_mail_from', function( \$email ) {
    \$site = get_site_url();
    \$parse = parse_url(\$site);
    \$email = "***REMOVED***@". \$parse['host'];
    return \$email;
});

function add_admin_acct(){

    ### Required in order to use wp_delete_user function
    require_once(ABSPATH.'wp-admin/includes/user.php' );

    global \$wpdb;
    \$tablename = \$wpdb->prefix . "users";

    ### Check for default WP Engine site name and description
    if (get_option( "blogname" ) == "Austin Ginder Blog") {
        ## Update default site title
        update_option( "blogname", "$install.wpengine.com" );
    }

    if (get_option( "blogdescription" ) == "Your SUPER-powered WP Engine Blog") {
        ## Update default site description
        update_option( "blogdescription", "" );
    }

    if (get_option( "timezone_string" ) == "") {
        ## Update default time zone
        update_option( "timezone_string", "America/New_York" );
    }

    ### Generating primary admin account

    if ( !username_exists( "anchorhost" ) && !email_exists( "support@anchor.host" ) ) {

        \$userdata = array(
            'user_login'    =>  'anchorhost',
            'user_email'    =>  'support@anchor.host',
            'display_name'  =>  'anchorhost',
            'first_name'    =>  'Anchor', 
            'last_name'     =>  'Hosting', 
            'user_nicename' =>  'anchorhost',
            'nickname'      =>  'anchorhost',
            'role'          =>  'administrator'
        );
        \$user_id = wp_insert_user( \$userdata );

    }

    ### Sending email for primary admin account

    if (\$user_id) {
        wp_new_user_notification( \$user_id, null, 'user' );

        ### Check for default WP Engine account
        \$user = get_user_by( 'login', '$install' );

        ### If found remove and reassign pages/post to new admin
        if(\$user) {
            wp_delete_user( \$user->ID, \$user_id );
        }
    }

    \$user_id = null;


EOT;


## mu-plugin: update admin email if needed

if ($customers and $customers[0]) {
    ## Fetch preloded add email
    $customer = $customers[0];
    $curl = curl_init( "https://anchor.host/wp-json/wp/v2/customer/$customer/?token=$token" );
    curl_setopt( $curl, CURLOPT_RETURNTRANSFER, 1);
    $response = curl_exec( $curl );
    $json_results_website = json_decode($response, true);
    $preloaded_email = $json_results_website["preloaded_email"];

    if ( $preloaded_email ) {

        $content .= <<<EOT

    ### Updating admin email address
    update_option( "admin_email", "$preloaded_email" );

EOT;

    }
}


$i = 1;

## mu-plugin generate: loops through user accounts
foreach ($json as $name => $install) {
   	$user_login = $name; 
   	$user_email = $install["email"];
   	$user_firstname = $install["firstname"];
   	$user_lastname = $install["lastname"];
   	$user_role = $install["role"];
   	$user_display = trim($user_firstname . " " . $user_lastname);
    $content .= <<<EOT

    ### Generating account #$i

    if ( !username_exists( "$user_login" ) && !email_exists( "$user_email" ) ) {

        \$userdata = array(
            'user_login'    =>  '$user_login',
            'user_email'    =>  '$user_email',
            'display_name'  =>  '$user_display',
            'first_name'    =>  '$user_firstname', 
            'last_name'     =>  '$user_lastname', 
            'user_nicename' =>  '$user_display',
            'nickname'      =>  '$user_display',
            'role'          =>  '$user_role'
        );
        \$user_id = wp_insert_user( \$userdata );

    }

    ### Sending email for account #$i

    if (\$user_id) {
        wp_new_user_notification( \$user_id, null, 'user' );
    }

    \$user_id = null;

EOT;
$i++; 
}

## mu-plugin generate: end of file
$content .= <<<EOT

	### Self destruct - Will only fill up the error_log on WP Engine :(
 	### unlink(__FILE__); 
}
add_action('init','add_admin_acct');

?>
EOT;

$output = file_put_contents($generated_plugin, $content);

?>