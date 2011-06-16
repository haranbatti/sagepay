USE sagepay;

CREATE TABLE tblOrders (
    VendorTxCode varchar(50) NOT NULL default '',
    TxType varchar(32) NOT NULL default '',
    Amount decimal(10,2) NOT NULL default '0',
    Currency varchar(3) NOT NULL default '',
    BillingFirstnames varchar(20) default NULL,
    BillingSurname varchar(20) default NULL,
    BillingAddress1 varchar(100) default NULL,
    BillingAddress2 varchar(100) default NULL,
    BillingCity varchar(40) default NULL,
    BillingPostCode varchar(10) default NULL,
    BillingCountry varchar(2) default NULL,
    BillingState varchar(2) default NULL,
    BillingPhone varchar(20) default NULL,
    DeliveryFirstnames varchar(20) default NULL,
    DeliverySurname varchar(20) default NULL,
    DeliveryAddress1 varchar(100) default NULL,
    DeliveryAddress2 varchar(100) default NULL,
    DeliveryCity varchar(40) default NULL,
    DeliveryPostCode varchar(10) default NULL,
    DeliveryCountry varchar(2) default NULL,
    DeliveryState varchar(2) default NULL,
    DeliveryPhone varchar(20) default NULL,
    CustomerEMail varchar(100) default NULL,
    VPSTxId varchar(64) default NULL,
    SecurityKey varchar(10) default NULL,
    TxAuthNo bigint(20) NOT NULL default '0',
    AVSCV2 varchar(50) default NULL,
    AddressResult varchar(20) default NULL,
    PostCodeResult varchar(20) default NULL,
    CV2Result varchar(20) default NULL,
    GiftAid tinyint default NULL,
    ThreeDSecureStatus varchar(50) default NULL,
    CAVV varchar(40) default NULL,
    RelatedVendorTxCode varchar(50) default NULL,
    Status varchar(255) default NULL, 
    AddressStatus varchar(20) default NULL,
	PayerStatus varchar(20) default NULL,
	CardType varchar(15) default NULL,
	Last4Digits varchar(4) default NULL,
	LastUpdated TIMESTAMP DEFAULT CURRENT_TIMESTAMP 
	                      ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY  (VendorTxCode)
) ENGINE=MyISAM;

CREATE TABLE tblProducts (
    ProductId bigint(20) unsigned NOT NULL auto_increment,
    Price decimal(10,2) NOT NULL default '0',
    Description varchar(200) default NULL,
    PRIMARY KEY  (ProductId)
) ENGINE=MyISAM;

CREATE TABLE tblOrderProducts (
    VendorTxCode varchar(50) NOT NULL,
    ProductId bigint(20) unsigned NOT NULL,
    Price decimal(10,2) NOT NULL default '0',
    Quantity smallint default '0',
    PRIMARY KEY  (VendorTxCode,ProductId)
) ENGINE=MyISAM;

INSERT INTO tblProducts(Price,Description) VALUES ('9.95','Shaolin Soccer');
INSERT INTO tblProducts(Price,Description) VALUES ('10.99','Batman - The Dark Knight');
INSERT INTO tblProducts(Price,Description) VALUES ('8.75','IronMan');
