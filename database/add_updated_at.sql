USE restaurant_ordering;

-- Add updated_at column to orders table
ALTER TABLE `orders` 
ADD COLUMN `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP 
AFTER `completed_at`;

-- Set initial values for updated_at based on created_at
UPDATE `orders` SET `updated_at` = `created_at` WHERE `updated_at` IS NULL; 