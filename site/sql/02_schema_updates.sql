-- AvrupaPazari: schema additions (import via phpMyAdmin AFTER 01_translations.sql). Safe to re-run.
SET NAMES utf8mb4;

-- 1) "Jobs & Workers" becomes a real category (module = jobs)
ALTER TABLE `categories` MODIFY `module` ENUM('esnaf','ikinci_el','emlak','arac','jobs') NOT NULL;
ALTER TABLE `listings`   MODIFY `module` ENUM('esnaf','ikinci_el','emlak','arac','jobs') NOT NULL;

INSERT INTO `categories` (`parent_id`,`module`,`slug`,`icon`,`sort_order`,`is_active`,`created_at`,`color_1`,`color_2`,`image`)
SELECT NULL,'jobs','jobs','briefcase',5,1,NOW(),'#7a5a3a','#5a3d22','categories/jobs.jpg'
WHERE NOT EXISTS (SELECT 1 FROM `categories` WHERE `module`='jobs' AND `parent_id` IS NULL);

INSERT IGNORE INTO `category_translations` (`category_id`,`lang`,`name`,`description`)
SELECT c.id, l.lang, l.name, l.description FROM `categories` c
JOIN (
  SELECT 'tr' AS lang, 'İş ve İşçi İlanları' AS name, 'Avrupa genelinde iş fırsatları ve iş arayanlar' AS description
  UNION ALL SELECT 'nl', 'Vacatures & Werk', 'Vacatures en werkzoekenden in heel Europa'
  UNION ALL SELECT 'en', 'Jobs & Workers', 'Job offers and job seekers across Europe'
  UNION ALL SELECT 'de', 'Jobs & Arbeitskräfte', 'Stellenangebote und Arbeitssuchende in ganz Europa'
) l
WHERE c.module='jobs' AND c.parent_id IS NULL;

-- 2) Category card images (files shipped in /uploads/categories/)
UPDATE `categories` SET `image`='categories/businesses.jpg'  WHERE `module`='esnaf'     AND `parent_id` IS NULL;
UPDATE `categories` SET `image`='categories/second-hand.jpg' WHERE `module`='ikinci_el' AND `parent_id` IS NULL;
UPDATE `categories` SET `image`='categories/property.jpg'    WHERE `module`='emlak'     AND `parent_id` IS NULL;
UPDATE `categories` SET `image`='categories/vehicles.jpg'    WHERE `module`='arac'      AND `parent_id` IS NULL;
UPDATE `categories` SET `image`='categories/jobs.jpg'        WHERE `module`='jobs'      AND `parent_id` IS NULL;

-- 3) uploads/esnaf renamed to uploads/businesses
UPDATE `esnaf` SET `image` = REPLACE(`image`, 'esnaf/', 'businesses/') WHERE `image` LIKE 'esnaf/%';

