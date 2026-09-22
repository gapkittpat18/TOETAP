# TOETAP Auto Deploy

This workflow deploys every push to `main` to the live TOETAP server.

## One-time GitHub setup

Open:

`TOETAP -> Settings -> Secrets and variables -> Actions -> New repository secret`

Create these secrets:

- `TOETAP_FTP_SERVER`
  - Example: the FTP/FTPS hostname supplied by your hosting company.
- `TOETAP_FTP_USERNAME`
- `TOETAP_FTP_PASSWORD`
- `TOETAP_FTP_PROTOCOL`
  - Usually `ftps` for secure FTP.
  - Use exactly the protocol supported by your host.
- `TOETAP_FTP_PORT`
  - Usually `21` for FTP/FTPS.
- `TOETAP_FTP_SERVER_DIR`
  - The directory that currently contains the live TOETAP files.
  - Example only: `/public_html/tapsole/`
  - Use the actual path from your hosting account.

## Safety

The workflow intentionally does NOT deploy or delete:

- production `.env`
- production database credentials
- production Strava credentials
- uploaded shoe photos
- SQL migration files
- README files

`dangerous-clean-slate` is disabled so server-only files are not wiped.

## Deployment flow

After setup:

1. Commit/push code to `main`.
2. GitHub Actions starts `Deploy TOETAP to app.toetap.run`.
3. Changed application files are uploaded to the server.
4. Test at:
   `https://app.toetap.run/tapsole/index.php`

You can also deploy manually from:
`GitHub -> Actions -> Deploy TOETAP to app.toetap.run -> Run workflow`

## First deployment

Before the first automatic deployment, make a backup of the existing live `/tapsole/` directory.

The workflow assumes the existing production server already has its own database/Strava configuration and uploaded files.
