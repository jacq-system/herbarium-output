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
