<?php
require_once("../inc/batch_site_config.inc");

set_include_path(get_include_path() . PATH_SEPARATOR . $project_path."html/inc"); 
$xml=simplexml_load_file($project_path."ancil_batch_user_config.xml") or die("Error: Cannot create object");

$host= $xml->db_host;
$dbname=$xml->db_name;
$boinc_dbname=$xml->boinc_db_name;
$user= $xml->batch_user;
$pass= $xml->batch_passwd;
$hostname = gethostname();

$ms_host= $xml->ancil_db_host;
$ms_dbname=$xml->ancil_db_name;
$ms_boinc_dbname=$xml->ancil_boinc_db_name;
$ms_user= $xml->ancil_user;
$ms_pass= $xml->ancil_passwd;

# include the table tag generatora 
require_once("includes/html_table.class.php");

$table="cpdn_batch b join $dbname.cpdn_project p on p.id=b.projectid";
$ms_table="cpdn_batch b join $ms_dbname.cpdn_project p on p.id=b.projectid";

$fields='concat(\'<a href='.$host_url_path.'/oifs_batch_info.php?batchid=\',b.id,\'>'.$batch_prefix.'\',b.id,\'</a>\') as Batch,';
$ms_fields='concat(\'<a href='.$ms_host_url_path.'/oifs_batch_info.php?batchid=\',b.id,\'>\',b.id,\'</a>\') as Batch,';

$fopen=" b.number_of_workunits as 'Ensemble Size', date(b.submit_time) as 'Submit Date', b.name as Name,substring_index(b.owner,'<',1) as Owner, b.description as Description";
$fclosed=" b.number_of_workunits as 'Ensemble Size',date(b.submit_time) as 'Submit Date', b.archive_status as Status, b.name as Name,substring_index(b.owner,'<',1) as Owner, b.description as Description";

$fields_open="$fields $fopen";
$fields_closed="$fields $fclosed";

$ms_fields_open="$ms_fields $fopen";
$ms_fields_closed="$ms_fields $fclosed";

$condition_open="WHERE b.ended=0 and p.name='OpenIFSATHOME'";
$condition_closed="WHERE b.ended=1 and p.name='OpenIFSATHOME'";

$order= 'order by b.id DESC';

$tablesorter_class='cpdnTable';



echo <<<EOH
<html>
<head>
<title>OpenIFS@home Dashboard</title>
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
<script type="text/javascript">
function condDisp(section){
	var x = document.getElementById(section);
	if (x.style.display ==="none") {
		x.style.display = "block";
	} else {
		x.style.display = "none";
	}
}
</script>
</head>
<body>
EOH;

echo '<div class="wrap" style="width:100%">';
echo '<div style="width:100%">';
echo '<img src="img/OIFS_Home_logo.png" alt="OpenIFS@home" style="width:200px">';
echo '<img src="img/CPDN_abbrv_logo.png" alt="CPDN" style="width:250px; float:right;">';
echo '</div>';
echo '<div style="clear: both;"></div>';
echo '</div>';
echo '<hr>';
?>
<table>
<tr class="nohover">
<td colspan="3"><h2>General Information</h2></td>
<td width="5%"></td>
<td colspan="5"><h2>Ensemble Preparation</h2></td>
</tr>
<tr class="nohover">
<td><a href="https://www.ecmwf.int/en/research/projects/openifs"><button style="height:50px;font-size:14px;border-radius: 8px;background-color:#DCDCDC;">OpenIFS</button></a></td>
<td><a href="https://drive.google.com/open?id=12_Dfl_Aw1Zbw_AN3T71qINzTWzb1ssfM"><button style="height:50px;font-size:14px;border-radius: 8px;background-color: #DCDCDC;">Training Notes</button></a></td>
<td><a href="https://www.cpdn.org/cpdn_publications.php"><button style="height:50px;font-size:14px;border-radius: 8px;background-color:#DCDCDC;">Publication List</button></a></td>
<td width="5%"></td>
<td><a href="oifs_upload_form.php"><button style="height:50px;font-size:14px;border-radius: 8px;background-color: #DCDCDC;">Upload Files</button></a></td>
<td><a href="oifs_search.php"><button style="height:50px;font-size:14px;border-radius: 8px;background-color:#DCDCDC;">Search Files</button></a></td>
<td><a href="oifs_xml_generation.php"><button style="height:50px;font-size:14px;border-radius: 8px;background-color:#DCDCDC;">Create Dev Ensemble</button></a></td>
<td><a href="https://www.cpdn.org/oifs_xml_generation.php"><button style="height:50px;font-size:14px;border-radius: 8px;background-color:#DCDCDC;">Create Main Ensemble</button></a></td>
<td><a href="https://trello.com/b/mQvpbUci/cpdn-dev-site-submissions"><button style="height:50px;font-size:14px;border-radius: 8px;background-color:#DCDCDC;">Dev Site Trello</button></a></td>
<td><a href="https://trello.com/b/fnvZafWI/cpdn-main-work-site-submissions"><button style="height:50px;font-size:14px;border-radius: 8px;background-color:#DCDCDC;">Main Site Trello</button></a></td>
</tr>
</table>
<?php
echo '<br><hr><h2>Open Batches</h2>';

