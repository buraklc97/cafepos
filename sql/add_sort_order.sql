-- SQL script to add sort_order columns for categories and products
ALTER TABLE categories ADD COLUMN sort_order INT NULL AFTER name;
ALTER TABLE products ADD COLUMN sort_order INT NULL AFTER price;
