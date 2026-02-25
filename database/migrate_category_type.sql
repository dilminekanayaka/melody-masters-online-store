-- Add 'type' to categories table to classify each category as physical or digital
ALTER TABLE categories
  ADD COLUMN IF NOT EXISTS `type` ENUM('physical','digital') NOT NULL DEFAULT 'physical';
