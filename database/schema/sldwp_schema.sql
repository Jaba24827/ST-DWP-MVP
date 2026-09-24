-- =====================================================================
--  SL-DWP — St. Luke Foundation Digital Workplace Platform
--  MySQL 8 / MariaDB 10.6 schema + synthetic seed data
--  Data tier of the three-tier architecture.
--
--  Conventions
--   * utf8mb4 throughout (Haitian Creole and French accents).
--   * InnoDB with real foreign keys — referential integrity is enforced
--     by the database, not only by the application.
--   * No plaintext secrets. password_hash holds a bcrypt digest;
--     mfa_codes.code_hash holds a hash of the one-time code;
--     users.phone_encrypted holds Laravel-encrypted ciphertext and
--     phone_last2 holds only the digits needed to render a mask.
--   * Audit rows are append-only: no UPDATE or DELETE grant is issued
--     to the application database account.
-- =====================================================================

CREATE DATABASE IF NOT EXISTS sldwp
  CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE sldwp;

SET FOREIGN_KEY_CHECKS = 0;
DROP TABLE IF EXISTS audit_logs, mfa_codes, password_histories, login_attempts,
  document_downloads, documents, announcements, permission_role, permissions,
  users, roles, sectors, sites, sessions;
SET FOREIGN_KEY_CHECKS = 1;

-- ---------------------------------------------------------------- org
CREATE TABLE sectors (
  id            TINYINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name          VARCHAR(60)  NOT NULL UNIQUE,
  description   VARCHAR(190) NULL,
  created_at    TIMESTAMP    NULL,
  updated_at    TIMESTAMP    NULL
) ENGINE=InnoDB;

CREATE TABLE sites (
  id            SMALLINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  sector_id     TINYINT UNSIGNED NOT NULL,
  name          VARCHAR(120) NOT NULL,
  commune       VARCHAR(80)  NULL,
  created_at    TIMESTAMP    NULL,
  updated_at    TIMESTAMP    NULL,
  CONSTRAINT fk_sites_sector FOREIGN KEY (sector_id) REFERENCES sectors(id) ON DELETE CASCADE,
  INDEX idx_sites_sector (sector_id)
) ENGINE=InnoDB;

-- --------------------------------------------------------------- rbac
CREATE TABLE roles (
  id            TINYINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name          VARCHAR(50)  NOT NULL UNIQUE,
  description   VARCHAR(190) NULL,
  is_system     TINYINT(1)   NOT NULL DEFAULT 0,  -- system roles cannot be deleted
  created_at    TIMESTAMP    NULL,
  updated_at    TIMESTAMP    NULL
) ENGINE=InnoDB;

CREATE TABLE permissions (
  id            SMALLINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `key`         VARCHAR(60)  NOT NULL UNIQUE,     -- documents.view.restricted
  description   VARCHAR(190) NOT NULL,
  `group`       VARCHAR(40)  NOT NULL DEFAULT 'general',
  created_at    TIMESTAMP    NULL,
  updated_at    TIMESTAMP    NULL
) ENGINE=InnoDB;

