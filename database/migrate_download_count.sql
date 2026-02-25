-- Add download_count to order_items to track how many times a digital product has been downloaded per purchase
ALTER TABLE order_items
  ADD COLUMN download_count INT DEFAULT 0 AFTER price;
