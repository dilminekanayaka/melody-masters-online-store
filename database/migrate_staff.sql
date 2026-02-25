-- Run this once to add the 'staff' role to the users table
ALTER TABLE users
  MODIFY COLUMN role ENUM('customer','admin','superadmin','staff') NOT NULL DEFAULT 'customer';
