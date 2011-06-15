<?
/**************************************************************************************************
 Name: Write to database and enter card details
 System: VPS
 Sub-system: Vendor Components
 Description: Demonstration file for writing order details to a MySQL database
 Version: 1.1 - 26-jan-05
 History:  
 Version Author   Date and Notes
     1.0          17-oct-03 PHP release
     1.1 Peter G  26-jan-05 Update protocol 2.20 -> 2.22
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
  $sql = "INSERT INTO $myTable (BillingAddress, BillingPostCode, DeliveryAddress, DeliveryPostCode, Amount, VendorTxCode, TxType)
  VALUES 
    (
      '" . $_POST["BillingAddress"] . "',
      '" . $_POST["BillingPostCode"] . "',
      '" . $_POST["DeliveryAddress"] . "',
      '" . $_POST["DeliveryPostCode"] . "',
      '" . $_POST["Amount"] . "',
      '" . $_POST["VendorTxCode"] . "',
      '" . $_POST["TxType"] . "'
    )
  ";

  // Get the query as an associative array
  $result=mysql_query($sql,$db);

  // Get the ID number of the new record
  $recordID = mysql_insert_id();

  // Close the database  connection
  mysql_close($db);

?>

<HTML>
  <HEAD>
    <TITLE>Payment details...</TITLE>
  </HEAD>

<SCRIPT language="javascript">
<!--

//***********************************************************
//   Set the TEST card number according to card type selected
//   REMOVE BEFORE GOING LIVE
//***********************************************************

function setCardNumber(cardObject){
  var cardNumber, issueNumber;
  var cardType = cardObject.options[cardObject.selectedIndex].value;
  switch(cardType){
   case 'VISA':
     cardNumber='4929000000006';
     startDate = 0;
    issueNumber='';
     break;
   case 'MC':
     cardNumber='5404000000000001';
     startDate = 0;
    issueNumber='';
     break;
   case 'DELTA':
     cardNumber='4462000000000003';
     startDate = 0;
    issueNumber='';
     break;
   case 'SOLO':
     cardNumber='6334900000000005';
     startDate = 0;
    issueNumber='1';
     break;
   case 'SWITCH':
     cardNumber='5641820000000005';
    issueNumber='01';
     startDate = 1;
     break;
   case 'AMEX':
     cardNumber='374200000000004';
    issueNumber='';
     startDate = 1;
     break;
  } // END switch (cardType)
  window.document.thisForm.CardNumber.value = cardNumber;
  if (issueNumber){
    window.document.thisForm.IssueNumber.value = issueNumber;
  } else {
    window.document.thisForm.IssueNumber.value = '';
  }
  if (startDate){
    if (myMonth==0){
      window.document.thisForm.StartDateMonth.selectedIndex = 12;
      window.document.thisForm.StartDateYear.selectedIndex = myYear+2;
    } else {
      window.document.thisForm.StartDateMonth.selectedIndex = myMonth;
      window.document.thisForm.StartDateYear.selectedIndex = myYear + 3;
    }
  } else {
    window.document.thisForm.StartDateMonth.selectedIndex = 0;
    window.document.thisForm.StartDateYear.selectedIndex = 0;
  }
}

// Set the date fields to current date
function setDate(){
  myDate = new Date();
  myMonth = myDate.getMonth();
  myYear = myDate.getYear()-2; // take away 2 because option list starts at 02
  myYear = (myYear.toString()).substring(2);
  // Expiry date
  window.document.thisForm.ExpiryDateMonth.selectedIndex = myMonth;
  window.document.thisForm.ExpiryDateYear.selectedIndex = myYear + 1;
}

//-->
</SCRIPT>

<BODY onLoad="setDate();">

<H1>Payment details</H1>
<?
  if(!$result){
    echo ("
      <P>There was an error writing to the database.</P>
    ");
  } else {
    echo ("
      Data written to database successfully. Now enter payment details -- you can leave these as-is for testing, or enter your own values if you wish.<BR>
      <FORM NAME='thisForm' ACTION='web_save_order.php' METHOD=POST>
      <TABLE>
        <TR VALIGN=top>
          <TD>Name on card: </TD>
          <TD><INPUT TYPE=text SIZE=32 NAME='CardHolder' VALUE='Test Name'></TD>
        </TR>
        <TR VALIGN=top>
          <TD>Card Number</TD>
          <TD><INPUT TYPE=text SIZE=32 NAME='CardNumber' VALUE='4929000000006'></TD>
        </TR>
        <TR VALIGN=top>
          <TD>Card Type: </TD>
          <TD>
            <SELECT NAME='CardType' onChange='setCardNumber(this);'>
              <OPTION VALUE='VISA'>VISA</OPTION>
              <OPTION VALUE='MC'>MasterCard</OPTION>
              <OPTION VALUE='DELTA'>Delta</OPTION>
              <OPTION VALUE='SOLO'>Solo</OPTION>
              <OPTION VALUE='SWITCH'>Switch</OPTION>
              <OPTION VALUE='AMEX'>American Express</OPTION>
            </SELECT>
          </TD>
        </TR>
        <TR VALIGN=top>
          <TD>Expiry date: </TD>
          <TD>
            <SELECT NAME='ExpiryDateMonth'>
              <OPTION VALUE='01'>01</OPTION>
              <OPTION VALUE='02'>02</OPTION>
              <OPTION VALUE='03'>03</OPTION>
              <OPTION VALUE='04'>04</OPTION>
              <OPTION VALUE='05'>05</OPTION>
              <OPTION VALUE='06'>06</OPTION>
              <OPTION VALUE='07'>07</OPTION>
              <OPTION VALUE='08'>08</OPTION>
              <OPTION VALUE='09'>09</OPTION>
              <OPTION VALUE='10'>10</OPTION>
              <OPTION VALUE='11'>11</OPTION>
              <OPTION VALUE='12'>12</OPTION>
            </SELECT>
            <SELECT NAME='ExpiryDateYear'>
              <OPTION VALUE='05'>2005</OPTION>
              <OPTION VALUE='06'>2006</OPTION>
              <OPTION VALUE='07'>2007</OPTION>
              <OPTION VALUE='08'>2008</OPTION>
            </SELECT>
          </TD>
        </TR>
        <TR VALIGN=top>
          <TD>Client Number [Optional]: </TD>
          <TD><INPUT TYPE=text SIZE=2 NAME='ClientNumber'></TD>
        </TR>
        <TR VALIGN=top>
          <TD>Start date [Optional]: </TD>
          <TD>
            <SELECT NAME='StartDateMonth'>
              <OPTION VALUE=''></OPTION>
              <OPTION VALUE='01'>01</OPTION>
              <OPTION VALUE='02'>02</OPTION>
              <OPTION VALUE='03'>03</OPTION>
              <OPTION VALUE='04'>04</OPTION>
              <OPTION VALUE='05'>05</OPTION>
              <OPTION VALUE='06'>06</OPTION>
              <OPTION VALUE='07'>07</OPTION>
              <OPTION VALUE='08'>08</OPTION>
              <OPTION VALUE='09'>09</OPTION>
              <OPTION VALUE='10'>10</OPTION>
              <OPTION VALUE='11'>11</OPTION>
              <OPTION VALUE='12'>12</OPTION>
            </SELECT>
            <SELECT NAME='StartDateYear'>
              <OPTION VALUE=''></OPTION>
              <OPTION VALUE='00'>2000</OPTION>
              <OPTION VALUE='01'>2001</OPTION>
              <OPTION VALUE='02'>2002</OPTION>
              <OPTION VALUE='03'>2003</OPTION>
              <OPTION VALUE='04'>2004</OPTION>
              <OPTION VALUE='05'>2005</OPTION>
            </SELECT>
          </TD>
        </TR>
        <TR VALIGN=top>
          <TD>Issue Number [Optional]: </TD>
          <TD><INPUT TYPE=text SIZE=2 NAME='IssueNumber' VALUE=''></TD>
        </TR>
        <TR VALIGN=top>
          <TD>CVV Value [Optional]: </TD>
          <TD><INPUT TYPE=text SIZE=4 NAME='CV2' VALUE=''></TD>
        </TR>
      </TABLE>
        <INPUT TYPE=hidden NAME=id VALUE=$recordID>
        <INPUT TYPE=hidden NAME=Description VALUE='" . $_POST["Description"] . "'>
        <INPUT TYPE=hidden NAME=VendorTxCode VALUE='" . $_POST["VendorTxCode"] . "'>
        <INPUT TYPE=hidden NAME=ContactNumber VALUE='" . $_POST["ContactNumber"] . "'>
        <INPUT TYPE=hidden NAME=ContactFax VALUE='" . $_POST["ContactFax"] . "'>
        <INPUT TYPE=hidden NAME=CustomerName VALUE='" . $_POST["CustomerName"] . "'>
        <INPUT TYPE=hidden NAME=CustomerEMail VALUE='" . $_POST["CustomerEMail"] . "'>
        <INPUT TYPE=hidden NAME=GiftAidPayment VALUE='" . $_POST["GiftAidPayment"] . "'>
        <INPUT TYPE=hidden NAME=ApplyAVSCV2 VALUE='" . $_POST["ApplyAVSCV2"] . "'>
        <INPUT TYPE=hidden NAME=ClientIPAddress VALUE='" . $_POST["ClientIPAddress"] . "'>
        <INPUT TYPE=hidden NAME=CAVV VALUE='" . $_POST["CAVV"] . "'>
        <INPUT TYPE=hidden NAME=XID VALUE='" . $_POST["XID"] . "'>
        <INPUT TYPE=hidden NAME=ECI VALUE='" . $_POST["ECI"] . "'>
        <INPUT TYPE=hidden NAME=3DSecureStatus VALUE='" . $_POST["3DSecureStatus"] . "'>
        <INPUT TYPE=hidden NAME=Basket VALUE='" . $_POST["Basket"] . "'>
        <INPUT TYPE=submit VALUE='Continue'>
      </FORM>
    ");
  }
?>

</BODY>
</HTML>
