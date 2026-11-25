# GMI Student Marketplace

PHP + MySQL campus marketplace: browse as guest, register, sell items, buy items, order history, admin dashboard.

## Setup (XAMPP / Laragon)

1. Copy this folder into `htdocs` (XAMPP) or `www` (Laragon).
2. Start **Apache** and **MySQL**.
3. Open phpMyAdmin → **Import** → choose `marketplace.sql` (creates the `marketplace` database, all tables, categories, conditions and an admin account).
4. *(Optional)* Import `sample_data.sql` to get demo items that use the pictures already in `image/`.
5. If your MySQL login is not `root` with an empty password, edit `connectdbmarketplace.php`.
6. Open `http://localhost/student marketplace latest/` (or whatever you named the folder).

| Account | Username | Password | Role |
|---|---|---|---|
| Admin | `admin` | `admin123` | 3 (change it from the Profile page!) |
| Demo seller *(sample_data.sql)* | `demo_seller` | `password123` | 1 |
| Demo buyer *(sample_data.sql)* | `demo_buyer` | `password123` | 1 |

## What was completed / fixed

**Missing pieces added**
- `marketplace.sql` – full database schema (there was none) + required seed data; `sample_data.sql` – optional demo data.
- `helpers.php` – shared escaping, CSRF tokens, safe alerts, secure image upload.
- `footer.php` – the old file was from a different project (timetable); replaced with a real footer and wired into `index.php`.
- Edit Item now lets you change **category and condition** (before only name/price/description/phone/image).
- Admin → Manage Users now has an **Edit** button (`edit_user.php` existed but nothing linked to it; it is now `index.php?page=10`).
- Footer, "Sold" badge on item page, empty-state rows in order history, back links.
- `image/.htaccess` – blocks script execution inside the upload folder.

**Bugs fixed**
- Admin dashboard read table `sales`, but checkout writes `transactions` → purchases never appeared. Dashboard now reads `transactions`.
- Checkout trusted hidden form fields for price and seller (could be edited in the browser) and allowed buying an item twice / your own item. Price and seller now come from the database, item is locked during purchase.
- Suspended users could still log in. Now blocked at login, and logged-in users who get suspended/deleted are logged out on the next click.
- Guests could see the seller's phone number even though the banner says login is required. Phone is now hidden until login.
- `view_item.php` had an unclosed `<div>`; `guest_guard` had no `.php` extension and a syntax error; `connectdbmarketplace.php` called `mysqli_error()` without its argument (fatal in PHP 8).
- Category/sort filters in `home.php` went straight into SQL (SQL injection) and `$sort` was echoed into JavaScript (XSS). Search now also survives changing category/sort.
- Image upload accepted any file type (e.g. `.php`) under the original file name (files overwrote each other). Now: real image check, 5 MB limit, random file names. Deleting/replacing an image no longer removes a file another item still uses.
- Order history used a plain JOIN, so deleting an item or user made old orders disappear. Now uses LEFT JOIN and the price stored on the order.
- Deleting a user now also removes their listings (they used to stay on the home page pointing at nobody).
- Duplicate usernames show a friendly message; registration has confirm-password and basic validation.
- Admin-dashboard delete/suspend/activate links and all forms now carry CSRF tokens; unescaped output (XSS) fixed in several pages.
- Mixed Malay/English messages unified to English.

**Renamed:** `edit item.php` → `edit_item.php`, `order.summary.php` → `order_summary.php`, `guest_guard` → `guest_guard.php`.

**Removed** (originals are still in your uploaded zip)
- `page1.php`, `page2.php`, `page3.php` – leftover session tutorial. `page1.php` set `$_SESSION['userid'] = 1` for anyone who opened it (login bypass).
- `index_login_patch.php` – code snippet, not a runnable page (its logic already lives in `index.php`).
- `authenticate.php` – duplicate of the login in `index.php`, not used by the login form.

**Kept but unused by any page yet:** `sales.php` (seller records an offline sale – now writes to `transactions`) and `admin_action.php` (admin "add item for user"). Both are secured; they just have no button pointing at them.

## If you already have a `marketplace` database

`marketplace.sql` uses `CREATE TABLE IF NOT EXISTS`, so it will not touch existing tables. Make sure these exist (run only what is missing):

```sql
ALTER TABLE `user` ADD COLUMN `status` VARCHAR(20) NOT NULL DEFAULT 'active';
ALTER TABLE `item` ADD COLUMN `status` VARCHAR(20) NOT NULL DEFAULT 'active';
ALTER TABLE `user` ADD UNIQUE (`username`);
-- If you have old rows in the former `sales` table, bring them into `transactions`:
INSERT INTO transactions (item_id, buyer_id, seller_id, price, status, transaction_date, payment_method, delivery_method)
SELECT item_id, buyer_id, seller_id, price, 'Completed', date_sold, payment_method, delivery_method FROM sales;
```

## Not changed on purpose – worth doing before any real deployment

- **Passwords use MD5.** I left it because changing the hash needs the `password` column to be wide enough (`VARCHAR(255)`) and existing accounts to be migrated. Recommended next step: `password_hash()` / `password_verify()` with re-hash on login.
- Database user is `root` with no password (fine for XAMPP, not for a server).
- Most SQL still uses `mysqli_real_escape_string` + integer casts rather than prepared statements.
- `style.css` is not linked by `index.php` (pages use inline styles); linking it would change the look, so I left it alone. `student.css` is used by the registration page.
- I could not run PHP/MySQL in my environment, so the code was checked by reading and a static bracket/tag check only. Please click through: register → login → add item → buy → order history → admin dashboard, and tell me if anything errors.
