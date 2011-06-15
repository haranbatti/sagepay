<?
// *** Include the initialisation files
include ("init-includes.php");

// Generate a random transaction code
$VendorTxCode = $Vendor . (rand(0,320000) * rand(0,320000));
?>
<HTML>
	<HEAD>
		<TITLE>Test purchase transaction</TITLE>
	</HEAD>

<BODY>

<H2>Test purchase transaction</H2>

<P>You may enter your own details in the boxes below or leave them as-is for testing. The VendorTxCode is generated randomly.

<FORM METHOD=POST ACTION="test-purchase-execute.php">
<TABLE BORDER=0>
	<TR>
		<TD>VendorTxCode:</TD>
		<TD><INPUT NAME= VendorTxCode TYPE=text VALUE=<?=$VendorTxCode?> SIZE=25></TD>
	</TR>
	<TR>
		<TD>Amount:</TD>
		<TD><INPUT NAME=Amount TYPE=text VALUE=200 SIZE=25><BR>
	</TR>
	<TR>
		<TD>Transaction type</TD>
		<TD>
			<SELECT NAME=TxType>
				<OPTION VALUE=PAYMENT CHECKED>PAYMENT</OPTION>
				<OPTION VALUE=DEFERRED>DEFERRED</OPTION>
				<OPTION VALUE=PREAUTH>PREAUTH</OPTION>
			</SELECT>
		</TD>
	</TR>
	<TR>
		<TD>Address [Optional]:</TD>
		<TD><TEXTAREA NAME=Address WIDTH=25 HEIGHT=4></TEXTAREA><BR>
	</TR>
	<TR>
		<TD>Postcode [Optional]:</TD>
		<TD><INPUT NAME=PostCode TYPE=text SIZE=25><BR>
	</TR>
	<TR>
		<TD>Description [Optional]:</TD>
		<TD><TEXTAREA NAME=Description WIDTH=25 HEIGHT=4></TEXTAREA><BR>
	</TR>
</TABLE>
<INPUT TYPE=submit VALUE="Submit">
</FORM>

</BODY>
</HTML>