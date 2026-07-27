# Elite Club Management Portal

A complete, professional, responsive **Elite Club Management Portal** built with **PHP 8+**, **MySQL**, **HTML5**, **CSS3** and **Vanilla JavaScript**. Designed to run on **XAMPP** out of the box with a premium, glassmorphism admin dashboard inspired by modern SaaS products.

---

## Features

### Authentication & Security
- Secure login with **password hashing** (bcrypt)
- Role-based access control (**Admin** and **Member** roles)
- Session management with **30-minute timeout**
- **CSRF protection** on every form
- **PDO prepared statements** (SQL-injection safe)
- **XSS protection** (all output escaped)
- Forgot Password / Reset flow
- Logout

### Landing Page (Home)
A beautiful marketing page shown before login with sections for:
- Hero banner with animated stats card
- About the club
- Features grid
- Membership benefits
- Statistics strip with animated counters
- Testimonials
- Gallery
- FAQ accordion
- Contact form
- Modern footer

### Admin Dashboard (Redesigned)
- **Animated statistic cards** with count-up animation: Total Members, Active Members, Expired Memberships, Today's Birthdays, Upcoming Events, Monthly Revenue, Attendance Today, Outstanding Payments
- **Circular progress bars** for membership health & renewal rate
- **Quick Actions panel** for one-click access to common tasks
- **Calendar widget** with event markers
- **Chart.js** charts: revenue (gradient bar), membership distribution (doughnut), weekly attendance (line)
- **Recent activity timeline**
- **Upcoming events** & **recent payments** widgets
- Latest announcement preview
- Notifications dropdown (expiry warnings, pending payments, upcoming events)

### Member Management
- Full CRUD (Add / Edit / Delete / View) with AJAX
- Auto-generated Member ID (`M-YYYY-0001`)
- Photo upload
- Search + filter by status, plan, gender, city
- Pagination, sorting, CSV export, print

### Membership Plans
- CRUD with duration (months), price, benefits, status
- Auto-calculates member expiry date when a plan is assigned

### Payments
- Record payments with auto-generated receipt number (`RCP-YYYYMMDD-0001`)
- Methods: Cash, Card, Bank Transfer, UPI, Cheque
- Filters by status, method, date range
- Printable individual receipt
- Monthly revenue, outstanding payments, total revenue stats
- CSV export

### Attendance
- Mark daily attendance (auto marks **late** after the configured threshold time)
- Prevents duplicate check-ins per day
- Search & filter by date / status
- CSV export

### Events
- CRUD events with title, description, location, date, time, organizer, max participants, status
- Members can **register / cancel** via AJAX
- Admins can view all registrations per event

### Announcements
- Admin publishes announcements (audience: all / members / admins)
- Members view announcements on their dashboard and a dedicated page

### Member Dashboard & Profile (Redesigned)
- **Profile cover** with avatar and membership badge
- Personalized welcome and expiry warning banner
- Stat cards: Member No, Status, Days Left, Total Paid
- **Circular progress** showing membership usage
- Attendance mini-chart (7 days)
- Upcoming registered events, recent payments, recent attendance
- **Activity timeline**
- Update profile with floating-label forms, change password

### Reports
- Members, Payments, Attendance, Membership Expiry, Revenue reports
- Export to **CSV**, **Print**

### UI / UX (Premium Redesign)
- **Outfit** + **Inter** Google Fonts
- **Font Awesome 6** icons
- New color palette: Primary `#4F46E5` (Indigo), Secondary `#0F172A` (Slate), Accent `#06B6D4` (Cyan), Success `#22C55E`, Warning `#F59E0B`, Danger `#EF4444`, Background `#F5F7FB`
- **Glassmorphism cards** with backdrop blur and soft shadows
- **16px rounded corners**, **gradient buttons**
- Collapsible animated sidebar with active-page indicator
- Top navbar: search, notifications, messages, profile menu, dark mode toggle, current date
- **Dark mode** toggle (saved in localStorage)
- **Loading screen** on boot
- **Back-to-top** button
- Modal popups, toast notifications, confirmation dialogs
- **Animated counters**, **circular progress**, **scroll-reveal** animations
- **Keyboard shortcuts** (Ctrl+K to focus search, Esc to close modals)
- Floating-label forms with icons inside inputs
- Modern tables with sticky headers, sorting, hover effects, responsive cards on mobile
- Fully **mobile responsive** with collapsible sidebar

---

## Default Login

| Role  | Email             | Password    |
|-------|-------------------|-------------|
| Admin | `admin@club.com`  | `admin123`  |

New members created from the admin panel get the default password `member123` (they can change it from their profile).

---

## Installation (XAMPP)