-- 4) Static pages (about, contact, privacy, terms, cookies) editable in the DB
CREATE TABLE IF NOT EXISTS `static_pages` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `slug` VARCHAR(60) NOT NULL,
  `lang` VARCHAR(5) NOT NULL,
  `title` VARCHAR(200) NOT NULL,
  `content` MEDIUMTEXT NOT NULL,
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `slug_lang` (`slug`,`lang`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT IGNORE INTO `static_pages` (`slug`,`lang`,`title`,`content`) VALUES
('about','tr','Hakkımızda','<p>AvrupaPazari, Avrupa\'da yaşayan Türkçe konuşan topluluk için güvenilir bir ilan platformudur. Araç, emlak, ikinci el ürün ve esnaf ilanlarını tek çatı altında toplarız.</p><p>Amacımız, Hollanda, Almanya, Belçika ve diğer Avrupa ülkelerindeki kullanıcıları güvenli ve hızlı bir şekilde bir araya getirmektir.</p>'),
('about','nl','Over ons','<p>AvrupaPazari is een betrouwbaar advertentieplatform voor de Turkssprekende gemeenschap in Europa. Auto\'s, woningen, tweedehands artikelen en lokale ondernemers op één plek.</p><p>Ons doel is gebruikers in Nederland, Duitsland, België en de rest van Europa veilig en snel met elkaar te verbinden.</p>'),
('about','en','About us','<p>AvrupaPazari is a trusted classifieds platform for the Turkish-speaking community in Europe. Vehicles, property, second-hand items and local businesses in one place.</p><p>Our goal is to connect users in the Netherlands, Germany, Belgium and across Europe safely and quickly.</p>'),
('about','de','Über uns','<p>AvrupaPazari ist eine vertrauenswürdige Kleinanzeigen-Plattform für die türkischsprachige Gemeinschaft in Europa. Fahrzeuge, Immobilien, Gebrauchtes und lokale Unternehmen an einem Ort.</p><p>Unser Ziel ist es, Nutzer in den Niederlanden, Deutschland, Belgien und ganz Europa sicher und schnell zusammenzubringen.</p>'),
('contact','tr','İletişim','<p>Sorularınız için bize e-posta ile ulaşabilirsiniz: <a href="mailto:info@avrupapazari.com">info@avrupapazari.com</a></p><p>Genellikle 1 iş günü içinde yanıt veriyoruz.</p>'),
('contact','nl','Contact','<p>Vragen? Mail ons: <a href="mailto:info@avrupapazari.com">info@avrupapazari.com</a></p><p>We reageren meestal binnen 1 werkdag.</p>'),
('contact','en','Contact','<p>Questions? Email us at <a href="mailto:info@avrupapazari.com">info@avrupapazari.com</a></p><p>We usually reply within 1 business day.</p>'),
('contact','de','Kontakt','<p>Fragen? Schreiben Sie uns: <a href="mailto:info@avrupapazari.com">info@avrupapazari.com</a></p><p>Wir antworten in der Regel innerhalb eines Werktags.</p>'),
('privacy','tr','Gizlilik Politikası','<p>Kişisel verileriniz yalnızca hesabınızı yönetmek, ilanlarınızı yayınlamak ve sizinle iletişim kurmak için kullanılır. Verileriniz üçüncü taraflara satılmaz.</p><p>Hesabınızı ve verilerinizi istediğiniz zaman silme talebinde bulunabilirsiniz.</p>'),
('privacy','nl','Privacybeleid','<p>Je persoonsgegevens worden alleen gebruikt om je account te beheren, je advertenties te publiceren en contact met je op te nemen. Gegevens worden niet verkocht aan derden.</p><p>Je kunt op elk moment verzoeken je account en gegevens te verwijderen.</p>'),
('privacy','en','Privacy Policy','<p>Your personal data is used only to manage your account, publish your listings and contact you. Data is never sold to third parties.</p><p>You can request deletion of your account and data at any time.</p>'),
('privacy','de','Datenschutzerklärung','<p>Ihre personenbezogenen Daten werden nur zur Verwaltung Ihres Kontos, zur Veröffentlichung Ihrer Anzeigen und zur Kontaktaufnahme verwendet. Daten werden nicht an Dritte verkauft.</p><p>Sie können jederzeit die Löschung Ihres Kontos und Ihrer Daten verlangen.</p>'),
('terms','tr','Kullanım Şartları','<p>Platformu kullanarak yasalara uygun, doğru ve yanıltıcı olmayan ilanlar vermeyi kabul edersiniz. Yasa dışı ürün ve hizmetlerin ilanı yasaktır.</p><p>AvrupaPazari, kurallara aykırı ilanları önceden bildirmeksizin kaldırma hakkını saklı tutar.</p>'),
('terms','nl','Gebruiksvoorwaarden','<p>Door het platform te gebruiken ga je akkoord met het plaatsen van wettelijke, correcte en niet-misleidende advertenties. Illegale producten en diensten zijn verboden.</p><p>AvrupaPazari behoudt zich het recht voor advertenties die de regels schenden zonder kennisgeving te verwijderen.</p>'),
('terms','en','Terms of Use','<p>By using the platform you agree to post lawful, accurate and non-misleading listings. Illegal products and services are prohibited.</p><p>AvrupaPazari reserves the right to remove listings that violate the rules without prior notice.</p>'),
('terms','de','Nutzungsbedingungen','<p>Mit der Nutzung der Plattform verpflichten Sie sich, rechtmäßige, korrekte und nicht irreführende Anzeigen zu veröffentlichen. Illegale Produkte und Dienstleistungen sind verboten.</p><p>AvrupaPazari behält sich das Recht vor, regelwidrige Anzeigen ohne Vorankündigung zu entfernen.</p>'),
('cookies','tr','Çerez Politikası','<p>Sitemiz oturumunuzu sürdürmek, dil ve tema tercihlerinizi hatırlamak için zorunlu çerezler kullanır. Reklam amaçlı takip çerezi kullanılmaz.</p>'),
('cookies','nl','Cookiebeleid','<p>Onze site gebruikt noodzakelijke cookies om je sessie te behouden en je taal- en themavoorkeuren te onthouden. Er worden geen tracking-cookies voor advertenties gebruikt.</p>'),
('cookies','en','Cookie Policy','<p>Our site uses essential cookies to keep your session and remember your language and theme preferences. No advertising tracking cookies are used.</p>'),
('cookies','de','Cookie-Richtlinie','<p>Unsere Website verwendet notwendige Cookies, um Ihre Sitzung aufrechtzuerhalten und Ihre Sprach- und Design-Einstellungen zu speichern. Es werden keine Werbe-Tracking-Cookies verwendet.</p>');

-- 5) Cleanup: unused backup table
DROP TABLE IF EXISTS `listing_emlak_backup_20260827`;

-- Vehicles first everywhere
UPDATE `categories` SET `sort_order` = CASE `module` WHEN 'arac' THEN 1 WHEN 'emlak' THEN 2 WHEN 'ikinci_el' THEN 3 WHEN 'esnaf' THEN 4 WHEN 'jobs' THEN 5 END WHERE `parent_id` IS NULL;

-- Switzerland added; country order NL, DE, BE, FR, AT, CH, DK, TR, PL
INSERT INTO `countries` (`code`,`name_tr`,`name_nl`,`name_en`,`name_de`,`is_active`,`sort_order`)
SELECT 'CH','İsviçre','Zwitserland','Switzerland','Schweiz',1,6 FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `countries` WHERE `code`='CH');
UPDATE `countries` SET `is_active`=1, `sort_order` = CASE `code` WHEN 'NL' THEN 1 WHEN 'DE' THEN 2 WHEN 'BE' THEN 3 WHEN 'FR' THEN 4 WHEN 'AT' THEN 5 WHEN 'CH' THEN 6 WHEN 'DK' THEN 7 WHEN 'TR' THEN 8 WHEN 'PL' THEN 9 ELSE `sort_order` END;
