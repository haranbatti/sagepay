<?
/*********************************************************
Title:       test-create-database-execute.php
Description: Script to create demo MySQL table
Version:     1.1 - 26-jan-05
History:
Version Author   Date and Notes
    1.1 Peter G  26-jan-05 Update protocol 2.20 -> 2.22
*********************************************************/

// *** Include the initialisation files
include ("init-includes.php");

$myPass = $_POST['pass'];
$myUser = $_POST['user'];
$dbname = $_POST['dbname'];
$tablename = $_POST['tablename'];

$dbError = '';
$tableError = '';

/***************************************
	Connect to the database
***************************************/

// Make the connection
$db = mysql_connect($myHost, $myUser, $myPass);

//Set the query
$sql = "CREATE DATABASE $dbname";

// Create the database
if (!$result=mysql_query($sql)){
	$dbError = 	"Error, database not created: " . mysql_error();
}

// Select the database
mysql_select_db($dbname,$db);

//Set the query
$sql = "CREATE TABLE " . $tablename . " (

  id bigint(20) unsigned NOT NULL auto_increment,
  BillingAddress varchar(200) default NULL,
  BillingPostCode varchar(10) default NULL,
  DeliveryAddress varchar(200) default NULL,
  DeliveryPostCode varchar(10) default NULL,
  VendorTxCode varchar(50) NOT NULL default '',
  Amount decimal(10,2) NOT NULL default '0',
  TxType varchar(32) NOT NULL default '',
  Status varchar(32) default NULL,
  StatusDetail varchar(200) default NULL,
  VPSTxId varchar(64) default NULL,
  SecurityKey varchar(10) default NULL,
  TxAuthNo bigint(20) NOT NULL default '0',
  AVSCV2 varchar(50) default NULL,
  AddressResult varchar(20) default NULL,
  PostCodeResult varchar(20) default NULL,
  CV2Result varchar(20) default NULL,
  PRIMARY KEY  (id),
  KEY VendorTxCode (VendorTxCode)
) TYPE=MyISAM
";

// Get the query object
if (!$result=mysql_query($sql,$db)){
	$tableError = "Error, table not created: " . mysql_error();
}

// Close the database
mysql_close($db);

?>

<HTML>
<BODY>
<H3>
<?
if ($dbError == ''){
	echo("Database Created successfully</SPAN>");
} else {
	echo ("<SPAN STYLE='color:red'>" . $dbError . "</SPAN>");
}
	echo('<BR>');
if ($tableError == ''){
	echo('Table Created successfully');
} else {
	echo ("<SPAN STYLE='color:red'>" . $tableError . "</SPAN>");
}

?></H3>
<P><A HREF="./">Back to main page</A>
</BODY>
</HTML>