1. **Install XAMPP** (with Apache + MySQL + PHP 8+) from <https://www.apachefriends.org>.
2. **Start** Apache and MySQL from the XAMPP Control Panel.
3. **Copy the project folder** `club-management` into your XAMPP `htdocs` directory:
   - Windows: `C:\xampp\htdocs\club-management`
   - macOS: `/Applications/XAMPP/htdocs/club-management`
   - Linux: `/opt/lampp/htdocs/club-management`
4. **Import the database**:
   - Open <http://localhost/phpmyadmin>
   - Click **Import**
   - Choose the file `database/club_management.sql` inside this project
   - Click **Go** — this creates the `club_management` database with all tables, relationships and seed data
5. **Open the app** in your browser: <http://localhost/club-management>

You'll see the new landing page. Click **Sign In** to access the portal. No configuration changes are needed — the database credentials use XAMPP defaults (`root`, no password).

> If your MySQL root has a password, edit `includes/config.php` and set `DB_PASS`.

---

## Folder Structure

```
club-management/
├── index.php                 # landing page (home) - redirects logged-in users to dashboard
├── login.php                 # split-screen login page
├── logout.php                # destroys session
├── dashboard.php             # routes admin/member to their dashboard
├── forgot-password.php       # password reset
│
├── admin/
│   ├── dashboard.php         # admin dashboard - animated stats, circular progress, charts, widgets
│   ├── members.php           # members CRUD (AJAX)
│   ├── plans.php             # membership plans CRUD (AJAX)
│   ├── payments.php          # payments management (AJAX)
│   ├── attendance.php        # attendance marking (AJAX)
│   ├── events.php            # events CRUD (AJAX)
│   ├── announcements.php     # announcements CRUD (AJAX)
│   ├── reports.php           # reports + CSV/print export
│   ├── settings.php          # club + admin profile + password
│   └── profile.php           # admin profile view
│
├── member/
│   ├── dashboard.php         # member dashboard - cover, badge, charts, timeline
│   ├── profile.php           # edit profile + cover + activity timeline + history
│   ├── payments.php          # own payment history
│   ├── attendance.php        # own attendance history
│   ├── events.php            # browse & register for events
│   ├── announcements.php     # read announcements
│   └── change-password.php   # change own password
│
├── assets/
│   ├── css/style.css         # full theme (light + dark, glassmorphism, new palette)
│   ├── js/app.js             # toasts, modals, AJAX, dark mode, counters, calendar, sidebar
│   ├── images/avatar.png     # default avatar
│   ├── images/index.php      # directory guard
│   └── uploads/              # member/admin uploaded photos (auto-created)
│       └── index.php
│
├── includes/
│   ├── config.php            # DB credentials + constants
│   ├── database.php          # PDO connection
│   ├── auth.php              # login, logout, RBAC, session timeout
│   ├── functions.php         # helpers: validation, stats, uploads, logs
│   ├── header.php            # top navbar + notifications + messages + profile dropdown
│   ├── sidebar.php           # animated collapsible sidebar
│   └── footer.php            # closing tags + back-to-top + JS includes
│
├── database/
│   └── club_management.sql   # full schema + seed data
│
└── README.md
```

---

## Database Schema

Tables created by `database/club_management.sql` (unchanged from the original to preserve data):

| Table                 | Purpose                                      |
|-----------------------|----------------------------------------------|
| `admins`              | Admin accounts (login)                       |
| `members`             | Club members (login + profile)               |
| `membership_plans`    | Plans (Monthly, Quarterly, Half Yearly, Yearly) |
| `payments`            | Payment transactions + receipt numbers       |
| `attendance`          | Daily check-ins (present / late / absent)    |
| `events`              | Club events                                  |
| `event_registration`  | Member registrations for events              |
| `announcements`       | Published announcements                      |
| `settings`            | App configuration (key/value)                |
| `activity_logs`       | Audit trail of user actions                  |

All tables use **InnoDB**, proper **primary keys**, **foreign keys**, **indexes** and `utf8mb4` charset. The database structure and all relationships are preserved from the original project.

---

## Technology Stack

- **Frontend**: HTML5, CSS3, Vanilla JavaScript, Chart.js, Font Awesome 6, Google Fonts (Outfit + Inter)
- **Backend**: PHP 8+ (PDO)
- **Database**: MySQL / MariaDB (XAMPP)
- **Server**: Apache (XAMPP)

No build step. No npm. No frameworks. Drop into `htdocs` and it runs.

---

## Security Notes

- All database queries use **PDO prepared statements**.
- Every form includes a **CSRF token** verified server-side.
- All output is escaped with `htmlspecialchars()` to prevent XSS.
- Passwords are hashed with `password_hash()` (bcrypt).
- Sessions use HttpOnly cookies and regenerate on login.
- Admin-only pages enforce `require_role('admin')`.
- Upload directories contain an `index.php` guard returning 403.

---

## License

Free to use for educational and portfolio purposes. Provided as-is.
