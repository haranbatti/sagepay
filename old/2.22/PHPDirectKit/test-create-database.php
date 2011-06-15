<?
/************************************
Script to create demo MySQL table
************************************/

// *** Include the initialisation files
include ("init-includes.php");


?>

<HTML>
	<HEAD>
		<TITLE>Create a test MySQL database</TITLE>
	</HEAD>

<BODY>
	<H2>Create a test MySQL database</H2>
	
	<P>This script will attempt to create a simple test database/table your local instance of MySQL. </P>
	
	<P>The table contains the minimum fields required for this kit to function as a test environment. Ultimately you will probably want to add your own fields to store additional transaction information.</P>
	
	<P>Obviously MySQL must be running and you must have the appropriate access rights to it (ie. create database and create table). Before testing the kit, be sure to set the required host name, user ID and password in the file <I>init-dbconnect.php</I>.</P>
	
	<FORM ACTION="test-create-database-execute.php" METHOD=POST>
	<TABLE>
		<TR VALIGN=top>
			<TD>User name</TD>
			<TD><INPUT TYPE="text" NAME="user" VALUE="<?=$myUser?>" SIZE=25></TD>
		</TR>
		<TR VALIGN=top>
			<TD>Password</TD>
			<TD><INPUT TYPE="password" NAME="pass" VALUE="<?=$myPass?>" SIZE=25></TD>
		</TR>
	</TABLE>

	<P>If you wish to, you may set the name of the database (if it's an already existing database, put the name in below and it'll create the table in that database) and the table name here to whatever you want. If you do so, you will also need to change the default database and table names in <I>init-dbconnect.php</I>.</P>
	
	<TABLE>
		<TR VALIGN=top>
			<TD>Database name</TD>
			<TD><INPUT TYPE="text" NAME="dbname" VALUE="<?=$myDB?>" SIZE=25></TD>
		</TR>
		<TR VALIGN=top>
			<TD>Table name</TD>
			<TD><INPUT TYPE="text" NAME="tablename" VALUE="<?=$myTable?>" SIZE=25></TD>
		</TR>
	</TABLE>
	<INPUT TYPE="Submit" VALUE="Submit">
	</FORM>
	
</BODY>
</HTML>