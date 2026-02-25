-- ============================================================
--  Migration: add superadmin role + Admin Users table
-- ============================================================
USE melody_masters_db;

-- 1. Extend the role ENUM to include 'superadmin'
ALTER TABLE users
  MODIFY COLUMN role ENUM('customer','staff','admin','superadmin') DEFAULT 'customer';
