# Security Hardening Checklist

V2 includes significant application-level protections. Production security still depends on the web server, operating system, database, hosting control panel and operational practices.

## Included in this codebase

- Prepared PDO queries (`ATTR_EMULATE_PREPARES=false`)
- CSRF validation on state-changing forms
- HTML output escaping for dynamic values
- Argon2id password hashing
- 4-digit numeric login CAPTCHA for administrator and member/volunteer login
- Login throttling
- Session regeneration after successful login
- HttpOnly and SameSite session cookies; Secure cookies on HTTPS
- Optional TOTP 2FA for administrator accounts
- Server-side role/permission enforcement
- MIME validation and random names for uploads
- Private donation-proof storage outside public uploads
- Protected proof download controller with real-path validation
- Content Security Policy, frame denial, no-sniff, referrer and permissions headers
- Audit logs for important actions
- Apache deny rules for `app`, `database`, `storage`, `tests` and `docs`

## Required before production

- Serve the website only over HTTPS.
- Set `APP_ENV=production` and `APP_DEBUG=false`.
- Use a dedicated database account with least-privilege permissions; do not use MySQL `root` in production.
- Protect hosting/control-panel accounts with MFA.
- Enable administrator TOTP 2FA.
- Keep PHP, database software and the web server patched and security-supported.
- Configure encrypted backups and test restoration.
- Add server-side malware/antivirus scanning for uploaded documents where hosting supports it.
- Add CDN/WAF/bot controls for hostile public traffic when appropriate.
- Monitor audit logs, authentication failures, PHP logs and database errors.
- Perform independent vulnerability scanning and penetration testing before accepting real public donations.
- Review legal/privacy/donation policies for the applicable jurisdiction.

## Donation integrity

Pending donations are never included in public raised totals. Public totals use approved records only. Donation status changes occur inside database transactions and produce verification/audit history.

## QR/privacy model

The volunteer QR points to a public verification URL/token. The public verification response is deliberately limited to safe identity/status information; password, email, phone and address are not embedded in the QR URL.

## Future enterprise additions

For a larger deployment, consider verified-email/password-reset workflows, centralized database-driven RBAC, queue workers, local QR library, object storage with signed URLs, CSP nonces, dependency/SAST/DAST scanning, SIEM integration, immutable audit export and formal security review.
