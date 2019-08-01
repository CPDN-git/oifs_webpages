<html>
<style>
img.one {
    height: auto;
    width: auto;
}

img.two {
    width: 70%;
}
</style>

<?php 


# include the table tag generatora
require_once("../inc/util.inc");
require_once("../inc/user.inc");
require_once("../inc/oifs_uploaders.inc");
require_once("../inc/batch_site_config.inc");

$batchid=get_int('batchid');
$user = get_logged_in_user();
if (in_array($user->email_addr,$allowed_uploaders)){


set_include_path(get_include_path() . PATH_SEPARATOR . $project_path."html/inc");
$xml=simplexml_load_file($project_path."ancil_batch_user_config.xml") or die("Error: Cannot create object");

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

echo '<head>';
echo '<title>CPDN Statistics</title>';
echo '<META NAME="ROBOTS" CONTENT="NOINDEV, NOFOLLOW">';
echo '<link rel="stylesheet" href="cpdn.css">';
echo '</head>';
echo '<div class="wrap" style="width:100%">';
echo '<div style="width:100%">';
echo '<img src="img/OIFS_Home_logo.png" alt="OpenIFS@home" style="width:200px">';
echo '<img src="img/CPDN_abbrv_logo.png" alt="CPDN" style="width:250px; float:right;">';
echo '</div>';
echo '<div style="clear: both;"></div>';
echo '</div>';
echo '<body>';
echo '<hr>';
echo '<h1>CPU time taken on batch '.$batch_prefix.$batchid.'</h2>';
$img_path=$host_url_path.'/readimage.php?path=tmp_batch/'.$img_name;
echo '<img class="two" src="'.$img_path.'" alt="Batch CPU times">';
echo '<hr>';
echo '<h1>Run times for batch '.$batch_prefix.$batchid.'</h2>';
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
