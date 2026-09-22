# TOETAP V2.2.27 — Git Setup

This repository baseline is **TOETAP V2.2.27 SHOE IMAGE PRESENTATION**.

## Local configuration
The real configuration files are intentionally excluded from Git:

- `config/database.php`
- `config/strava.php`

Copy the corresponding `.example.php` files and fill credentials locally, or load values from environment variables.

## User uploads
`uploads/shoes/` is runtime/user data and is excluded from Git. The directory itself is retained with `.gitkeep` and `.htaccess`.

## Database
SQL schema/migration files remain versioned. Do not commit database dumps containing user data or Strava tokens.

## Security
The source archive used to create this baseline contained a Strava client secret in `config/strava.php`. That secret is not included in the Git-ready baseline. Rotate/revoke the old Strava client secret before using a public or shared deployment.
