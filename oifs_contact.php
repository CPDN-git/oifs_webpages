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

require_once(BOINC_INC . 'batch_site_config.inc');

set_include_path(
    get_include_path() .
    PATH_SEPARATOR . BOINC_INC .
    PATH_SEPARATOR . CPDN_LOCAL
);


echo <<<EOH
<html>
<head>
<title>OpenIFS@home Dashboard</title>
<META NAME="ROBOTS" CONTENT="NOINDEV, NOFOLLOW">
<style type="text/css">
                        #sortedtable thead th {
                                color: #00f;
                                font-weight: bold;
                                text-decoration: underline;
                        }
                </style>
<link rel="stylesheet" href="/cpdn.css">
</head>
<body>
EOH;
echo '<div class="wrap" style="width:100%">';
echo '<div style="width:100%">';
echo '<img src="/img/OIFS_Home_logo.png" alt="OpenIFS@home" style="width:200px">';
echo '<img src="/img/CPDN_abbrv_logo.png" alt="CPDN" style="width:250px; float:right;">';
echo '</div>';
echo '<div style="clear: both;"></div>';
echo '</div>';
echo '<hr>';
?>
<h1>New Collaboration Enquiry Form</h1>
<form name="contactform" method="post" action="/cpdn/send_form_email.php">
<table width="100%" border="0" style="border:none;">
<tr class="nohover"><td Width=10%>First Name:</td><td width=90%><input type="text" name="FirstName" size="20"></td></tr>
<tr class="nohover"><td>Last Name:</td><td><input type="text" id="LastName" name="LastName" size="20"></td></tr>
<tr class="nohover"><td>Affiliation:</td><td><input type="text" id="Affiliation" name="Affiliation" size="50"></td></tr>
<tr class="nohover"><td>E-mail:</td><td><input type="text" id="email" name="email" size="50"></td></tr>        
<tr class="nohover"><td>Message:</td><td><textarea id="Message" name="Message" rows="10" cols="90"></textarea></td></tr>
</table>
<input class="button" type="submit" name="submit" value="Send" />
</form>
