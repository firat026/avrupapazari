-- Messaging + job listings. Safe to re-run.
SET NAMES utf8mb4;

CREATE TABLE IF NOT EXISTS `listing_jobs` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `listing_id` INT UNSIGNED NOT NULL,
  `job_type` ENUM('offer','seek') NOT NULL DEFAULT 'offer',
  `sector` VARCHAR(120) NOT NULL DEFAULT '',
  `salary` VARCHAR(80) NOT NULL DEFAULT '',
  `employment` ENUM('fulltime','parttime','temporary','freelance') NOT NULL DEFAULT 'fulltime',
  PRIMARY KEY (`id`),
  UNIQUE KEY `listing_id` (`listing_id`),
  CONSTRAINT `fk_listing_jobs` FOREIGN KEY (`listing_id`) REFERENCES `listings` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `translations` (`lang`,`key_group`,`key_name`,`value`) VALUES
('tr','msg','title','Mesajlarım'),('nl','msg','title','Mijn berichten'),('en','msg','title','My messages'),('de','msg','title','Meine Nachrichten'),
('tr','msg','send','Mesaj Gönder'),('nl','msg','send','Bericht sturen'),('en','msg','send','Send message'),('de','msg','send','Nachricht senden'),
('tr','msg','placeholder','Merhaba, ilanınız hâlâ güncel mi?'),('nl','msg','placeholder','Hallo, is de advertentie nog beschikbaar?'),('en','msg','placeholder','Hi, is this listing still available?'),('de','msg','placeholder','Hallo, ist die Anzeige noch verfügbar?'),
('tr','msg','sent','Mesajınız gönderildi.'),('nl','msg','sent','Je bericht is verzonden.'),('en','msg','sent','Your message has been sent.'),('de','msg','sent','Ihre Nachricht wurde gesendet.'),
('tr','msg','empty','Henüz mesajınız yok.'),('nl','msg','empty','Je hebt nog geen berichten.'),('en','msg','empty','No messages yet.'),('de','msg','empty','Noch keine Nachrichten.'),
('tr','msg','own_listing','Bu sizin ilanınız.'),('nl','msg','own_listing','Dit is je eigen advertentie.'),('en','msg','own_listing','This is your own listing.'),('de','msg','own_listing','Das ist Ihre eigene Anzeige.'),
('tr','msg','reply','Yanıtla'),('nl','msg','reply','Beantwoorden'),('en','msg','reply','Reply'),('de','msg','reply','Antworten'),
('tr','msg','about','İlan'),('nl','msg','about','Advertentie'),('en','msg','about','Listing'),('de','msg','about','Anzeige'),
('tr','msg','unread','okunmamış'),('nl','msg','unread','ongelezen'),('en','msg','unread','unread'),('de','msg','unread','ungelesen'),
('tr','msg','select','Bir sohbet seçin'),('nl','msg','select','Kies een gesprek'),('en','msg','select','Select a conversation'),('de','msg','select','Wählen Sie ein Gespräch'),
('tr','msg','too_short','Mesaj çok kısa.'),('nl','msg','too_short','Bericht is te kort.'),('en','msg','too_short','Message is too short.'),('de','msg','too_short','Nachricht ist zu kurz.'),
('tr','job','post_title','İş İlanı Ver'),('nl','job','post_title','Vacature plaatsen'),('en','job','post_title','Post a job listing'),('de','job','post_title','Stellenanzeige aufgeben'),
('tr','job','type','İlan Türü'),('nl','job','type','Type'),('en','job','type','Listing type'),('de','job','type','Anzeigentyp'),
('tr','job','type_offer','İş Veriyorum (İşçi Arıyorum)'),('nl','job','type_offer','Ik bied werk aan'),('en','job','type_offer','I am hiring'),('de','job','type_offer','Ich biete Arbeit'),
('tr','job','type_seek','İş Arıyorum'),('nl','job','type_seek','Ik zoek werk'),('en','job','type_seek','I am looking for work'),('de','job','type_seek','Ich suche Arbeit'),
('tr','job','sector','Sektör'),('nl','job','sector','Sector'),('en','job','sector','Sector'),('de','job','sector','Branche'),
('tr','job','salary','Maaş / Ücret'),('nl','job','salary','Salaris / Vergoeding'),('en','job','salary','Salary / Pay'),('de','job','salary','Gehalt / Vergütung'),
('tr','job','salary_hint','Örn. €2.500 / ay veya saatlik €15'),('nl','job','salary_hint','Bijv. €2.500 / maand of €15 per uur'),('en','job','salary_hint','e.g. €2,500 / month or €15 per hour'),('de','job','salary_hint','z. B. €2.500 / Monat oder €15 pro Stunde'),
('tr','job','employment','Çalışma Şekli'),('nl','job','employment','Dienstverband'),('en','job','employment','Employment type'),('de','job','employment','Beschäftigungsart'),
('tr','job','fulltime','Tam zamanlı'),('nl','job','fulltime','Fulltime'),('en','job','fulltime','Full-time'),('de','job','fulltime','Vollzeit'),
('tr','job','parttime','Yarı zamanlı'),('nl','job','parttime','Parttime'),('en','job','parttime','Part-time'),('de','job','parttime','Teilzeit'),
('tr','job','temporary','Geçici / Sezonluk'),('nl','job','temporary','Tijdelijk / Seizoen'),('en','job','temporary','Temporary / Seasonal'),('de','job','temporary','Befristet / Saison'),
('tr','job','freelance','Serbest'),('nl','job','freelance','Freelance'),('en','job','freelance','Freelance'),('de','job','freelance','Freiberuflich'),
('tr','job','title_label','İlan Başlığı'),('nl','job','title_label','Titel'),('en','job','title_label','Listing title'),('de','job','title_label','Titel der Anzeige'),
('tr','job','description','Açıklama'),('nl','job','description','Omschrijving'),('en','job','description','Description'),('de','job','description','Beschreibung'),
('tr','job','submit','İlanı Yayınla'),('nl','job','submit','Plaatsen'),('en','job','submit','Publish listing'),('de','job','submit','Anzeige veröffentlichen'),
('tr','job','success','İş ilanınız yayınlandı.'),('nl','job','success','Je vacature is geplaatst.'),('en','job','success','Your job listing is published.'),('de','job','success','Ihre Stellenanzeige wurde veröffentlicht.'),
('tr','job','sectors','İnşaat,Lojistik & Nakliye,Gastronomi & Restoran,Temizlik,Market & Perakende,Üretim & Fabrika,Kuaför & Güzellik,Bakım & Sağlık,Ofis & Yönetim,Bilişim,Tarım & Bahçe,Diğer'),
('nl','job','sectors','Bouw,Logistiek & Transport,Horeca,Schoonmaak,Supermarkt & Retail,Productie & Fabriek,Kapper & Beauty,Zorg,Kantoor & Administratie,IT,Landbouw & Tuin,Overig'),
('en','job','sectors','Construction,Logistics & Transport,Hospitality & Restaurant,Cleaning,Grocery & Retail,Production & Factory,Hairdressing & Beauty,Care & Health,Office & Admin,IT,Agriculture & Garden,Other'),
('de','job','sectors','Bau,Logistik & Transport,Gastronomie,Reinigung,Supermarkt & Einzelhandel,Produktion & Fabrik,Friseur & Beauty,Pflege & Gesundheit,Büro & Verwaltung,IT,Landwirtschaft & Garten,Sonstiges')
ON DUPLICATE KEY UPDATE `value` = VALUES(`value`);
