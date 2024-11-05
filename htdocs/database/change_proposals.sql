USE herbarinput_log;
ALTER TABLE `tbl_herbardb_users` DROP `secret`; -- unused column
ALTER TABLE `tbl_herbardb_users` DROP `iv`; -- probably unused column

