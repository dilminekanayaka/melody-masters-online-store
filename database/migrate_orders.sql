-- ============================================================
--  MELODY MASTERS — MIGRATION: add shipping address to orders
--  Run once after melody_masters_db.sql
-- ============================================================

USE melody_masters_db;

ALTER TABLE orders
  ADD COLUMN full_name    VARCHAR(120) DEFAULT NULL AFTER user_id,
  ADD COLUMN email        VARCHAR(120) DEFAULT NULL AFTER full_name,
  ADD COLUMN phone        VARCHAR(30)  DEFAULT NULL AFTER email,
  ADD COLUMN address1     VARCHAR(180) DEFAULT NULL AFTER phone,
  ADD COLUMN address2     VARCHAR(180) DEFAULT NULL AFTER address1,
  ADD COLUMN city         VARCHAR(80)  DEFAULT NULL AFTER address2,
  ADD COLUMN postcode     VARCHAR(20)  DEFAULT NULL AFTER city,
  ADD COLUMN country      VARCHAR(80)  DEFAULT NULL AFTER postcode,
  ADD COLUMN payment_method VARCHAR(40) DEFAULT 'cod' AFTER country,
  ADD COLUMN notes        TEXT         DEFAULT NULL AFTER payment_method;
