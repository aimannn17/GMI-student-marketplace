-- ============================================================
--  OPTIONAL sample data so the site is not empty on first run.
--  Import AFTER marketplace.sql.  Names/prices are placeholders - edit freely.
--  Demo logins:  demo_seller / password123     demo_buyer / password123
-- ============================================================
USE `marketplace`;

INSERT IGNORE INTO `user` (`username`, `fullname`, `password`, `phone_number`, `role`, `status`) VALUES
  ('demo_seller', 'Demo Seller', '482c811da5d5b4bc6d497ffa98491e38', '0123456789', 1, 'active'),
  ('demo_buyer',  'Demo Buyer',  '482c811da5d5b4bc6d497ffa98491e38', '0129876543', 1, 'active');

SET @seller := (SELECT user_id FROM `user` WHERE username = 'demo_seller');

INSERT INTO `item` (`item_name`, `price`, `description`, `phone_number`, `user_id`, `image`, `category_id`, `condition_id`) VALUES
  ('Adidas Sport Shoes',       120.00, 'Lightly used sport shoes, size 42.',               '0123456789', @seller, 'Adidas sport shoes.jpg',  4, 2),
  ('Nike Air Max Shoes',       220.00, 'Comfortable everyday sneakers.',                   '0123456789', @seller, 'nike air max shoes.jpg',  4, 2),
  ('Nike KD 15',               280.00, 'Basketball shoes in good condition.',              '0123456789', @seller, 'nike kd 15.jpg',          4, 3),
  ('Apple Watch',              450.00, 'Works perfectly, includes charger.',               '0123456789', @seller, 'apple watch.jpg',         2, 3),
  ('Smart Watch',               90.00, 'Fitness tracking smart watch.',                    '0123456789', @seller, 'Smart Watch.jpg',         2, 2),
  ('Bluetooth Speaker',         60.00, 'Portable speaker with good bass.',                 '0123456789', @seller, 'Speaker.jpg',             2, 3),
  ('PS4 Console',              650.00, 'PS4 with one controller.',                         '0123456789', @seller, 'ps4.jpg',                 2, 3),
  ('ASUS TUF Gaming Laptop',  2800.00, 'Great for gaming and assignments.',                '0123456789', @seller, 'TUF.jpg',                 2, 3),
  ('Buku Etika',                15.00, 'Ethics textbook, clean pages.',                    '0123456789', @seller, 'Buku Etika.jpg',          1, 3),
  ('Buku Nota',                  8.00, 'Lecture notes book, barely used.',                 '0123456789', @seller, 'Buku Nota.jpg',           1, 2),
  ('Yonex Badminton Racket',   150.00, 'Yonex racket, restrung recently.',                 '0123456789', @seller, 'yonex.jpg',               5, 3),
  ('Yamaha Y15',              6500.00, 'Well-maintained motorcycle, road tax valid.',      '0123456789', @seller, 'y15.jpg',                 5, 3),
  ('Mr Potato Snack',            5.00, 'Fresh snack, pickup at campus.',                   '0123456789', @seller, 'Mr Potato.jpg',           3, 1),
  ('Taufufa',                    3.00, 'Homemade soy pudding, daily batch.',               '0123456789', @seller, 'taufufa.jpg',             3, 1);
