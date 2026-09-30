-- Adds the new stationery category list (additive -- existing categories
-- are left in place, no products are recategorized) and seeds 5 test
-- products, each properly linked to a supplier via
-- supplier_products.store_product_id (the same FK the product_proposals
-- approval flow creates, just written directly here since this is seed
-- data rather than a live proposal walkthrough).

INSERT IGNORE INTO categories (name, description, is_active) VALUES
    ('Papers', 'Bond paper, construction paper, and other paper stock', 1),
    ('Pens', 'Ballpoint, gel, and other pens', 1),
    ('Folders', 'Document folders and envelopes', 1),
    ('Notebooks', 'Notebooks, pads, and composition books', 1),
    ('Pencils', 'Wooden and mechanical pencils', 1),
    ('Sharpeners', 'Pencil sharpeners', 1),
    ('Erasers', 'Erasers', 1),
    ('Rulers', 'Rulers and other measuring tools', 1),
    ('Scissors', 'Scissors', 1),
    ('Markers', 'Permanent and whiteboard markers', 1),
    ('Highlighters', 'Highlighters', 1),
    ('Glues', 'Glue sticks and liquid glue', 1),
    ('Tapes', 'Adhesive and packing tape', 1);

-- Each product is sold in bulk (one pack = one sellable unit, per the
-- "pack is the whole unit" decision) -- stock_quantity below counts packs,
-- not individual sheets/pieces inside them.

INSERT INTO products (barcode, name, description, category_id, price, cost, stock_quantity, reorder_level, is_active)
VALUES
    ('STA-PPR-001', 'Bond Paper (Stack of 500)', 'Short bond paper, substance 20, 500 sheets per stack.',
        (SELECT id FROM categories WHERE name = 'Papers'), 220.00, 165.00, 30, 8, 1),
    ('STA-PEN-001', 'Ballpoint Pen (Box of 12)', 'Black ballpoint pens, medium tip, 12 pens per box.',
        (SELECT id FROM categories WHERE name = 'Pens'), 96.00, 60.00, 40, 10, 1),
    ('STA-FLD-001', 'Long Folder (Pack of 20)', 'Long (legal-size) paper folders, assorted colors, 20 per pack.',
        (SELECT id FROM categories WHERE name = 'Folders'), 150.00, 105.00, 25, 6, 1),
    ('STA-NTB-001', 'Spiral Notebook (Pack of 10)', '80-leaf spiral notebooks, 10 per pack.',
        (SELECT id FROM categories WHERE name = 'Notebooks'), 340.00, 260.00, 20, 5, 1),
    ('STA-PNC-001', 'Wooden Pencil (Box of 12)', 'No. 2 wooden pencils, 12 per box.',
        (SELECT id FROM categories WHERE name = 'Pencils'), 60.00, 36.00, 35, 10, 1);

-- Supplier assignment (picked from the two active suppliers on file):
--   Sample Supplier Inc.        -> Papers, Folders, Pencils
--   Northgate Office Supplies   -> Pens, Notebooks
INSERT INTO supplier_products (supplier_id, store_product_id, name, description, price, quantity, is_active)
VALUES
    ((SELECT id FROM suppliers WHERE company_name = 'Sample Supplier Inc.'),
        (SELECT id FROM products WHERE barcode = 'STA-PPR-001'),
        'Bond Paper (Stack of 500)', 'Short bond paper, substance 20, 500 sheets per stack.', 165.00, 120, 1),
    ((SELECT id FROM suppliers WHERE company_name = 'Northgate Office Supplies'),
        (SELECT id FROM products WHERE barcode = 'STA-PEN-001'),
        'Ballpoint Pen (Box of 12)', 'Black ballpoint pens, medium tip, 12 pens per box.', 60.00, 150, 1),
    ((SELECT id FROM suppliers WHERE company_name = 'Sample Supplier Inc.'),
        (SELECT id FROM products WHERE barcode = 'STA-FLD-001'),
        'Long Folder (Pack of 20)', 'Long (legal-size) paper folders, assorted colors, 20 per pack.', 105.00, 90, 1),
    ((SELECT id FROM suppliers WHERE company_name = 'Northgate Office Supplies'),
        (SELECT id FROM products WHERE barcode = 'STA-NTB-001'),
        'Spiral Notebook (Pack of 10)', '80-leaf spiral notebooks, 10 per pack.', 260.00, 70, 1),
    ((SELECT id FROM suppliers WHERE company_name = 'Sample Supplier Inc.'),
        (SELECT id FROM products WHERE barcode = 'STA-PNC-001'),
        'Wooden Pencil (Box of 12)', 'No. 2 wooden pencils, 12 per box.', 36.00, 130, 1);
