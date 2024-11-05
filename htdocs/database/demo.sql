-- fixtures of already existing tables
DROP DATABASE IF EXISTS herbarinput_log;
CREATE DATABASE herbarinput_log;
GRANT ALL PRIVILEGES ON `herbarinput_log`.* TO `jacq`@`%`;

USE herbarinput_log;

CREATE TABLE `tbl_herbardb_users` (
                                      `userID` int(11) NOT NULL,
                                      `groupID` int(11) NOT NULL DEFAULT 0,
                                      `source_id` int(11) NOT NULL DEFAULT 0,
                                      `use_access` tinyint(4) DEFAULT NULL,
                                      `active` tinyint(4) NOT NULL DEFAULT 1,
                                      `username` varchar(255) DEFAULT NULL,
                                      `firstname` varchar(255) NOT NULL DEFAULT '',
                                      `surname` varchar(255) NOT NULL DEFAULT '',
                                      `emailadress` varchar(255) NOT NULL DEFAULT '',
                                      `phone` varchar(255) DEFAULT NULL,
                                      `mobile` varchar(255) DEFAULT NULL,
                                      `editFamily` varchar(255) DEFAULT NULL,
                                      `login` datetime DEFAULT NULL,
                                      `timestamp` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
                                      `iv` varchar(255) DEFAULT NULL,
                                      `secret` varchar(255) DEFAULT NULL,
                                      `pw` varchar(255) DEFAULT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

INSERT INTO `tbl_herbardb_users` (`userID`, `groupID`, `source_id`, `use_access`, `active`, `username`, `firstname`, `surname`, `emailadress`, `phone`, `mobile`, `editFamily`, `login`, `timestamp`, `iv`, `secret`, `pw`) VALUES(2, 12, 6, 0, 1, 'hrainer', 'heimo', 'rainer', '', NULL, NULL, NULL, NULL, '2023-09-20 05:09:02', 'm2TEVqkdEmmsvN3CivmMRdY0ofi+UjxPv/SP5aGnIjw=', '9STNZPv8oBmQoMyDZdpQqm36lLsa', '$2y$10$knT5qs/X525ZL6vTDHWJUOdYgMfSkeXLT4CViVlWPv.E9tmznvngi');
INSERT INTO `tbl_herbardb_users` (`userID`, `groupID`, `source_id`, `use_access`, `active`, `username`, `firstname`, `surname`, `emailadress`, `phone`, `mobile`, `editFamily`, `login`, `timestamp`, `iv`, `secret`, `pw`) VALUES(26, 12, 1, 0, 1, 'joschach', 'Johannes', 'Schachner', '', NULL, NULL, NULL, NULL, '2024-04-23 12:22:01', 'v3eeWG7Iw35x4xAenzA2fx3wXlTBZuXeM64q7SBL4qg=', 'hosjjhMTGpb2pEVOXbDwyoMC95xe', '$2y$10$j8NL/w0Ces2Ut6.xkg8joOemsF7/zLrdvLMGMFgz7W4iuZYFfydya');
INSERT INTO `tbl_herbardb_users` (`userID`, `groupID`, `source_id`, `use_access`, `active`, `username`, `firstname`, `surname`, `emailadress`, `phone`, `mobile`, `editFamily`, `login`, `timestamp`, `iv`, `secret`, `pw`) VALUES(164, 12, 29, 0, 1, 'dröpert', 'dominik', 'röpert', '', NULL, NULL, NULL, NULL, '2023-09-08 14:20:31', '5jwrHibFyMgBD+pSM+pnZBo/qfGJjO5YeSnuny5VNcM=', '3AwJrTxipGb0cX2ExaQ7udsdlbUL', '$2y$10$8s8/vwyQ2o4ZHFNRyDsoZesAfDie9a9GtkbK9PQxcdPoXDOCF4xyS');
INSERT INTO `tbl_herbardb_users` (`userID`, `groupID`, `source_id`, `use_access`, `active`, `username`, `firstname`, `surname`, `emailadress`, `phone`, `mobile`, `editFamily`, `login`, `timestamp`, `iv`, `secret`, `pw`) VALUES(396, 15, 41, 0, 1, 'zvaněček', 'zdeněk', 'vaněček', 'zdeněk.vaněček@unipr.cz', NULL, NULL, NULL, NULL, '2022-02-15 07:53:56', NULL, NULL, '$2y$10$ALxNasTmemeNjyjWh36qS.Lq7nk9WxBCQtqLBvtu6I12PbW9X9j9e');
INSERT INTO `tbl_herbardb_users` (`userID`, `groupID`, `source_id`, `use_access`, `active`, `username`, `firstname`, `surname`, `emailadress`, `phone`, `mobile`, `editFamily`, `login`, `timestamp`, `iv`, `secret`, `pw`) VALUES(217, 1, 41, 0, 1, 'pmráz', 'patrik', 'mráz', '', NULL, NULL, NULL, NULL, '2021-08-10 17:29:32', 'L/2Gwkjb+66paymF8Doc5RKVeUcFWXgG7DbrN+QzFYY=', 'BsWVHQ86r+tS741gnGYDcpd7vr8W', '$2y$10$oDBRPPxmCkRhGAAMiLqBle42X1VLvVhRpA35NhPwyoHwxdo7f4Ul.');
INSERT INTO `tbl_herbardb_users` (`userID`, `groupID`, `source_id`, `use_access`, `active`, `username`, `firstname`, `surname`, `emailadress`, `phone`, `mobile`, `editFamily`, `login`, `timestamp`, `iv`, `secret`, `pw`) VALUES(123, 15, 1, 0, 1, 'mhofbauer', 'markus', 'hofbauer', '', NULL, NULL, NULL, NULL, '2021-09-13 19:01:22', 'G3t/Wl+4+4MmCjSacj8OkkxZ82kd4nU7KGd9tds34HQ=', 'tsmn/mOWUS7Zc2x4JZYZI9zNMX6U', '$2y$10$0c4RGlqAUIt2YK7j6OUms.0IPTdVXBC0gpmbDzxamM8Zs.NktqYNe');
INSERT INTO `tbl_herbardb_users` (`userID`, `groupID`, `source_id`, `use_access`, `active`, `username`, `firstname`, `surname`, `emailadress`, `phone`, `mobile`, `editFamily`, `login`, `timestamp`, `iv`, `secret`, `pw`) VALUES(500, 1, 41, 0, 1, 'novotp', 'Petr', 'Novotný', 'novotp@natur.cuni.cz', NULL, NULL, NULL, NULL, '2021-08-10 17:29:32', '', '', '$2y$13$Ikxpb7pqql6qH4.9gBsCremPjefWa84E0l7KMMSzswyy6w9ki/UeC');
-- to hash a password use "php bin/console security:hash-password"

ALTER TABLE `tbl_herbardb_users`
    ADD PRIMARY KEY (`userID`),
  ADD KEY `groupID` (`groupID`),
  ADD KEY `source_id` (`source_id`),
  ADD KEY `username` (`username`);

CREATE TABLE `tbl_herbardb_groups` (
                                       `groupID` int(11) NOT NULL,
                                       `group_name` varchar(50) NOT NULL DEFAULT '""',
                                       `group_description` varchar(255) NOT NULL DEFAULT '""',
                                       `species` tinyint(4) NOT NULL DEFAULT 0,
                                       `author` tinyint(4) NOT NULL DEFAULT 0,
                                       `epithet` tinyint(4) NOT NULL DEFAULT 0,
                                       `genera` tinyint(4) NOT NULL DEFAULT 0,
                                       `family` tinyint(4) NOT NULL DEFAULT 0,
                                       `lit` tinyint(4) NOT NULL DEFAULT 0,
                                       `litAuthor` tinyint(4) NOT NULL DEFAULT 0,
                                       `litPer` tinyint(4) NOT NULL DEFAULT 0,
                                       `litPub` tinyint(4) NOT NULL DEFAULT 0,
                                       `index` tinyint(4) NOT NULL DEFAULT 0,
                                       `type` tinyint(4) NOT NULL DEFAULT 0,
                                       `specimensTypes` tinyint(4) NOT NULL DEFAULT 0,
                                       `collIns` tinyint(4) NOT NULL DEFAULT 0,
                                       `collUpd` tinyint(4) NOT NULL DEFAULT 0,
                                       `seriesIns` tinyint(4) NOT NULL DEFAULT 0,
                                       `seriesUpd` tinyint(4) NOT NULL DEFAULT 0,
                                       `specim` tinyint(4) NOT NULL DEFAULT 0,
                                       `dt` tinyint(4) NOT NULL DEFAULT 0,
                                       `chorol` tinyint(4) NOT NULL DEFAULT 0,
                                       `btnTax` tinyint(4) NOT NULL DEFAULT 0,
                                       `btnLit` tinyint(4) NOT NULL DEFAULT 0,
                                       `btnSpc` tinyint(4) NOT NULL DEFAULT 0,
                                       `btnObs` tinyint(4) NOT NULL DEFAULT 0,
                                       `btnImg` tinyint(4) NOT NULL DEFAULT 0,
                                       `btnNom` tinyint(4) NOT NULL DEFAULT 0,
                                       `btnImport` tinyint(4) NOT NULL DEFAULT 0,
                                       `linkTaxon` tinyint(4) NOT NULL DEFAULT 0,
                                       `batch` tinyint(4) NOT NULL DEFAULT 0,
                                       `batchAdmin` tinyint(4) NOT NULL DEFAULT 0,
                                       `admin` tinyint(4) NOT NULL DEFAULT 0,
                                       `editor` tinyint(4) NOT NULL DEFAULT 0,
                                       `commonnameUpdate` int(4) NOT NULL,
                                       `commonnameInsert` int(4) NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

INSERT INTO `tbl_herbardb_groups` VALUES(1, 'editors', 'editors can freely edit all forms and base tabels', 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 0, 1, 1, 1, 1, 1, 1, 0, 1, 0, 0, 0, 1, 1, 1);
INSERT INTO `tbl_herbardb_groups` VALUES(2, 'input', 'users who utilize authority files and enter specimen data', 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 1, 0, 1, 0, 1, 0, 0, 0, 0, 1, 0, 1, 0, 0, 1, 0, 0, 0, 0, 0, 0);
INSERT INTO `tbl_herbardb_groups` VALUES(3, 'index editors', 'taxon editors can edit all taxonomic tables / literature citations / typecollections', 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 0, 0, 0, 0, 0, 1, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0);
INSERT INTO `tbl_herbardb_groups` VALUES(4, 'herbarium users', 'can only edit DT numbers corresponding to genus', 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 1, 0, 1, 0, 1, 0, 0, 0, 0, 1, 0, 0, 0, 0, 0, 0);
INSERT INTO `tbl_herbardb_groups` VALUES(5, 'literature editors', 'can only edit literature citations', 0, 0, 0, 0, 0, 1, 1, 1, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0);
INSERT INTO `tbl_herbardb_groups` VALUES(6, 'taxon editors', 'taxon editors can edit all taxonomic tables and asign protologue citations and typecollections to names', 1, 1, 1, 1, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 1, 1, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0);
INSERT INTO `tbl_herbardb_groups` VALUES(7, 'review', 'account for reviewing purposes', 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 1, 1, 1, 1, 1, 1, 0, 1, 1, 0, 0, 0, 1, 1);
INSERT INTO `tbl_herbardb_groups` VALUES(8, 'specimens & literature & new taxa', 'users who utilize authority files and enter specimen data, literature and new taxon names', 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 0, 1, 0, 1, 0, 0, 1, 1, 1, 1, 1, 1, 0, 1, 0, 0, 0, 0, 0, 0);
INSERT INTO `tbl_herbardb_groups` VALUES(9, 'batch & loan administrators', 'administrates batches and loans', 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 1, 0, 0, 0, 0, 1, 1, 0, 0, 0, 0, 0);
INSERT INTO `tbl_herbardb_groups` VALUES(10, 'observations', 'users who enter observation data', 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 1, 0, 0, 0, 1, 0, 0, 0, 0, 0, 1, 0, 0, 0, 1, 0, 0, 0, 0, 0, 0);
INSERT INTO `tbl_herbardb_groups` VALUES(11, 'administrators', 'administer users and groups', 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 1, 0, 0, 0);
INSERT INTO `tbl_herbardb_groups` VALUES(12, 'general administrators', 'general administration users', 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1);
INSERT INTO `tbl_herbardb_groups` VALUES(13, 'editors & batches', 'can edit all forms and base tabels and aggregate specimens for batches', 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 0, 1, 1, 1, 1, 1, 1, 0, 1, 1, 0, 0, 1, 0, 0);
INSERT INTO `tbl_herbardb_groups` VALUES(14, 'specimen & batch', 'users who utilize authority files and enter specimen data', 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 1, 0, 1, 0, 1, 0, 0, 0, 0, 1, 0, 1, 1, 0, 1, 1, 0, 0, 0, 0, 0);
INSERT INTO `tbl_herbardb_groups` VALUES(16, 'editors & import & batch', 'can freely edit all forms and base tabels, import external data and aggregate specimens for batches', 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 0, 1, 1, 1, 1, 1, 1, 1, 1, 1, 0, 0, 1, 0, 0);
INSERT INTO `tbl_herbardb_groups` VALUES(15, 'editors & import', 'can freely edit all forms and base tabels and import external data', 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 0, 1, 1, 1, 1, 1, 1, 1, 1, 0, 0, 0, 1, 0, 0);
INSERT INTO `tbl_herbardb_groups` VALUES(17, 'specimens & literature editors', 'can edit specimens, literature citations and asign protologue citations to names', 0, 0, 0, 0, 0, 1, 1, 1, 1, 1, 1, 0, 1, 0, 1, 0, 1, 0, 0, 1, 1, 1, 1, 1, 1, 0, 1, 0, 0, 0, 0, 0, 0);
INSERT INTO `tbl_herbardb_groups` VALUES(18, 'specimen & observations', 'users who utilize authority files and enter specimen data', 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 1, 0, 1, 0, 1, 0, 0, 0, 0, 1, 1, 1, 0, 1, 1, 0, 0, 0, 0, 0, 0);
INSERT INTO `tbl_herbardb_groups` VALUES(22, 'specimens & common names & literature', 'users who utilize authority files and enter specimen data and common names', 0, 0, 0, 0, 0, 1, 1, 1, 0, 1, 0, 0, 1, 0, 1, 0, 1, 0, 0, 1, 1, 1, 1, 1, 0, 0, 1, 0, 0, 0, 0, 1, 1);
INSERT INTO `tbl_herbardb_groups` VALUES(23, 'review_taxa', 'account for reviewing purposes', 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 1, 0, 0, 0, 0, 0, 0, 1, 0, 0, 0, 0, 0, 0);
INSERT INTO `tbl_herbardb_groups` VALUES(24, 'specimen & import', 'users who utilize authority files and enter specimen data and check imported specimens', 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 1, 0, 1, 0, 1, 0, 0, 0, 0, 1, 0, 1, 0, 1, 1, 0, 0, 0, 0, 0, 0);
INSERT INTO `tbl_herbardb_groups` VALUES(25, 'specimens & literature editors & typification', 'can edit specimens, literature citations,  asign protologue citations to names and add typification information on to specimens', 0, 0, 0, 0, 0, 1, 1, 1, 1, 1, 1, 1, 1, 0, 1, 0, 1, 0, 0, 1, 1, 1, 1, 1, 1, 0, 1, 0, 0, 0, 0, 0, 0);
INSERT INTO `tbl_herbardb_groups` VALUES(26, 'specimens & typification & batches', 'can edit specimens, add typification information on to specimens and administrate batches', 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 1, 1, 0, 1, 0, 1, 0, 0, 1, 0, 1, 1, 1, 1, 0, 1, 1, 0, 0, 0, 0, 0);
INSERT INTO `tbl_herbardb_groups` VALUES(27, 'specimens & literature & typification & batches', 'can edit specimens, literature citations,  asign protologue citations to names, add typification information on to specimens and compose batches', 0, 0, 0, 0, 0, 1, 1, 1, 1, 1, 1, 1, 1, 0, 1, 0, 1, 0, 0, 1, 1, 1, 1, 1, 1, 0, 1, 1, 0, 0, 0, 0, 0);
INSERT INTO `tbl_herbardb_groups` VALUES(28, 'editors & chorology & import', 'editors can freely edit all forms and base tabels', 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 0, 0, 0, 1, 1, 1);

ALTER TABLE `tbl_herbardb_groups`
    ADD PRIMARY KEY (`groupID`);

ALTER TABLE `tbl_herbardb_groups`
    MODIFY `groupID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=29;


-- OAuth2 bundle
DROP DATABASE IF EXISTS jacq;
CREATE DATABASE jacq;
USE jacq;

CREATE TABLE `oauth2_access_token` (
  `identifier` char(80) NOT NULL,
  `client` varchar(32) NOT NULL,
  `expiry` datetime NOT NULL COMMENT '(DC2Type:datetime_immutable)',
  `user_identifier` varchar(128) DEFAULT NULL,
  `scopes` text DEFAULT NULL COMMENT '(DC2Type:oauth2_scope)',
  `revoked` tinyint(1) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `oauth2_authorization_code` (
  `identifier` char(80) NOT NULL,
  `client` varchar(32) NOT NULL,
  `expiry` datetime NOT NULL COMMENT '(DC2Type:datetime_immutable)',
  `user_identifier` varchar(128) DEFAULT NULL,
  `scopes` text DEFAULT NULL COMMENT '(DC2Type:oauth2_scope)',
  `revoked` tinyint(1) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `oauth2_client` (
  `identifier` varchar(32) NOT NULL,
  `name` varchar(128) NOT NULL,
  `secret` varchar(128) DEFAULT NULL,
  `redirect_uris` text DEFAULT NULL COMMENT '(DC2Type:oauth2_redirect_uri)',
  `grants` text DEFAULT NULL COMMENT '(DC2Type:oauth2_grant)',
  `scopes` text DEFAULT NULL COMMENT '(DC2Type:oauth2_scope)',
  `active` tinyint(1) NOT NULL,
  `allow_plain_text_pkce` tinyint(1) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `oauth2_client` (`identifier`, `name`, `secret`, `redirect_uris`, `grants`, `scopes`, `active`, `allow_plain_text_pkce`) VALUES
('testclient', 'Test Client', 'testpass', 'http://localhost:8080/callback', 'refresh_token authorization_code', 'profile specimen_read speciment_write', 1, 0);

CREATE TABLE `oauth2_refresh_token` (
  `identifier` char(80) NOT NULL,
  `access_token` char(80) DEFAULT NULL,
  `expiry` datetime NOT NULL COMMENT '(DC2Type:datetime_immutable)',
  `revoked` tinyint(1) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `oauth2_user_consent` (
  `id` int(11) NOT NULL,
  `client_id` varchar(32) NOT NULL,
  `user_id` int(11) NOT NULL,
  `created` datetime NOT NULL COMMENT '(DC2Type:datetime_immutable)',
  `expires` datetime DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)',
  `scopes` longtext DEFAULT NULL COMMENT '(DC2Type:simple_array)',
  `ip_address` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

ALTER TABLE `oauth2_access_token`
  ADD PRIMARY KEY (`identifier`),
  ADD KEY `IDX_454D9673C7440455` (`client`);

ALTER TABLE `oauth2_authorization_code`
  ADD PRIMARY KEY (`identifier`),
  ADD KEY `IDX_509FEF5FC7440455` (`client`);

ALTER TABLE `oauth2_client`
  ADD PRIMARY KEY (`identifier`);

ALTER TABLE `oauth2_refresh_token`
  ADD PRIMARY KEY (`identifier`),
  ADD KEY `IDX_4DD90732B6A2DD68` (`access_token`);

ALTER TABLE `oauth2_user_consent`
  ADD PRIMARY KEY (`id`),
  ADD KEY `IDX_C8F05D0119EB6921` (`client_id`),
  ADD KEY `IDX_C8F05D01A76ED395` (`user_id`);

ALTER TABLE `oauth2_user_consent`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

ALTER TABLE `oauth2_access_token`
  ADD CONSTRAINT `FK_454D9673C7440455` FOREIGN KEY (`client`) REFERENCES `oauth2_client` (`identifier`) ON DELETE CASCADE;

ALTER TABLE `oauth2_authorization_code`
  ADD CONSTRAINT `FK_509FEF5FC7440455` FOREIGN KEY (`client`) REFERENCES `oauth2_client` (`identifier`) ON DELETE CASCADE;

ALTER TABLE `oauth2_refresh_token`
  ADD CONSTRAINT `FK_4DD90732B6A2DD68` FOREIGN KEY (`access_token`) REFERENCES `oauth2_access_token` (`identifier`) ON DELETE SET NULL;

ALTER TABLE `oauth2_user_consent`
  ADD CONSTRAINT `FK_C8F05D0119EB6921` FOREIGN KEY (`client_id`) REFERENCES `oauth2_client` (`identifier`);
-- JACQ uses MyISAM :(
-- ALTER TABLE `oauth2_user_consent` ADD CONSTRAINT `FK_C8F05D01A76ED395` FOREIGN KEY (`user_id`) REFERENCES`herbarinput_log`.`tbl_herbardb_users` (`userID`);
