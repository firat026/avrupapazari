-- Favorites texts. Safe to re-run.
SET NAMES utf8mb4;
INSERT INTO `translations` (`lang`,`key_group`,`key_name`,`value`) VALUES
('tr','account','favorites','Favorilerim'),('nl','account','favorites','Mijn favorieten'),('en','account','favorites','My favorites'),('de','account','favorites','Meine Favoriten'),
('tr','account','no_favorites','Henüz favori ilanınız yok. İlanlardaki kalp ikonuna tıklayarak kaydedin.'),('nl','account','no_favorites','Je hebt nog geen favorieten. Klik op het hartje bij een advertentie om op te slaan.'),('en','account','no_favorites','No favorites yet. Tap the heart on a listing to save it.'),('de','account','no_favorites','Noch keine Favoriten. Tippen Sie auf das Herz einer Anzeige, um sie zu speichern.'),
('tr','listing','add_favorite','Favorilere Ekle'),('nl','listing','add_favorite','Toevoegen aan favorieten'),('en','listing','add_favorite','Add to favorites'),('de','listing','add_favorite','Zu Favoriten hinzufügen'),
('tr','listing','saved','Kaydedildi'),('nl','listing','saved','Opgeslagen'),('en','listing','saved','Saved'),('de','listing','saved','Gespeichert')
ON DUPLICATE KEY UPDATE `value` = VALUES(`value`);
