# PaperTrail

PaperTrail is a highly optimized client-side document formatter. It allows you to convert messy raw spreadsheets (Excel, CSV, TSV) into elegant, print-ready PDF documents instantly. All file parsing, styling, and PDF exports are handled 100% locally in your web browser. Your private corporate financials never leave your machine.

---

## Technical Stack
- **Backend Framework:** Laravel 13 (PHP 8.4)
- **Frontend Utilities:** Alpine.js, Vanilla CSS, Tailwind CSS v4
- **Database Engine:** SQLite (local telemetry logging)
- **Local Dev Bundling:** Vite

---

## Features
- **Auto Type-Detection:** Financial fields auto-align right, text headers stay left, and date inputs format cleanly.
- **Interactive Workspace:** Drag column headers to reorder, rename headers, toggle column visibilities, and resize fonts.
- **Diverse Letterhead Layouts:** Cycle through 10+ professional editorial letterhead templates instantly.
- **Top Accent Color Bar:** Optional color accent horizontal strip matching your company branding.
- **Dynamic File Naming:** Printed PDF suggests clean standard formats: `CompanyAcronym_DocTitle_Date` (e.g. `VT_Statement_13 Jul 2026`).
- **Telemetry Counter:** Direct SQLite logging pipeline counts pages printed and rows loaded without collecting any file content or user details.

---

## Local Installation Guide

Follow these steps to set up and run PaperTrail locally on your machine.

### Prerequisites
Make sure you have the following installed:
1. **PHP 8.4**
2. **Composer**
3. **Node.js & npm**
4. **SQLite** (or Laravel Herd / Valet)

### 1. Clone & Set Up Directory
Navigate to your projects directory and install all requirements.

```bash
# Clone the repository
git clone https://github.com/mxTuhin/PaperTrail.git
cd PaperTrail
```

### 2. Automatic One-Click Setup
PaperTrail includes a custom setup script that installs dependencies, prepares the database, generates keys, and builds assets automatically.

```bash
composer run setup
```

*If you prefer manual steps, run:*
```bash
composer install
cp .env.example .env
php artisan key:generate
touch database/database.sqlite
php artisan migrate --force
npm install
npm run build
```

### 3. Running the Server Locally
To launch the server, Vite compiler, and background workers simultaneously, execute:

```bash
composer run dev
```

The application will be served at `http://127.0.0.1:8000` (or your local domain setup).

---

## Admin Panel Access

PaperTrail includes a minimal admin panel at `/admin` to monitor telemetry stats (total uploads, prints, and parsed data counts).

1. Go to `http://127.0.0.1:8000/admin` in your browser.
2. Enter the Basic Authentication credentials:
   - **Username / Email:** `admin@papertrail.test`
   - **Password:** `admin123`

To reset or update the admin credentials via Tinker, run:
```bash
php artisan tinker --execute '$user = App\Models\User::where("email", "admin@papertrail.test")->first(); if ($user) { $user->password = Illuminate\Support\Facades\Hash::make("YOUR_NEW_PASSWORD"); $user->save(); }'
```

---

## Privacy & Security Policy
- **Offline Processing:** Spreadsheets are read and parsed completely inside the client browser. No sheets or financial row details are uploaded or stored.
- **Anonymized Metadata Telemetry:** The only data logged is an anonymous usage counter (total row count, columns count, and event action types) saved to the local SQLite database.

---

## License

This project is licensed under a proprietary **End User License Agreement (EULA) — Personal Use Only**.

- **Personal Use:** Free to install and run on your own personal devices for private, non-commercial, and educational purposes.
- **No Distribution:** Sharing, copying, publishing, or distributing this software to any third party is strictly prohibited.
- **No Commercial Use:** Using this software for business operations, revenue generation, or professional services is strictly prohibited.
