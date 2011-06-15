<?
/**************************************************************************************************
	Name: VPS Order Complete
	System: VPS
	Sub-system: Vendor Components
	Description: An example page to inform the user of a successful order
	Version: 1.1
	Date: 18/10/2002
	History:  Version 1.1 - PHP release
	History:  Version 1.0 - First ASP release
**************************************************************************************************/

// It's a good idea here to clear your shopping basket in case the user wants to buy other things
 
?> 

<html>
	<head>
		<title>Order Complete Page</title>
	</head>

<body>

<H1>Your payment has been accepted</H1>

<P><font size="3"><b>Thank you for your order</b></font><BR><BR>

Our transaction code for this purchase is <STRONG><?= $_GET['VendorTxCode'] ?></STRONG>. Please quote this code in all communications with us about this order.<BR><BR>

Your goods will be delivered within the next 10 working days.<BR><BR>

Click <A HREF="./">here</A> to return to our main menu.
</P>

</body>
</html>