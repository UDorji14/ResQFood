# ResQFood

ResQFood is a role-based food redistribution platform that helps businesses share surplus food with nearby users and charities, while giving admins moderation and operational control.

## Highlights

- Multi-role platform: `business`, `general_user`, `charity`, `admin`
- Secure authentication, role-based access control, session handling
- Food listing lifecycle: create, browse, reserve, collect, expire, cancel
- Partial quantity reservations with availability tracking
- Reservation workflows with pickup confirmation and status logs
- User-to-admin reporting workflow with moderation tools
- Admin management screens for users, listings, and reports
- Impact metrics for completed pickups (estimated meals, kg saved, CO2 reduced)

## Tech Stack

- **Backend:** PHP (procedural/modular, no framework)
- **Database:** MySQL (PDO, prepared statements, transactions)
- **Frontend:** HTML, CSS, vanilla JavaScript
- **Environment:** XAMPP (Apache + MySQL + PHP)

## Project Structure

```text
ResQFood/
|- assets/                 # CSS/JS and static assets
|- config/                 # DB configuration
|- database/               # SQL schema and DB resources
|- includes/               # Reusable helpers (auth, csrf, validation, etc.)
|- modules/
|  |- admin/               # Admin pages and actions
|  |- listings/            # Listing CRUD and browse
|  |- reservations/        # Reservation flow
|  |- reports/             # User reporting module
|  |- profile/             # User profile management
|  |- dashboard/           # Dashboard/impact views
|- partials/               # Shared layout partials and role shells
|- uploads/                # Uploaded listing images
|- index.php               # Public landing page
|- login.php / register.php
|- dashboard.php           # Role-aware authenticated entry
```

## Core Modules

- **Authentication:** login, registration, logout, role guards
- **Business Operations:** create/manage listings, confirm pickups
- **Community/Charity:** browse food, reserve quantity, track reservations
- **Reports:** submit reports, track status, moderate in admin panel
- **Administration:** user management, listing oversight, reports moderation

## Security Practices

- Prepared statements via PDO
- CSRF token verification on state-changing requests
- Output escaping (`htmlspecialchars`)
- Server-side validation for all forms
- Session hardening and role/permission checks

## Setup (Local - XAMPP)

1. Place the project in your web root, for example:
   - `D:/xamp/htdocs/ResQFood`
2. Start **Apache** and **MySQL** from XAMPP.
3. Create a database (default used in project): `resqfood_db`.
4. Import:
   - `database/resqfood_schema.sql`
5. Update DB credentials in:
   - `config/db.php`
6. Open:
   - `http://localhost/ResQFood/`

## Default Routing

- Public landing: `index.php`
- Auth: `login.php`, `register.php`
- Authenticated entry: `dashboard.php`
- Admin: `modules/admin/dashboard.php`

## Notes

- Admin creation and password reset scripts are intentionally not included in production code paths.
- Temporary migration/debug scripts were removed to keep the repository clean.