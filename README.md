# CvSU Campus Event Management

A lightweight PHP event management system for Cavite State University.

## Overview

This repository contains a simple native PHP application for managing campus events, organizer registration, participant signups, attendance tracking, and reporting.

## Features

- Public event listing and event detail pages
- Organizer registration request workflow
- Admin approval for new organizer accounts
- Event creation, editing, and deletion
- Participant signups and QR code attendance tracking
- Activity logs and exportable reports
- Simple admin dashboard and site navigation

## Requirements

- PHP 7.4+ (XAMPP recommended)
- MySQL / MariaDB
- Web server with PHP support (Apache via XAMPP)

## Setup

1. Copy the project into your web server root, e.g. `C:\xampp\htdocs\campus-events`.
2. Create a database named `campus_events`.
3. Import the SQL schema from `database/schema.sql` into the database.
4. Update database settings in `config/database.php` if needed.
5. Open the site at `http://localhost/campus-events`.

## Default Admin Access

The application uses a seeded admin account in `database/schema.sql`.

- Email: `admin@cvsu.edu.ph`
- If the password is unknown, use the reset tool below to set a new one.

## Reset Admin Password

Use the built-in reset page:

- `http://localhost/campus-events/setup/reset-admin.php`

Enter the admin email and a new password to update the admin account.

## Organizer Registration Workflow

- Public users can register as organizers via `register.php`.
- Organizer accounts are created as pending (`is_active = 0`).
- Admin must approve organizer requests from the admin panel.
- Pending organizer applications are not able to log in until approved.

## Admin Permissions

- Only users with `role = admin` see and can access the organizer request approval page.
- Organizer users use the admin dashboard for their approved event management tasks.

## Notes

- Temporary debugging files have been removed from the repository.
- The app is designed for local development and testing in XAMPP.
