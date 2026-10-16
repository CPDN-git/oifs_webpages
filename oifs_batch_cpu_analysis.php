<?php
// Force PHP to show errors
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// =========================================================================
// 1. SERVER-SIDE PATHS (PHP) - Always use explicit absolute paths
// =========================================================================
define('PROJECT_PATH', realpath(__DIR__ . '/../../..') . '/');   // i.e. /storage/www/cpdnboinc
define('CPDN_LOCAL',   __DIR__ );
define('BOINC_INC',    PROJECT_PATH . 'html/inc/');
define('LOCAL_INC',    __DIR__ . '/include/');

// =========================================================================
// 2. CLIENT-SIDE PATHS (HTML) - *MUST* always start with "/" to target the domain root
//    e.g. src="/img/mylogo.png"  and not  src="../img/mylogo.png"
//    Where "/img/mylogo.png"   means  main.cpdn.org/img/mylogo.png
// =========================================================================


//  Now switch to boinc's html root so all the includes load correctly
chdir(CPDN_LOCAL . '/..');

require_once(BOINC_INC . 'util.inc');
require_once(BOINC_INC . 'user.inc');
require_once(BOINC_INC . 'oifs_uploaders.inc');
require_once(BOINC_INC . 'batch_site_config.inc');

set_include_path(
    get_include_path() .
    PATH_SEPARATOR . BOINC_INC .
    PATH_SEPARATOR . CPDN_LOCAL
);

# include the table tag generatora

$batchid=get_int('batchid');
$user = get_logged_in_user();
if (in_array($user->email_addr,$allowed_uploaders)){


$xml=simplexml_load_file(PROJECT_PATH . 'ancil_batch_user_config.xml') or die("Error: Cannot create object");

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