$r = escapeshellcmd( $python_env.' '.$base_path.'oifs_webpages/oifs_batch_status.py CPDN_DEV');
$output = shell_exec($r);

$r = escapeshellcmd( $python_env.' '.$base_path.'oifs_webpages/oifs_main_batch_status.py CPDN_DEV');
$output = shell_exec($r);

$query_open ="SELECT $fields_open FROM $table $condition_open $order";
$query_closed ="SELECT $fields_closed FROM $table $condition_closed $order";

$ms_query_open ="SELECT $ms_fields_open FROM $ms_table $condition_open $order";
$ms_query_closed ="SELECT $ms_fields_closed FROM $ms_table $condition_closed $order";

$link = mysqli_connect($host,$user,$pass,$dbname) or die("Error " . mysqli_error($link));
$link_ms = mysqli_connect($ms_host,$ms_user,$ms_pass,$ms_dbname) or die("Error " . mysqli_error($link_ms));

class Auto_Table extends HTML_table     {
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
#       if (is_array($attrs) and count($attrs)>0){
#               var_dump($attrs);
#               echo count($attrs);
#               $mytag .= $this->addAttribs($attrs);
#               }
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

echo "<h3>Open Dev Batches</h3>";
echo '<img src="oifs_batch_statistics.png" alt="oifs_batch_stats" style="width:50%">';
echo '<div id="DevOpen">';
$result_open = $link->query($query_open) or die("Error in the consult.." . mysqli_error($link));
$tbl = new Auto_Table('myTable', 'tablesorter');
$tbl->make_table($result_open);
$tbl->make_script('script',' ',array('src' => "jquery/jquery-latest.js"));
echo $tbl->display_script();
echo $tbl->display();
mysqli_free_result($result_open);
echo '</div>';
echo '<br>';
echo '<button onclick="condDisp(\'DevOpen\')">Show/Hide Details</button>';

echo '<br><hr style="border-top: dashed 1px;"><h3>Open Main Batches</h3>';
echo '<img src="oifs_batch_statistics_main.png" alt="oifs_main_batch_stats" style="width:50%">';
echo '<div id="MainOpen">';
$ms_result_open = $link_ms->query($ms_query_open) or die("Error in the consult.." . mysqli_error($link_ms));
$tbl3 = new Auto_Table('myTable', 'tablesorter');
$tbl3->make_table($ms_result_open);
$tbl3->make_script('script',' ',array('src' => "jquery/jquery-latest.js"));
echo $tbl3->display_script();
echo $tbl3->display();
mysqli_free_result($ms_result_open);
echo '</div>';
echo '<br>';
echo '<button onclick="condDisp(\'MainOpen\')">Show/Hide Details</button>';

echo '<br><hr><h2>Closed Batches</h3>';
echo "<h3>Closed Dev Batches</h3>";
echo '<div id="DevClosed" style="display:none">';
$result_closed = $link->query($query_closed) or die("Error in the consult.." . mysqli_error($link));
$tbl2 = new Auto_Table('myTable', 'tablesorter');
$tbl2->make_table($result_closed);
$tbl2->make_script('script',' ',array('src' => "jquery/jquery-latest.js"));
echo $tbl2->display_script();
echo $tbl2->display();
mysqli_free_result($result_closed);
echo '</div>';
echo '<br>';
echo '<button onclick="condDisp(\'DevClosed\')">Show/Hide Details</button>';

echo '<br><hr style="border-top: dashed 1px;"><h3>Closed Main Batches</h3>';
echo '<div id="MainClosed" style="display:none">';
$ms_result_closed = $link_ms->query($ms_query_closed) or die("Error in the consult.." . mysqli_error($link_ms));
$tbl4 = new Auto_Table('myTable', 'tablesorter');
$tbl4->make_table($ms_result_closed);
$tbl4->make_script('script',' ',array('src' => "jquery/jquery-latest.js"));
echo $tbl4->display_script();
echo $tbl4->display();
mysqli_free_result($ms_result_closed);
echo '</div>';
echo '<br>';
echo '<button onclick="condDisp(\'MainClosed\')">Show/Hide Details</button>';
mysqli_close ($link);
mysqli_close ($link_ms);

 ?>
