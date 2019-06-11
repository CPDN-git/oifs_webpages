<?php
require_once("../inc/util.inc");
require_once("../inc/user.inc");
require_once("../inc/batch_site_config.inc");

// Report simple running errors
error_reporting(E_ERROR | E_WARNING | E_PARSE);

set_include_path(get_include_path() . PATH_SEPARATOR . $project_path."html/inc");
$xml=simplexml_load_file($project_path."ancil_batch_user_config.xml") or die("Error: Cannot create object");

$host= $xml->db_host;
$dbname=$xml->db_name;
$boinc_dbname=$xml->boinc_db_name;
$user= $xml->batch_user;
$pass= $xml->batch_passwd;

# include the table tag generatora
require_once('includes/html_table.class.php');

require_once("../inc/util.inc");

echo <<<EOH
<html>
<head>
<title>Batch Details</title>
<META NAME="ROBOTS" CONTENT="NOINDEV, NOFOLLOW">
<script type="text/javascript" src="jquery/jquery-latest.js"></script>
<script type="text/javascript" src="jquery/jquery.tablesorter.js"></script>
<script type="text/javascript">
                        $(document).ready(function() {
                                $("#myTable").tablesorter();
                        });
                </script>
<style type="text/css">
                        #sortedtable thead th {
                                color: #00f;
                                font-weight: bold;
                                text-decoration: underline;
                        }
                </style>
<link rel="stylesheet" href="cpdn.css">
</head>
<body>
EOH;

echo '<img src="img/logo.png">';
echo '<hr>';

$date="";
$batchid = get_int("batchid");

$fields="CONCAT('$batch_prefix',b.id) as Batch,";
$fields="$fields b.name as Name,";
$fields="$fields p.name as Project_Name,";
$fields="$fields b.description as Description,";
$fields="$fields b.number_of_workunits as Number_of_workunits,";
$fields="$fields $date(b.submit_time) as Submit_date,";
$fields="$fields app.name as App,";
$fields="$fields substring_index(b.owner,'<',1) as Owner,";
$fields="$fields b.first_start_year as First_start_year,";
$fields="$fields b.last_start_year as Last_start_year,";
$fields="$fields b.umid_start as UMID_start,";
$fields="$fields b.umid_end as UMID_end,";
$fields="$fields b.tech_info as Tech_info,";
$fields="$fields b.notify as Batch_emails_on,";
$fields="$fields b.ended as Batch_finalised,";
$fields="$fields FROM_UNIXTIME(b.closed_date) as Closed_date,";
$fields="$fields b.archive_status as Archive_status,";
$fields="$fields b.current_location as Current_location";
$fields_array=explode(', ',$fields);
echo "<h2>".$site." Site Batch Details</h2>";
echo "<br>";

$querystring="SELECT $fields FROM cpdn_batch b join $boinc_dbname.app on b.appid=app.id left join $dbname.cpdn_project p on b.projectid=p.id where b.id=$batchid";
#echo "$querystring <br>";

$link = mysqli_connect($host,$user,$pass,$dbname) or die("Error " . mysqli_error($link));
$result = $link->query($querystring) or die("Error " . mysqli_error($link));

$row=$result->fetch_row();
#var_dump($row);

foreach(array_keys($fields_array) as $key) {
	$inter=explode("as ",$fields_array[$key]);
	$title=end($inter);
	echo "<strong>".$title.": </strong>".$row[$key]."<br>";
}

$batch_tot=$row[4];
mysqli_free_result($result);

$fields="s.time as Time_stats_last_checked,";
$fields="$fields IFNULL(s.unsent,0) as Unsent,";
$fields="$fields IFNULL(s.in_progress,0) as Running,";
$fields="$fields IFNULL(s.success,0) as Completed,";
$fields="$fields IFNULL(s.hard_failures,0) as Hard_Failures,";
$fields="$fields IFNULL(s.client_error,0) as Total_Failures";

$fields_array=explode(', ',$fields);

$query_stats="SELECT $fields FROM cpdn_batch_stats s where s.batchid=$batchid order by s.time desc limit 1";


$result = $link->query($query_stats) or die("Error " . mysqli_error($link));
$row_stats=$result->fetch_row();
mysqli_free_result($result);


$pct_success=round($row_stats[3]/$batch_tot*100);
$pct_fail=round($row_stats[5]/$batch_tot*100);
$pct_h_fail=round($row_stats[4]/$batch_tot*100);
$pct_running=round($row_stats[2]/$batch_tot*100);
$pct_unsent=round($row_stats[1]/$batch_tot*100);

$nbsp="";
echo "<br><hr>";
echo "<h2>Batch Statistics</h2><br><strong>Last updated on:</strong> ".$row_stats[0]."<br>";
echo "<br><a href=".$host_url_path."/batch_completions.php?batchid=".$batchid."><font color=#00FF7F><strong>Success: </strong></font>$nbsp".$row_stats[3]." (".$pct_success."%)</a>";
echo "<br><font color=#FF8080><strong>Fails: </strong></font>$nbsp".$row_stats[5]." (".$pct_fail."%)";
echo "<br><a href=".$host_url_path."/batch_hard_fails.php?batchid=".$batchid."><font color=#FF0000><strong>Hard Fail: </strong></font>$nbsp".$row_stats[4]." (".$pct_h_fail."%)</a>";
echo "<br><a href=".$host_url_path."/batch_running.php?batchid=".$batchid."><font color=#FFA500><strong>Running: </strong></font>$nbsp".$row_stats[2]." (".$pct_running."%)</a>";
echo "<br><a href=".$host_url_path."/batch_unsent.php?batchid=".$batchid."><font color=#4169E1><strong>Unsent: </strong></font>$nbsp".$row_stats[1]." (".$pct_unsent."%)</a><br>";

