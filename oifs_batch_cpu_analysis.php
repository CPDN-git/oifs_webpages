<?php
// Load the host-specific configuration before defining paths from its variables.
// Its legacy dev-site detection expects the working directory to be html/user.
if (!chdir(__DIR__ . '/..')) {
    http_response_code(500);
    exit('Unable to select the BOINC web directory.');
}
$config_file = __DIR__ . '/../../inc/batch_site_config.inc';
if (!is_readable($config_file)) {
    http_response_code(500);
    exit('Batch site configuration is unavailable.');
}
require_once($config_file);


// =========================================================================
// 1. SERVER-SIDE PATHS (PHP) - Derived from the host configuration
// =========================================================================
define('CPDN_LOCAL', project_path . 'html/user/cpdn');  // project_path comes from batch_site_config.inc
define('BOINC_INC', project_path . 'html/inc/');
define('LOCAL_INC', CPDN_LOCAL . '/include/');

// =========================================================================
// 2. CLIENT-SIDE PATHS (HTML) - Start with "/" or a configured absolute URL
// =========================================================================

// Set search paths before loading BOINC and its nested includes.
set_include_path(
    get_include_path() .
    PATH_SEPARATOR . BOINC_INC .
    PATH_SEPARATOR . CPDN_LOCAL
);

require_once(BOINC_INC . 'util.inc');
require_once(BOINC_INC . 'user.inc');
require_once(project_path . 'html/project/oifs_uploaders.inc');

$batchid=get_int('batchid');
$user = get_logged_in_user();
if (in_array($user->email_addr,$allowed_uploaders)){


$xml=simplexml_load_file(project_path . 'ancil_batch_user_config.xml') or die("Error: Cannot create object");

$host= $xml->db_host;
$dbname=$xml->db_name;
$boinc_dbname=$xml->boinc_db_name;
$user= $xml->batch_user;
$pass= $xml->batch_passwd;

$r = escapeshellcmd( $python_env.' '.$script_path.'batch_cpu_diagnosis.py '.$project.' '.$batchid);
$output = shell_exec($r);

$r2 = escapeshellcmd( $python_env.' '.$script_path.'batch_run_time.py '.$batchid);
$output2 = shell_exec($r2);
$img_name= "batch_".$batch_prefix.$batchid."_cpu_times.png";
$img_name2= "Batch_".$batch_prefix.$batchid."_timings.png";

echo <<<HTML
<!DOCTYPE html>
<html>
<head>
<title>CPDN Statistics</title>
<meta name="robots" content="noindex, nofollow">
<link rel="stylesheet" href="/cpdn.css">
<style>
img.one {
    height: auto;
    width: auto;
}

img.two {
    width: 70%;
}
</style>
</head>
<body>
HTML;
echo '<div class="wrap" style="width:100%">';
echo '<div style="width:100%">';
echo '<img src="/img/OIFS_Home_logo.png" alt="OpenIFS@home" style="width:200px">';
echo '<img src="/img/CPDN_abbrv_logo.png" alt="CPDN" style="width:250px; float:right;">';
echo '</div>';
echo '<div style="clear: both;"></div>';
echo '</div>';
echo '<hr>';
echo '<h1>CPU time taken on batch '.$batch_prefix.$batchid.'</h1>';
$img_path=$host_url_path.'/readimage.php?path=tmp_batch/'.$img_name;
echo '<img class="two" src="'.$img_path.'" alt="Batch CPU times">';
echo '<hr>';
echo '<h1>Run times for batch '.$batch_prefix.$batchid.'</h1>';
$img_path2=$host_url_path.'/readimage.php?path=tmp_batch/'.$img_name2;
echo '<img class="two" src="'.$img_path2.'" alt="Batch Timings">';
echo '<hr>';
echo '</body>';
echo '</html>';

} 
else{
 echo "This page has restricted access.  To access this page contact OeRC staff.";
        }
?>