CREATE TABLE permission_role (
  role_id       TINYINT UNSIGNED  NOT NULL,
  permission_id SMALLINT UNSIGNED NOT NULL,
  PRIMARY KEY (role_id, permission_id),
  CONSTRAINT fk_pr_role FOREIGN KEY (role_id)       REFERENCES roles(id)       ON DELETE CASCADE,
  CONSTRAINT fk_pr_perm FOREIGN KEY (permission_id) REFERENCES permissions(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- -------------------------------------------------------------- users
CREATE TABLE users (
  id                  BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name                VARCHAR(120) NOT NULL,
  email               VARCHAR(190) NOT NULL UNIQUE,
  email_verified_at   TIMESTAMP    NULL,
  password            VARCHAR(255) NOT NULL,          -- bcrypt, cost 12
  role_id             TINYINT UNSIGNED NULL,
  sector_id           TINYINT UNSIGNED NULL,
  site_id             SMALLINT UNSIGNED NULL,
  position            VARCHAR(90)  NULL,
  phone_encrypted     TEXT         NULL,              -- AES-256 via Laravel Crypt
  phone_last2         CHAR(2)      NULL,              -- for the masked display only
  mfa_channel         ENUM('email','sms') NOT NULL DEFAULT 'email',
  mfa_enabled         TINYINT(1)   NOT NULL DEFAULT 1,
  password_changed_at TIMESTAMP    NULL,
  must_change_password TINYINT(1)  NOT NULL DEFAULT 0,
  failed_logins       TINYINT UNSIGNED NOT NULL DEFAULT 0,
  locked_until        TIMESTAMP    NULL,
  is_active           TINYINT(1)   NOT NULL DEFAULT 1,
  remember_token      VARCHAR(100) NULL,
  created_at          TIMESTAMP    NULL,
  updated_at          TIMESTAMP    NULL,
  CONSTRAINT fk_users_role   FOREIGN KEY (role_id)   REFERENCES roles(id)   ON DELETE SET NULL,
  CONSTRAINT fk_users_sector FOREIGN KEY (sector_id) REFERENCES sectors(id) ON DELETE SET NULL,
  CONSTRAINT fk_users_site   FOREIGN KEY (site_id)   REFERENCES sites(id)   ON DELETE SET NULL,
  INDEX idx_users_role (role_id),
  INDEX idx_users_active_sector (is_active, sector_id)
) ENGINE=InnoDB;

-- Reuse is refused by comparing against the last five digests.
CREATE TABLE password_histories (
  id            BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id       BIGINT UNSIGNED NOT NULL,
  password_hash VARCHAR(255)    NOT NULL,
  created_at    TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_ph_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  INDEX idx_ph_user (user_id, created_at)
) ENGINE=InnoDB;

-- ----------------------------------------------------------------- mfa
CREATE TABLE mfa_codes (
  id            BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id       BIGINT UNSIGNED NOT NULL,
  code_hash     VARCHAR(255)    NOT NULL,   -- never the code itself
  channel       ENUM('email','sms') NOT NULL,
  destination   VARCHAR(190)    NOT NULL,   -- masked at render time
  attempts      TINYINT UNSIGNED NOT NULL DEFAULT 0,
  consumed_at   TIMESTAMP       NULL,
  expires_at    TIMESTAMP       NOT NULL,
  ip            VARCHAR(45)     NULL,
  created_at    TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_mfa_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  INDEX idx_mfa_user_live (user_id, consumed_at, expires_at)
) ENGINE=InnoDB;

CREATE TABLE login_attempts (
  id            BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  email         VARCHAR(190) NOT NULL,
  ip            VARCHAR(45)  NULL,
  successful    TINYINT(1)   NOT NULL DEFAULT 0,
  stage         ENUM('password','mfa') NOT NULL DEFAULT 'password',
  created_at    TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_la_email_time (email, created_at)
) ENGINE=InnoDB;

-- ------------------------------------------------------- content tier
CREATE TABLE announcements (
  id            BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  title         VARCHAR(160) NOT NULL,
  body          TEXT         NOT NULL,
  audience      VARCHAR(60)  NOT NULL DEFAULT 'all',   -- 'all' or a sector name
  author_id     BIGINT UNSIGNED NOT NULL,
  published_at  TIMESTAMP    NULL,
  expires_at    TIMESTAMP    NULL,
  created_at    TIMESTAMP    NULL,
  updated_at    TIMESTAMP    NULL,
  CONSTRAINT fk_ann_author FOREIGN KEY (author_id) REFERENCES users(id) ON DELETE CASCADE,
  INDEX idx_ann_audience (audience, published_at),
  FULLTEXT KEY ft_ann (title, body)
) ENGINE=InnoDB;

CREATE TABLE documents (
  id             BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  title          VARCHAR(160) NOT NULL,
  stored_path    VARCHAR(255) NOT NULL,   -- outside the web root; never rendered
  original_name  VARCHAR(190) NOT NULL,
  mime           VARCHAR(100) NOT NULL,
  size_bytes     BIGINT UNSIGNED NOT NULL,
  checksum_sha256 CHAR(64)    NULL,       -- integrity check on download
  sector_id      TINYINT UNSIGNED NULL,
  classification ENUM('public','internal','restricted') NOT NULL DEFAULT 'internal',
  owner_id       BIGINT UNSIGNED NOT NULL,
  created_at     TIMESTAMP    NULL,
  updated_at     TIMESTAMP    NULL,
  deleted_at     TIMESTAMP    NULL,       -- soft delete keeps the audit trail honest
  CONSTRAINT fk_doc_owner  FOREIGN KEY (owner_id)  REFERENCES users(id)   ON DELETE CASCADE,
  CONSTRAINT fk_doc_sector FOREIGN KEY (sector_id) REFERENCES sectors(id) ON DELETE SET NULL,
  INDEX idx_doc_class_sector (classification, sector_id),
  INDEX idx_doc_live (deleted_at, created_at),
  FULLTEXT KEY ft_doc (title)
) ENGINE=InnoDB;

CREATE TABLE document_downloads (
  id            BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  document_id   BIGINT UNSIGNED NOT NULL,
  user_id       BIGINT UNSIGNED NOT NULL,
  ip            VARCHAR(45) NULL,
  created_at    TIMESTAMP   NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_dd_doc  FOREIGN KEY (document_id) REFERENCES documents(id) ON DELETE CASCADE,
  CONSTRAINT fk_dd_user FOREIGN KEY (user_id)     REFERENCES users(id)     ON DELETE CASCADE,
  INDEX idx_dd_doc_time (document_id, created_at)
) ENGINE=InnoDB;

-- --------------------------------------------------------------- audit
CREATE TABLE audit_logs (
  id            BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id       BIGINT UNSIGNED NULL,
  actor         VARCHAR(190) NULL,        -- kept even when the account is removed
  action        VARCHAR(80)  NOT NULL,    -- documents.open, auth.mfa.failed, ...
  target        VARCHAR(190) NULL,
  result        ENUM('allowed','denied') NOT NULL,
  ip            VARCHAR(45)  NULL,
  user_agent    VARCHAR(255) NULL,
  context       JSON         NULL,
  created_at    TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_audit_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
  INDEX idx_audit_action (action, result, created_at),
  INDEX idx_audit_actor (actor, created_at)
) ENGINE=InnoDB;

CREATE TABLE sessions (
  id            VARCHAR(100) PRIMARY KEY,
  user_id       BIGINT UNSIGNED NULL,
  ip_address    VARCHAR(45)  NULL,
  user_agent    TEXT         NULL,
  payload       LONGTEXT     NOT NULL,
  last_activity INT          NOT NULL,
  INDEX idx_sessions_user (user_id),
  INDEX idx_sessions_activity (last_activity)
) ENGINE=InnoDB;

-- =====================================================================
--  Seed — synthetic data only. No operational or clinical records.
--  Password digest below is bcrypt of: Demo!Passw0rd2026
-- =====================================================================
INSERT INTO sectors (name, description) VALUES
  ('Administration','Head office, finance and governance'),
  ('Education','Schools and training centres'),
  ('Health','Hospital, clinics and community health'),
  ('Production','Workshops and the donations warehouse'),
  ('Projects','Community and donor-funded projects');

INSERT INTO sites (sector_id, name, commune) VALUES
  (1,'Head office','Port-au-Prince'),
  (2,'Ecole St. Luc, Tabarre','Tabarre'),
  (2,'Ecole St. Luc, Cite Soleil','Cite Soleil'),
  (3,'Hopital St. Luc','Tabarre'),
  (3,'Clinic, Fontamara','Carrefour'),
  (4,'Production centre','Tabarre'),
  (5,'Field office','Croix-des-Bouquets');

INSERT INTO roles (name, description, is_system) VALUES
  ('Administrator','Full administration of users, roles and content',1),
  ('Sector manager','Publishes notices and uploads documents for a sector',1),
  ('Staff','Reads notices, directory and permitted documents',1),
  ('Auditor','Read-only access including the audit log',1);

INSERT INTO permissions (`key`, description, `group`) VALUES
  ('dashboard.view','Open the dashboard','work'),
  ('directory.view','Search the staff directory','work'),
  ('announcements.view','Read notices','work'),
  ('announcements.publish','Publish and withdraw notices','work'),
  ('documents.view','Open public and internal documents','documents'),
  ('documents.view.restricted','Open restricted documents','documents'),
  ('documents.upload','Upload documents','documents'),
  ('documents.delete','Delete documents','documents'),
  ('users.manage','Create and edit user accounts','administration'),
  ('roles.manage','Change role permissions','administration'),
  ('audit.view','Read the audit log','administration'),
  ('account.security','Manage your own password and MFA','account');

-- Administrator: everything.
INSERT INTO permission_role (role_id, permission_id)
  SELECT 1, id FROM permissions;
-- Sector manager.
INSERT INTO permission_role (role_id, permission_id)
  SELECT 2, id FROM permissions WHERE `key` IN
  ('dashboard.view','directory.view','announcements.view','announcements.publish',
   'documents.view','documents.upload','account.security');
-- Staff.
INSERT INTO permission_role (role_id, permission_id)
  SELECT 3, id FROM permissions WHERE `key` IN
  ('dashboard.view','directory.view','announcements.view','documents.view','account.security');
-- Auditor.
INSERT INTO permission_role (role_id, permission_id)
  SELECT 4, id FROM permissions WHERE `key` IN
  ('dashboard.view','directory.view','announcements.view','documents.view','audit.view','account.security');

INSERT INTO users (name, email, password, role_id, sector_id, site_id, position, phone_last2, mfa_channel, is_active, password_changed_at, created_at, updated_at) VALUES
  ('Jaebets Dorsainvil','j.dorsainvil@stlukehaiti.org','$2y$12$e0NRq5b8sJ5R1mQ3sV1u4uY9xK0oGZ9m2dP7cQkV3oUu1nJ2lVQW6',1,1,1,'IT systems engineer','01','email',1,NOW(),NOW(),NOW()),
  ('Micheline Pierre','m.pierre@stlukehaiti.org','$2y$12$e0NRq5b8sJ5R1mQ3sV1u4uY9xK0oGZ9m2dP7cQkV3oUu1nJ2lVQW6',2,2,2,'Education coordinator','12','sms',1,NOW(),NOW(),NOW()),
  ('Ronald Jean-Baptiste','r.jeanbaptiste@stlukehaiti.org','$2y$12$e0NRq5b8sJ5R1mQ3sV1u4uY9xK0oGZ9m2dP7cQkV3oUu1nJ2lVQW6',3,3,4,'Ward nurse','24','email',1,NOW(),NOW(),NOW()),
  ('Carline Altidor','c.altidor@stlukehaiti.org','$2y$12$e0NRq5b8sJ5R1mQ3sV1u4uY9xK0oGZ9m2dP7cQkV3oUu1nJ2lVQW6',4,1,1,'Internal control officer','30','email',1,NOW(),NOW(),NOW()),
  ('Gerald Moise','g.moise@stlukehaiti.org','$2y$12$e0NRq5b8sJ5R1mQ3sV1u4uY9xK0oGZ9m2dP7cQkV3oUu1nJ2lVQW6',2,4,6,'Workshop supervisor','41','sms',1,NOW(),NOW(),NOW()),
  ('Nadege Charles','n.charles@stlukehaiti.org','$2y$12$e0NRq5b8sJ5R1mQ3sV1u4uY9xK0oGZ9m2dP7cQkV3oUu1nJ2lVQW6',3,2,3,'Teacher','55','email',1,NOW(),NOW(),NOW()),
  ('Frantz Delva','f.delva@stlukehaiti.org','$2y$12$e0NRq5b8sJ5R1mQ3sV1u4uY9xK0oGZ9m2dP7cQkV3oUu1nJ2lVQW6',3,5,7,'Project officer','66','email',1,NOW(),NOW(),NOW()),
  ('Yolette Saint-Fleur','y.saintfleur@stlukehaiti.org','$2y$12$e0NRq5b8sJ5R1mQ3sV1u4uY9xK0oGZ9m2dP7cQkV3oUu1nJ2lVQW6',3,3,5,'Community health agent','78','sms',1,NOW(),NOW(),NOW()),
  ('Wilner Etienne','w.etienne@stlukehaiti.org','$2y$12$e0NRq5b8sJ5R1mQ3sV1u4uY9xK0oGZ9m2dP7cQkV3oUu1nJ2lVQW6',3,4,6,'Quality control technician','82','email',0,NOW(),NOW(),NOW());

INSERT INTO announcements (title, body, audience, author_id, published_at, created_at, updated_at) VALUES
  ('Payroll cut-off moves to the 22nd','Sector managers should submit attendance sheets by the 22nd of each month so head office can close payroll on time.','all',1,NOW(),NOW(),NOW()),
  ('School reopening schedule, Tabarre','Classes resume on Monday. Teachers are asked to confirm availability with the coordinator before Friday.','Education',2,NOW(),NOW(),NOW()),
  ('Generator maintenance, production centre','Power will be interrupted between 06:00 and 09:00 on Saturday for scheduled maintenance.','Production',5,NOW(),NOW(),NOW()),
  ('New document classification rules','Any file holding staff personal details must be uploaded as restricted. Internal remains the default for operational documents.','all',1,NOW(),NOW(),NOW());

INSERT INTO documents (title, stored_path, original_name, mime, size_bytes, sector_id, classification, owner_id, created_at, updated_at) VALUES
  ('Staff handbook 2026','documents/seed/handbook.pdf','Staff handbook 2026.pdf','application/pdf',1887436,1,'public',1,NOW(),NOW()),
  ('Education sector work plan','documents/seed/workplan.docx','Education sector work plan.docx','application/vnd.openxmlformats-officedocument.wordprocessingml.document',655360,2,'internal',2,NOW(),NOW()),
  ('Clinic supply request form','documents/seed/supply.pdf','Clinic supply request form.pdf','application/pdf',215040,3,'public',8,NOW(),NOW()),
  ('Salary scale review','documents/seed/salary.xlsx','Salary scale review.xlsx','application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',97280,1,'restricted',1,NOW(),NOW()),
  ('Production quality log, March','documents/seed/quality.xlsx','Production quality log March.xlsx','application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',317440,4,'internal',9,NOW(),NOW()),
  ('Donor report, Q1','documents/seed/donor.pdf','Donor report Q1.pdf','application/pdf',2516582,5,'restricted',7,NOW(),NOW()),
  ('Site contact list','documents/seed/contacts.pdf','Site contact list.pdf','application/pdf',122880,1,'internal',4,NOW(),NOW()),
  ('Fire safety notice','documents/seed/fire.pdf','Fire safety notice.pdf','application/pdf',90112,3,'public',3,NOW(),NOW());

INSERT INTO audit_logs (user_id, actor, action, target, result, created_at) VALUES
  (2,'m.pierre@stlukehaiti.org','documents.upload','Education sector work plan','allowed',NOW()),
  (3,'r.jeanbaptiste@stlukehaiti.org','documents.open','Salary scale review','denied',NOW()),
  (1,'j.dorsainvil@stlukehaiti.org','auth.mfa.verified','session','allowed',NOW()),
  (NULL,'unknown','auth.login','w.etienne@stlukehaiti.org','denied',NOW());

-- ---------------------------------------------------------------------
--  Least privilege for the application account. Note the absence of
--  DROP, ALTER and of UPDATE/DELETE on audit_logs.
-- ---------------------------------------------------------------------
-- CREATE USER 'sldwp_app'@'localhost' IDENTIFIED BY '<strong password>';
-- GRANT SELECT, INSERT, UPDATE, DELETE ON sldwp.* TO 'sldwp_app'@'localhost';
-- REVOKE UPDATE, DELETE ON sldwp.audit_logs FROM 'sldwp_app'@'localhost';
-- FLUSH PRIVILEGES;
