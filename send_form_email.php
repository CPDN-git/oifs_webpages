<?php
// Force PHP to show errors
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// This handler lives in html/user/cpdn; BOINC expects html/user as cwd.
if (!chdir(__DIR__ . '/..')) {
    http_response_code(500);
    exit('Unable to select the BOINC web directory.');
}
$config_file = __DIR__ . '/../../project/batch_site_config.inc';
if (!is_readable($config_file)) {
    http_response_code(500);
    exit('Batch site configuration is unavailable.');
}
require_once($config_file);
if (!isset($project_path) || !is_string($project_path) || $project_path === '') {
    http_response_code(500);
    exit('Batch site configuration is incomplete for this host.');
}

define('PROJECT_PATH', rtrim($project_path, '/') . '/');
define('CPDN_LOCAL', PROJECT_PATH . 'html/user/cpdn');
define('BOINC_INC', PROJECT_PATH . 'html/inc/');
define('PROJECT_INC', rtrim($project_path, '/') . '/html/project/');

require_once(PROJECT_INC . 'oifs_contacts.inc');

set_include_path(
    get_include_path() .
    PATH_SEPARATOR . BOINC_INC .
    PATH_SEPARATOR . CPDN_LOCAL .
    PATH_SEPARATOR . PROJECT_INC
);


echo <<<EOH
<html>
<head>
<title>OpenIFS@home Contact Us</title>
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

if(isset($_POST['email'])) {
 
    $email_subject = "OpenIFS@home Enquiry";
 
    function died($error) {
        // your error code can go here
        echo "We are very sorry, but there were error(s) found with the form you submitted. ";
        echo "These errors appear below.<br /><br />";
        echo $error."<br /><br />";
        echo "Please go back and fix these errors.<br /><br />";
        die();
    }
 
 
    // validation expected data exists
    if(!isset($_POST['FirstName']) ||
        !isset($_POST['LastName']) ||
        !isset($_POST['Affiliation']) ||
    !isset($_POST['email']) ||
        !isset($_POST['Message'])) {
        died('We are sorry, but there appears to be a problem with the form you submitted.');       
    }
 
     
 
    $first_name = $_POST['FirstName']; // required
    $last_name = $_POST['LastName']; // required
    $affil = $_POST['Affiliation']; // required
    $email_from = $_POST['email']; // required
    $comments = $_POST['Message']; // required
 
    $error_message = "";
    $email_exp = '/^[A-Za-z0-9._%-]+@[A-Za-z0-9.-]+\.[A-Za-z]{2,4}$/';
 
  if(!preg_match($email_exp,$email_from)) {
    $error_message .= 'The e-mail address you entered does not appear to be valid.<br />';
  }
 
    $string_exp = "/^[A-Za-z .'-]+$/";
 
  if(!preg_match($string_exp,$first_name)) {
    $error_message .= 'The first name you entered does not appear to be valid.<br />';
  }
 
  if(!preg_match($string_exp,$last_name)) {
    $error_message .= 'The last name you entered does not appear to be valid.<br />';
  }

  if(strlen($affil) < 2) {
    $error_message .= 'The affiliation you entered do not appear to be valid.<br />';
  }

  if(strlen($comments) < 2) {
    $error_message .= 'The message you entered do not appear to be valid.<br />';
  }
 
  if(strlen($error_message) > 0) {
    died($error_message);
  }
 
    $email_message = "Form details below.\n\n";
 
     
    function clean_string($string) {
      $bad = array("content-type","bcc:","to:","cc:","href");
      return str_replace($bad,"",$string);
    }
 
     
 
    $email_message .= "First Name: ".clean_string($first_name)."\n";
    $email_message .= "Last Name: ".clean_string($last_name)."\n";
    $email_message .= "Affiliation: ".clean_string($affil)."\n";
    $email_message .= "Email: ".clean_string($email_from)."\n";
    $email_message .= "Message: ".clean_string($comments)."\n";
 
// create email headers
$headers = 'From: '.$email_from."\r\n".
'Reply-To: '.$email_from."\r\n" .
'X-Mailer: PHP/' . phpversion();
foreach ($email_contact as $email_to){
    mail($email_to, $email_subject, $email_message, $headers); 
} 
?>
 
<!-- include your own success html here -->
<h1>New Collaboration Enquiry Form</h1> 
Thank you for contacting us. We will be in touch with you very soon.
 
<?php
 
}
?>
