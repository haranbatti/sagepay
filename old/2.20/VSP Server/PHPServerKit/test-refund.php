<?
// *** Include the initialisation files
include ("init-includes.php");

/***************************************
	Connect to the database
***************************************/

// Database settings
$myDB = "mydb";								// Database name
$myTable = "mytable";					// Table name

// Make the connection
$db = mysql_connect($myHost, $myUser, $myPass);

// Select the database
mysql_select_db($myDB,$db);

// 
$sql = "SELECT * from $myTable
	WHERE TxAuthNo > 0 AND TxTYPE='PAYMENT'
";

// Get the query object
@$result=mysql_query($sql,$db);

// How many rows?
$numRows = mysql_num_rows($result);

// Initialise output
$output = '';

for($i=0; $i<$numRows; $i++){
	$row = mysql_fetch_array($result);
	$output .= "<TR VALIGN=top>\n";
	$output .= '
		<TD>' .
		$row['VendorTxCode'] . '
		</TD>
		<TD>' .
		$row['Amount'] . '
		</TD>
		<TD>' .
		$row['TxType'] . '
		</TD>'
	;
	$output .= "<TD><INPUT TYPE=radio NAME=VendorTxCode VALUE='" . $row['VendorTxCode'] . "'";

	// Select the first by default
	if (!$i){
		$output .= ' CHECKED';
	}

	$output .= '></TD>';
	$output .= "\n</TR>\n";
}

// Close the database
mysql_close($db);

/**************************************/

?>

<HTML>
	<HEAD>
		<TITLE>Test refund transaction</TITLE>
	</HEAD>

<BODY>

<H2>Test refund transaction</H2>


<P>Pick a transaction:</P>

<FORM METHOD=POST ACTION="vps_order_refund.php">
<TABLE BORDER=1>
	<TR VALIGN=top>
		<TD><B>VendorTxCode</B></TD>
		<TD><B>Amount</B></TD>
		<TD><B>Type</B></TD>
		<TD><B>Pick One</B></TD>
		
	<?=$output?>
</TABLE>

<P>You may enter your own details in the boxes below or leave them as-is for testing.</P>

<TABLE BORDER=0>
	<TR>
		<TD>Refund Amount:</TD>
		<TD><INPUT NAME=Amount TYPE=text VALUE=200 SIZE=25><BR>
	</TR>
	<TR>
		<TD>Description [Optional - <BR>up to 100 characters]:</TD>
		<TD><TEXTAREA NAME=Description WIDTH=25 HEIGHT=4></TEXTAREA><BR>
</TABLE>
<INPUT TYPE=submit VALUE="Submit">
</FORM>

</BODY>
</HTML>