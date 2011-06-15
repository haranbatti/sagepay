<?
/***********************************************************************
Title:       test-purchase.php
Description: 
  Collect details, excluding credit card details, required
  to effect a payment transaction.

Version:     1.1 - 26-jan-05
History:
Version Author   Date and Notes
    1.1 Peter G  26-jan-05 Update protocol 2.20 -> 2.22
************************************************************************/

// Include the initialisation files
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
		<TD><INPUT NAME=Amount TYPE=text VALUE=20 SIZE=25><BR>
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
		<TD>Description [Optional]:</TD>
		<TD><TEXTAREA NAME=Description WIDTH=25 HEIGHT=4></TEXTAREA><BR>
	</TR>
	<TR>
		<TD>Billing Address [Optional]:</TD>
		<TD><TEXTAREA NAME="BillingAddress" WIDTH=25 HEIGHT=4></TEXTAREA><BR>
	</TR>
	<TR>
		<TD>Billing Postcode [Optional]:</TD>
		<TD><INPUT NAME="BillingPostCode" TYPE=text SIZE=25><BR>
	</TR>
	<TR>
		<TD>Delivery Address [Optional]:</TD>
		<TD><TEXTAREA NAME="DeliveryAddress" WIDTH=25 HEIGHT=4></TEXTAREA><BR>
	</TR>
	<TR>
		<TD>Delivery Postcode [Optional]:</TD>
		<TD><INPUT NAME="DeliveryPostCode" TYPE=text SIZE=25><BR>
	</TR>
	<TR>
		<TD>Contact Number [Optional]:</TD>
		<TD><INPUT NAME="ContactNumber" TYPE=text SIZE=25><BR>
	</TR>
	<TR>
		<TD>Contact Fax [Optional]:</TD>
		<TD><INPUT NAME="ContactFax" TYPE=text SIZE=25><BR>
	</TR>
	<TR>
		<TD>Customer Name [Optional]:</TD>
		<TD><INPUT NAME="CustomerName" TYPE=text SIZE=50><BR>
	</TR>
	<TR>
		<TD>Customer E-Mail [Optional]:</TD>
		<TD><INPUT NAME="CustomerEMail" TYPE=text SIZE=50><BR>
	</TR>
  <TR>
    <TD>Gift Aid Payment Flag [Optional]:</TD>
    <TD>
      <select name="GiftAidPayment">
        <option value="">No value sent - not a gift aid transaction</option>
        <option value="0">0 - not a gift aid transaction</option>
        <option value="1">1 - customer has agreed to donate tax as gift aid</option>
      </select>
    </TD>
  </TR>
  <TR>
    <TD>Apply AVSCV2 Flag [Optional]</TD>
    <TD>
       <select name="ApplyAVSCV2">
         <option value="">No value sent - if enabled, check. if rules, apply. (default)</option>
         <option value="0">0 - if enabled, check. if rules, apply. (default)</option>
         <option value="1">1 - force check. if rules, apply.</option>
         <option value="2">2 - do not check.</option>
         <option value="3">3 - force check. do not apply rules.</option>
       </select>
     </TD>
   </TR>
	<TR>
		<TD>Client IP Address [Optional]:</TD>
		<TD><INPUT NAME="ClientIPAddress" TYPE=text SIZE=25><BR>
	</TR>
	<TR>
		<TD>CAVV - 3D-Secure signature [Optional]:</TD>
		<TD><INPUT NAME="CAVV" TYPE=text SIZE=25><BR>
	</TR>
	<TR>
		<TD>XID - 3D-Secure ID [Optional]:</TD>
		<TD><INPUT NAME="XID" TYPE=text SIZE=25><BR>
	</TR>
	<TR>
		<TD>ECI - 3D-Secure transaction type [Optional]:</TD>
		<TD><INPUT NAME="ECI" TYPE=text SIZE=25><BR>
	</TR>
	<TR>
		<TD>3D Secure Status [Optional]:</TD>
		<TD><INPUT NAME="3DSecureStatus" TYPE=text SIZE=25><BR>
	</TR>
	<TR>
		<TD>Basket - leave blank if you do not wish to send the basket [Optional]:</TD>
		<TD><INPUT TYPE="text" NAME="Basket" VALUE="3:Sony SV-234 DVD Player:1:£170.20:£29.79:£199.99:£199.99:The Fast and The Furious Region 2 DVD:2:£17.01:£2.98:£19.99:£39.98:Delivery:1:£4.99:----:£4.99:£4.99"></TD>
	</TR>

</TABLE>
<INPUT TYPE=submit VALUE="Submit">
</FORM>

</BODY>
</HTML>
