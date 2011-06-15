<?
/**************************************************************************************************
 Name: Write to database
 System: VPS
 Sub-system: Vendor Components
 Description: Demonstration file for writing order details to a MySQL database
 Version: 1.0
 Date: 17/10/2002
 History:  Version 1.0 - PHP release
*************************************************************************************************/

// *** Include the initialisation files
include ("init-includes.php");

/**************************************************************************************************
	Send order information to your database
**************************************************************************************************/

	/*
	Some demo code for connecting to a MySQL database
	*/

	// Make the connection
	$db = mysql_connect($myHost, $myUser, $myPass);

	// Select the database
	mysql_select_db($myDB,$db);

	// Set the query (insert new record)
	$sql = "INSERT INTO $myTable (Address, PostCode, Amount, VendorTxCode, TxType)
	VALUES 
		(
			'" . $_POST["Address"] . "',
			'" . $_POST["PostCode"] . "',
			'" . $_POST["Amount"] . "',
			'" . $_POST["VendorTxCode"] . "',
			'" . $_POST["TxType"] . "'
		)
	";

	// Get the query as an associative array
	$result=mysql_query($sql,$db);

	// Get the ID number of the new record
	$recordID = mysql_insert_id();

	// Close the database	connection
	mysql_close($db);

?>

<HTML>
	<HEAD>
		<TITLE>Wrtiting data to database,,,</TITLE>
	</HEAD>

<BODY>

<H1>Wrtiting data to database...</H1>
<?
	if(!$result){
		echo ("
			<P>There was an error writing to the database.</P>
		");
	} else {
		echo ("
			Data written successfully.<BR>\n
			<FORM ACTION='web_save_order.php' METHOD=POST>\n
				<INPUT TYPE=hidden NAME=id VALUE=$recordID>\n
				<INPUT TYPE=hidden NAME=Description VALUE='" . $_POST["Description"] . "'>\n
				<INPUT TYPE=hidden NAME=VendorTxCode VALUE='" . $_POST["VendorTxCode"] . "'>\n
				<INPUT TYPE=submit VALUE='Continue'>
			</FORM>
		");
	}
?>

</BODY>
</HTML>