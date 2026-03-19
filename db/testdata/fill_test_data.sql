-- Test data for local development: 3 users, 3 companies, and related records.
-- Run after migrations. Safe to run multiple times (INSERT IGNORE / or truncate first).
-- Usage: make fill-testdata (or run this file manually against local DB).

SET NAMES utf8mb4;

-- 3 users (password hash = 'password' via bcrypt)
INSERT IGNORE INTO users (id, company_name, tax_id, email, phone, password, is_admin, created_at, updated_at) VALUES
(1, 'Agro SDE S.A.', '30123456789', 'contacto@agrosde.local', '+5493854123456', '12345', 0, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
(2, 'Miel del Norte', '30234567890', 'info@mieldelnorte.local', '+5493854234567', '12345', 0, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
(3, 'Servicios Logísticos Santiago', '30345678901', 'admin@logistica.local', '+5493854345678', '12345', 1, UNIX_TIMESTAMP(), UNIX_TIMESTAMP());

-- 3 companies (one per user)
INSERT IGNORE INTO companies (id, user_id, name, name_en, tax_id, legal_name, start_date, website, nuestra_historia, organization_type, main_activity, main_activity_en, moderation_status, created_at, updated_at) VALUES
(1, 1, 'Agro SDE S.A.', 'Agro SDE S.A.', '30123456789', 'Agro SDE Sociedad Anónima', '2020-03-15', 'https://agrosde.local', 'Dedicados a la producción y exportación de granos y derivados desde Santiago del Estero.', 'Empresa', 'Producción y comercialización de granos', 'Grain production and trading', 'approved', UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
(2, 2, 'Miel del Norte', 'Honey of the North', '30234567890', 'Miel del Norte S.R.L.', '2018-06-01', 'https://mieldelnorte.local', 'Apicultura y miel orgánica para mercados nacionales e internacionales.', 'PyME', 'Apicultura y elaboración de miel', 'Beekeeping and honey production', 'approved', UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
(3, 3, 'Servicios Logísticos Santiago', 'Santiago Logistics Services', '30345678901', 'SLS S.A.', '2019-01-10', 'https://logistica.local', 'Soluciones de logística y distribución para el sector agroindustrial.', 'Empresa', 'Logística y distribución', 'Logistics and distribution', 'pending', UNIX_TIMESTAMP(), UNIX_TIMESTAMP());

-- 3 company addresses (one legal per company)
INSERT IGNORE INTO company_addresses (id, company_id, type, street, street_number, postal_code, locality, department, created_at) VALUES
(1, 1, 'legal', 'Av. Libertad', '1234', '4200', 'Santiago del Estero', 'Capital', UNIX_TIMESTAMP()),
(2, 2, 'legal', 'Ruta 34', 'Km 502', '4200', 'La Banda', 'Banda', UNIX_TIMESTAMP()),
(3, 3, 'legal', 'Calle 25 de Mayo', '567', '4200', 'Santiago del Estero', 'Capital', UNIX_TIMESTAMP());

-- 3 company contacts
INSERT IGNORE INTO company_contacts (id, company_id, contact_person, position, email, area_code, phone, created_at) VALUES
(1, 1, 'Juan Pérez', 'Gerente Comercial', 'jperez@agrosde.local', '385', '4123456', UNIX_TIMESTAMP()),
(2, 2, 'María González', 'Responsable de Exportación', 'mgonzalez@mieldelnorte.local', '385', '4234567', UNIX_TIMESTAMP()),
(3, 3, 'Carlos Rodríguez', 'Director', 'crodriguez@logistica.local', '385', '4345678', UNIX_TIMESTAMP());

-- 3 company_data (minimal JSON)
INSERT IGNORE INTO company_data (id, company_id, current_markets, target_markets, differentiation_factors, needs, competitiveness, logistics, expectations, consents, created_at, updated_at) VALUES
(1, 1, '[]', '[]', '[]', '[]', '{}', '{}', '{}', '{}', UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
(2, 2, '[]', '[]', '[]', '[]', '{}', '{}', '{}', '{}', UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
(3, 3, '[]', '[]', '[]', '[]', '{}', '{}', '{}', '{}', UNIX_TIMESTAMP(), UNIX_TIMESTAMP());

-- 3 company social networks
INSERT IGNORE INTO company_social_networks (id, company_id, network_type, url, created_at) VALUES
(1, 1, 'facebook', 'https://facebook.com/agrosde', UNIX_TIMESTAMP()),
(2, 2, 'instagram', 'https://instagram.com/mieldelnorte', UNIX_TIMESTAMP()),
(3, 3, 'linkedin', 'https://linkedin.com/company/sls', UNIX_TIMESTAMP());

-- 10 products (Miel del Norte: 3 + 1 extra with long name for UI/layout tests)
INSERT IGNORE INTO products (id, company_id, user_id, is_main, type, activity, name, name_en, description, tariff_code, annual_export, certifications, created_at, updated_at, deleted_at) VALUES
(1, 1, 1, 1, 'product', NULL, 'Soja en grano', 'Soybeans', 'Soja de primera calidad para exportación.', '1201.90.00', 'Hasta 5000 tn', 'Orgánico', UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), NULL),
(2, 1, 1, 0, 'product', NULL, 'Maíz', 'Corn', 'Maíz amarillo y blanco.', '1005.90.00', 'Hasta 3000 tn', NULL, UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), NULL),
(3, 1, 1, 0, 'service', 'Asesoramiento', 'Consultoría agronómica', 'Agronomic consulting', 'Asesoramiento técnico para cultivos.', NULL, NULL, NULL, UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), NULL),
(4, 2, 2, 1, 'product', NULL, 'Miel multifloral', 'Multifloral honey', 'Miel orgánica multifloral.', '0409.00.00', 'Hasta 20 tn', 'Orgánico, exportación', UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), NULL),
(5, 2, 2, 0, 'product', NULL, 'Propóleos', 'Propolis', 'Propóleos en bruto y tintura.', '0410.00.00', 'Hasta 2 tn', NULL, UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), NULL),
(6, 2, 2, 0, 'service', 'Venta', 'Envasado a medida', 'Custom packaging', 'Envasado de miel para terceros.', NULL, NULL, NULL, UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), NULL),
(7, 3, 3, 1, 'service', 'Logística', 'Transporte de granos', 'Grain transport', 'Transporte terrestre de granos a puertos.', NULL, NULL, NULL, UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), NULL),
(8, 3, 3, 0, 'service', 'Almacenaje', 'Depósito fiscal', 'Bonded warehouse', 'Almacenaje y custodia de mercadería.', NULL, NULL, NULL, UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), NULL),
(9, 3, 3, 0, 'product', NULL, 'Servicio de despacho aduanero', 'Customs clearance service', 'Tramitación aduanera.', NULL, NULL, NULL, UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), NULL),
(10, 2, 2, 0, 'product', NULL, 'Miel orgánica multifloral certificada de flora nativa del Chaco Seco (algarrobo, quebracho, eucalipto)', 'Certified organic multifloral honey from native Chaco Seco flora (carob, quebracho, eucalyptus), raw cold-filtered, packed in amber glass with full traceabili', 'Línea premium para mercados exigentes; lotes numerados y análisis de laboratorio.', '0409.00.00', 'Hasta 5 tn', 'Orgánico Arg., trazabilidad', UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), NULL);
