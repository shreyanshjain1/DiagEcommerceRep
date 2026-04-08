-- Add FULLTEXT support for product search suggestions.
-- Run this on existing databases.
ALTER TABLE products
  ADD FULLTEXT INDEX ft_products_search (name, sku, brand, short_desc, long_desc);
