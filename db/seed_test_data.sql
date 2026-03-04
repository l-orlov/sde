-- Test data for local development only.
-- Run after create_tables.sql.
--
-- To log in as this user:
-- 1. Open the site; if you see the gate screen, use: login adminsantiago, password sde12345
-- 2. Go to Login (or ?page=login). Then use: CUIT 20123456789, password test123

INSERT INTO users (company_name, tax_id, email, phone, password, is_admin)
VALUES (
  'Test Company SRL',
  '20123456789',
  'test@example.com',
  '+5493814567890',
  'test123',
  0
);

SET @user_id = LAST_INSERT_ID();

INSERT INTO companies (user_id, name, tax_id, website, main_activity, organization_type, nuestra_historia, moderation_status)
VALUES (
  @user_id,
  'Test Company SRL',
  '20123456789',
  'https://test-company.example.com',
  'Food & Beverage',
  'SRL',
  'Test company for local development. We produce organic products for export.',
  'approved'
);

SET @company_id = LAST_INSERT_ID();

INSERT INTO company_data (company_id, current_markets, target_markets)
VALUES (
  @company_id,
  '["Argentina", "Uruguay"]',
  '["USA", "European Union", "Brazil"]'
);

INSERT INTO products (company_id, user_id, is_main, type, name, description, activity, tariff_code)
VALUES
  (@company_id, @user_id, 1, 'product', 'Organic Honey', 'Pure organic honey from Santiago del Estero. 500g jar.', 'Beekeeping', '0409.00.00'),
  (@company_id, @user_id, 0, 'product', 'Dulce de Leche', 'Traditional artisanal dulce de leche. 1kg.', 'Dairy', '0404.90.00');
