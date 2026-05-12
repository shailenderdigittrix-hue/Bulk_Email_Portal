# Bulk Email Management Portal

A PHP-based bulk email management system that allows you to manage contacts, create email templates, run campaigns, and track delivery stats in real time.

## Features

- Upload and manage contacts (CSV import)
- Create and edit email templates
- Create and run email campaigns
- Real-time campaign stats (Sent / Pending / Failed)
- Cron-based email sending via PHPMailer / SendGrid
- Paginated contacts dashboard
- Note: For testing purposes, email sending is configured using Gmail SMTP. Please use your Gmail ID and App Password for testing.

## Cron Job Setup

Set up a cron job on your server to handle background email sending:

```bash
* * * * * /usr/bin/php /var/www/html/my_php_project/cron/send_emails.php
`Runs every minute`

## Tech Stack
- PHP(8.3.29), MySQL, PDO
- Bootstrap 5
- PHPMailer
- jQuery (AJAX stats polling)

## Setup

1. Import `schema.sql` into MySQL to create the `bulk_email_portal` database.
2. Configure DB credentials in `config/database.php`.
3. Run `composer install` to install dependencies.
4. if PHPMailer is not already installed, run: `composer require phpmailer/phpmailer`.
5. Set up a cron job to run `cron/send_emails.php` for background email sending.
6. Serve via MAMP (or any PHP server) pointing to this directory.

## Database Tables

| Table | Description |
|---|---|
| `contacts` | Stores imported contacts |
| `email_templates` | Stores reusable email templates |
| `campaigns` | Stores campaign records |
| `campaign_recipients` | Tracks per-recipient send status |
| `email_logs` | Logs all email send attempts |


## File Structure
```
bulk-email-portal/
│
├── config/
│   └── database.php
│
├── uploads/
│
├── templates/
│
├── cron/
│   └── send_emails.php
│
├── index.php
├── contacts.php
├── upload_contacts.php
├── templates.php
├── campaigns.php
├── send_campaign.php
├── functions.php
└── schema.sql
```


composer require phpmailer/phpmailer
