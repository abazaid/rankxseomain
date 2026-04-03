# RankX Standalone

Standalone SEO SaaS platform with direct email registration.

## Main Features
- Client authentication (register/login/forgot password/reset password)
- Dashboard sections:
  - Home
  - SEO Settings
  - Products SEO (keyword-driven generation)
  - Keywords Research
  - Domain SEO
  - Brands SEO (keyword-driven generation)
  - Categories SEO (keyword-driven generation)
  - Operations Log
  - Account Settings
- Admin dashboard and quota management
- OpenAI generation and DataForSEO analysis

## Tech
- PHP 8+
- MySQL/MariaDB
- OpenAI API
- DataForSEO API
- SMTP for password reset email

## Quick Start
1. Copy `.env.example` to `.env`
2. Configure database and API credentials
3. Import `database/schema.sql`
4. Serve `public/index.php`

## Notes
- This version does not use external marketplace integration callbacks.
- Data is stored in DB plus `storage/stores.json` for app settings/state.
