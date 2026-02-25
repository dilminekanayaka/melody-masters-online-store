-- Migration: add shipping_cost to products table
ALTER TABLE products
  ADD COLUMN shipping_cost DECIMAL(10,2) DEFAULT 0.00 AFTER price;
