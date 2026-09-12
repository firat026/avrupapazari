-- Category/jobs page filter texts. Safe to re-run.
SET NAMES utf8mb4;
INSERT INTO `translations` (`lang`,`key_group`,`key_name`,`value`) VALUES
('tr','common','country','Ülke'),('nl','common','country','Land'),('en','common','country','Country'),('de','common','country','Land'),
('tr','common','city','Şehir'),('nl','common','city','Stad'),('en','common','city','City'),('de','common','city','Stadt'),
('tr','common','clear','Temizle'),('nl','common','clear','Wissen'),('en','common','clear','Clear'),('de','common','clear','Zurücksetzen'),
('tr','sort','newest','En Yeni'),('nl','sort','newest','Nieuwste'),('en','sort','newest','Newest'),('de','sort','newest','Neueste'),
('tr','sort','price_asc','Fiyat: Artan'),('nl','sort','price_asc','Prijs: laag-hoog'),('en','sort','price_asc','Price: low to high'),('de','sort','price_asc','Preis: aufsteigend'),
('tr','sort','price_desc','Fiyat: Azalan'),('nl','sort','price_desc','Prijs: hoog-laag'),('en','sort','price_desc','Price: high to low'),('de','sort','price_desc','Preis: absteigend'),
('tr','sort','popular','En Popüler'),('nl','sort','popular','Populairst'),('en','sort','popular','Most popular'),('de','sort','popular','Beliebteste')
ON DUPLICATE KEY UPDATE `value` = VALUES(`value`);
