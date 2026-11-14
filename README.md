# Emergency Medicine Delivery System (Online Pharmacy System)

IGNOU BCA Project (BCSP-064) | Student: Prince Chaudhary | Guide: Ashish Mehta

A web application that lets customers order medicines in an emergency, upload prescriptions, pay online or by cash, and track delivery against a 20 minute countdown. Pharmacy staff manage medicines, verify prescriptions, assign delivery agents and generate reports.

**Stack (as approved in the synopsis):** HTML, CSS, JavaScript (front end) | PHP (back end) | MySQL (database) | Three-tier architecture.

---

## 1. Quick start (XAMPP on Windows)

1. Install XAMPP (PHP 8.0 or newer) and start **Apache** and **MySQL** in the XAMPP control panel.
2. Copy the folder `emds` into `C:\xampp\htdocs\`.
3. Open **http://localhost/emds/install.php** in the browser and click **Install now**. This creates the `pharmacy` database, all 12 tables and sample data.
4. Open **http://localhost/emds/** (website) and **http://localhost/emds/admin/login.php** (admin panel).
5. Delete `install.php` after setup (good security practice).

Alternative to step 3: open phpMyAdmin, click **Import**, choose `database/database.sql`, click **Go**.

**No XAMPP? Run with PHP's built-in server:**
```
cd emds
php -S localhost:8080
```
Then open http://localhost:8080/install.php (needs PHP with the `pdo_mysql` extension and a running MySQL or MariaDB).

## 2. Login details

| Role | Email | Password |
|---|---|---|
| Admin | admin@emds.in | Admin@123 |
| Customer | aarav@example.com | Customer@123 |
| Customer | neha@example.com | Customer@123 |

You can also register a new customer from the website.

## 3. Configuration (`config.php`)

| Setting | Default | Notes |
|---|---|---|
| `DB_HOST / DB_USER / DB_PASS / DB_NAME` | localhost / root / (empty) / pharmacy | XAMPP defaults. If MySQL runs on port 3307, set `DB_PORT`. |
| `DELIVERY_MINUTES` | 20 | **Set to 2 before the viva demo** so the countdown and map animation finish quickly. Set back to 20 afterwards. |
| `DELIVERY_FEE`, `FREE_DELIVERY_ABOVE` | 30 and 500 | Flat emergency fee, free above the limit. |
| `$DELIVERY_AGENTS` | 5 agents | Names shown in the admin "assign agent" list. |

## 4. Test data for payments (demo gateway)

| Method | Success | Declined (to show failure handling) |
|---|---|---|
| UPI | any valid ID such as `name@upi` | `fail@upi` |
| Card | 4111 1111 1111 1111, any future expiry, any CVV | 4000 0000 0000 0002 |
| Net banking | choose any bank | none |
| Cash on delivery | order confirmed, payment stays Pending until delivery, then becomes Success automatically | none |

Card numbers are validated (Luhn check) but **never stored**.

## 5. Five minute demo script

1. **Customer:** log in as aarav, search "paracetamol", add to cart (no page reload), checkout, pay by UPI.
2. **Prescription flow:** add "Amoxicillin" (marked Rx), checkout, upload any JPG, PNG or PDF as the prescription. Order shows **Awaiting Prescription**.
3. **Admin (second browser or private window):** log in at /admin, open **Prescriptions**, view the file, **Approve**. The customer's 20 minute timer starts now.
4. Admin: **Orders** > set Confirmed > Packed, then **Delivery** > assign an agent. Order becomes **Out for Delivery**.
5. **Customer tracking page:** countdown running, rider moving on the live map, status updating by itself every 4 seconds.
6. Admin: **Mark delivered**. The customer page switches to Delivered without a refresh.
7. Admin: **Reports** (5 types, date filter, CSV export, print) and **Dashboard** (low stock, expiring medicines, orders by status).

## 6. Project structure

```
emds/
  index.php, products.php, product.php     Home, search and listing, details and feedback
  cart.php, checkout.php, payment.php      Cart, order placement, payment
  receipt.php, orders.php, track.php        Receipt, my orders and cancel, live tracking
  prescriptions.php, file.php               Prescription upload, protected file viewer
  login.php, register.php, logout.php       Customer authentication
  api/track.php                             JSON endpoint polled by the tracking page
  admin/                                    Admin panel (dashboard, orders, delivery, prescriptions,
                                            medicines, categories, payments, customers, reports)
  includes/                                 db.php, functions.php (helpers, security),
                                            orders.php (business rules), header/footer
  assets/css/style.css, assets/js/          Styling, cart/payment scripts, tracking map
  database/database.sql                     Schema (12 tables) and sample data
  uploads/prescriptions/                    Uploaded prescriptions (blocked from direct web access)
  install.php                               One click database installer
  docs/VIVA_GUIDE.md                        Synopsis mapping, design decisions, viva questions
```

## 7. Security features (synopsis section 15)

- Passwords stored as bcrypt hashes (`password_hash`), never in plain text.
- All SQL uses prepared statements (no SQL injection).
- CSRF token on every form, session ID regenerated on login, HttpOnly session cookie.
- Login lockout for 5 minutes after 5 failed attempts.
- Output escaped everywhere (no XSS). Input validated on the server (email, mobile, address, price, stock, quantity).
- Prescription uploads: extension and real file type both checked, random file names, size limit 5 MB, files served only to the owner or an admin.
- Stock is locked and updated inside database transactions, so two customers cannot buy the last unit.

## 8. Troubleshooting

| Problem | Fix |
|---|---|
| "Database connection failed" | Start MySQL in XAMPP. Check `DB_USER`, `DB_PASS`, `DB_PORT` in `config.php`. |
| Page opens but images or styles are missing | Open the site through `http://localhost/emds/` and not by double clicking the file. |
| Prescription upload fails for large files | In `php.ini` set `upload_max_filesize` and `post_max_size` to at least 6M, then restart Apache. |
| Links go to the wrong folder | Set `BASE_PATH_OVERRIDE` in `config.php`, for example `'/emds'`. |
| Want to reset all data | Run `install.php` again and tick the confirmation box. |

*This software is an academic project. The payment gateway is a demonstration and the delivery map is a schematic simulation. It does not give medical advice.*
