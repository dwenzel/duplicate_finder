#
# Table structure for tx_duplicatefinder_duplicate_hash
#
CREATE TABLE tx_duplicatefinder_duplicate_hash (
	uid int(11) NOT NULL auto_increment,
	hash varchar(64) DEFAULT '0' NOT NULL,
	fuzzy_hash varchar(148) DEFAULT '0' NOT NULL,
	foreign_uid int(11) DEFAULT '0' NOT NULL,
	foreign_table varchar(64) DEFAULT '' NOT NULL,
	tstamp int(11) unsigned DEFAULT '0' NOT NULL,

	PRIMARY KEY (uid),
	KEY foreign_table (foreign_table),
);