echo "<br>";
$tmp_path=$base_path.'tmp_batch/';
$r = escapeshellcmd( $python_env.' '.$base_path.'oifs_webpages/oifs_batch_runtime.py '.$batchid.' CPDN_DEV');
$output = shell_exec($r);
$batch_img=$tmp_path.'Batch_'.$batch_prefix.$batchid.'_timings.png';
if (file_exists($batch_img))
{
     $b64image = base64_encode(file_get_contents($batch_img));
     echo "<img src = 'data:image/png;base64,$b64image' alt='oifs_batch_timings' style='width:50%'>";
}


echo "<br>";
echo "A breakdown of <a href=https://www.cpdn.org/".$boinc_dbname."/batch_os_breakdown.php?batchid=".$batchid.">successful completions by Operating System</a><br>";
echo "Restricted access to <a href=".$host_url_path."/oifs_batch_cpu_analysis.php?batchid=".$batchid.">batch CPU analysis</a>";
echo "<br>";
if ($hostname == 'pandia'){

$fields='concat(\'<a href=https://www.cpdn.org/cpdnboinc/ancil_file_details.php?file_name=\',p.charvalue,\'.gz>\',p.charvalue,\'</a>\') as Filename,';
#echo $fields;

$querystring="select ".$fields." pt.paramtypesh as 'File type', round(sum(if(r.outcome=3,1,0))/b.max_results_per_workunit) as Fails, sum(if(r.outcome=1,1,0)) as Success, sum(if(r.outcome=0,1,0)) as Running from $dbname.paramtype pt join $dbname.parameter p on pt.paramtypeid=p.paramtypeid join $dbname.cpdn_workunit w on p.workunitid=w.wuid join $boinc_dbname.result r on r.workunitid=w.wuid join cpdn_batch b on w.cpdn_batch=b.id where w.cpdn_batch=$batchid and paramtypesh like 'file_%' group by p.charvalue order by pt.paramtypesh";

$result = $link->query($querystring) or die("Error " . mysqli_error($link));

$tablesorter_class='cpdnTable';

class Auto_Table extends HTML_table	{
	private $db_result = NULL;
	private $script_tags =array();
	private $style_tags =array();
	
function make_script($script='script', $content='', $attrs=array()){
	array_push ($this->script_tags, $this->make_tag($script, $content, $attrs));
	
	}

function display_script(){
	$mystr='';
	foreach ($this->script_tags as $tag){
		$mystr.= "$tag\n";
		}
	return $mystr;
	}

function make_css($tag){
	}

function make_tag($tag, $content='', $attrs=array()){
	# void tags have no content, and must be closed with />
	$html_void= array("area", "base", "br", "col", "command", "embed", "hr", "img", "input", "keygen", "link", "meta", "param", "source", "track", "wbr");
	$mytag =''; 
	$mytag ='<'.$tag;
#	if (is_array($attrs) and count($attrs)>0){
#		var_dump($attrs);
#		echo count($attrs);
#		$mytag .= $this->addAttribs($attrs);
#		}
	if (in_array($tag, $html_void)){
		$mytag .= ' />';
		}
	else {
		$mytag .= ">$content</$tag>";
		}
	return $mytag;
	}
	
	
function make_table($db_result){
	$this->db_result = $db_result;
	$this->addTSection('thead');
	$this->addRow();
	$finfo = $this->db_result->fetch_fields();
		 foreach ($finfo as $f){
			$this->addCell($f->name, '', 'header');
			}
	$this->addTSection('tbody');
	while ($row = $db_result->fetch_row()) {
		$this->addRow();
		foreach ($row as $cell){ 
			$this->addCell($cell);
			}
		}
	}
}

$row_cnt = $result->num_rows;
if ($row_cnt > 0) 
{
	echo '<br>';
	echo '<hr>';
	echo '<h2>Breakdown by Ancillary File</h2>';
	echo '<br>';

	$tbl = new Auto_Table('myTable', 'tablesorter');
	#$tbl->addCaption($query, 'cap', array('id'=> 'tblCap') );

	$tbl->make_table($result);
	$tbl->make_script('script',' ',array('src' => "jquery/jquery-latest.js"));
	echo $tbl->display_script();
	echo $tbl->display();
}
 mysqli_free_result($result);
 mysqli_close ($link);

//$tbl->addTSection('thead');
//$tbl->addRow();
//	 $finfo = $result->fetch_fields();
//	 foreach ($finfo as $f)// 
//  		{
//  		$tbl->addCell($f->name, '', 'header');
//  		}
// $tbl->addTSection('tbody');
// while ($row = $result->fetch_row()) {
// 		$tbl->addRow();
// 		foreach ($row as $cell)
// 		{ 
//  		$tbl->addCell($cell);
//  		}
//  		}

}
?>
