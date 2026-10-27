# 97 Foundation Professional V7.3

Version: **Professional V7.3 — Scholarships & Education Support, Footer Update**

V7.3 adds an online Student Scholarship & Education Support application (Scholarships page) and an Admin → Education Support → Scholarship Applications workspace where the team reviews, rates, filters and manually decides every application — nothing is approved automatically. Parent/guardian NIC numbers are stored encrypted and only authorised administrators (Super Admin and Administrator roles) can see applications. The website footer is redesigned and Privacy Policy and Terms & Conditions pages are added. **The database update applies automatically on the first page load** (or run `php database/upgrade_v7_3.php`, or import `database/upgrade_v7_3.sql` in phpMyAdmin). See `CHANGELOG.md`.

**Back up `storage/private/personal-data.key` together with the database.** It is created automatically the first time an application is saved; without it, stored NIC numbers cannot be read. (Alternatively set a long `PERSONAL_DATA_KEY` environment variable on the server before the first application.)

Previous release: **Professional V7.2 — Homepage, Donations, Fundraising, News & SEO Update**

V7.2 redesigns the homepage with a calm motion system, makes a payment receipt mandatory for every bank-transfer donation, adds a global fundraising on/off switch, upgrades News & Blog (categories, readable web addresses, publish/unpublish, article pages with contents and related posts), adds share buttons and strengthens SEO. **The small database update applies automatically on the first page load** (or run `php database/upgrade_v7_2.php`). See `CHANGELOG.md`.

## Testing

- Quick checks: `php tests/v7_3_features_static.php`, `php tests/v7_2_features_static.php` (and the other files in `tests/`).
- Browser tests (Playwright): `npm install --no-save playwright && npx playwright install chromium`, then
  `BASE_URL=http://localhost/97-foundation ADMIN_COOKIE="foundation97_session=<your session id>" node tests/e2e/site.e2e.js`.
  Sign in to the Admin normally first and copy the `foundation97_session` cookie; the Admin CAPTCHA blocks automated sign-in.
  The suite creates "E2E …" test records, so run it against a test copy of the database.

Previous release: **Professional V7.1 — Header, Navigation, Hero & Admin Update**

V7.1 delivers the new header (contacts, logo, foundation text image, Donate Now / Login), the new navigation with About Us and Members dropdowns, the five-image hero slider, Officials / Past Officials / What We Do pages and a professional Admin design. **Existing installations: run `php database/upgrade_v7_1.php` once, or use Admin → Branding & SEO → Apply Update.** See `CHANGELOG.md`.

Previous release: **Professional V6.8 — Member Production Readiness**

V6.7 focuses on reliable member registration/login, Admin approval and publication, shared-host CSRF/session resilience, public member visibility, secure 8–12 character member passwords, mobile member UI and professional production error handling.

**Existing installations must run `php database/upgrade_v6_7.php` once after uploading this release.** Then run the read-only `php tools/member-production-check.php` before launch.

# 97 Foundation — Professional Charity Platform

This package upgrades the existing 97 Foundation PHP/MySQL system with a more complete public website, Admin CMS, fundraising workflow, Foundation Member directory, Volunteer/Member portals, QR verification, News & Blog, social-media management, live website chat, responsive pagination and professional glassmorphism UI.




## V6.3 highlights

- Simplified **Developer Integration → Easy Payment Setup** into a three-step wizard.
- Normal PayHere hosted checkout needs only Merchant ID + Merchant Secret; API App credentials stay optional under Advanced Settings.
- Added **Save & Test Setup**, clear readiness indicators and automatic display of webhook/return/cancel URLs.
- Added secure automatic payment encryption-key generation inside protected `storage/private` when no server key is configured.
- Simplified the public Online Donation form to Amount → Essential Donor Details → Review & Payment.
- No new database migration beyond `database/upgrade_v6_2.php`.

## V6.0 highlights

- Rebuilt Admin and Member/Volunteer Portal notifications into a professional in-page Notification Centre opened directly from the header bell.
- Complete notification content can be read without leaving the current page, with clear read/unread states, date/time, notification type and optional related-item links.
- Added individual **Mark as Read**, **Mark All as Read**, All/Unread filtering, internal scrolling and progressive loading for older notifications.
- Added responsive desktop/tablet/mobile notification drawer behavior, subtle animations and dynamic unread-count synchronization across notification badges.
- Redesigned the full Notifications pages in both Admin and Portal while preserving pagination.
- No database migration is required for V6.0.


## V5.9 highlights

- Expanded Foundation-logo usage across Member Registration, Member Login, Portal Login, member/volunteer fallbacks, messaging recipient chips and campaign management.
- Added Admin-managed **Foundation Portal Login Image**, **Member Login Side Image**, **Admin Login Background** and professional browser tab favicon management under **Branding, Visuals & SEO**.
- Rebuilt the Admin Login screen into a premium split visual/sign-in experience; if no custom background is uploaded, a professional green glassmorphism fallback is rendered automatically.
- Added local **SEO + AEO controls for Sri Lanka**, with Polonnaruwa / Thambala defaults, keywords, answer summary, local structured data and WebSite/NGO schema.
- Campaign cover images now refresh immediately after replacement through versioned public-upload URLs; the Admin campaign editor shows the current cover image.
- Existing V5.8 databases should run `php database/upgrade_v5_9.php` once.

## V5.8 highlights

- Rebuilt the public **Member Registration** page with professional sectioned forms, responsive validation-friendly inputs, secure CAPTCHA and clear membership guidance.
- Rebuilt the separate **Member Login Portal** with a premium NGO/SaaS login experience.
- Fixed spacing between **Register as a Member** and **Member Login** buttons.
- Fixed the floating **WhatsApp** action to the bottom-left on desktop and mobile.
- Removed the always-visible optional 2FA field from Admin Login. Accounts with 2FA enabled now receive a dedicated second-step authenticator challenge after successful password/CAPTCHA verification.
- Kept Admin **Security & 2FA** as the place to enable/disable authenticator protection after sign-in.
- Refined the authenticated Member Portal dashboard, sidebar icons, identity summary, announcements, events, notifications and responsive layouts.
- Added file-modification cache busting for CSS/JS assets so browsers receive updated UI files after deployments.
- No new database migration is required beyond the existing V5.7 database upgrade.

## V4.8 highlights

- Added a dedicated **System → Account Security** page for administrator sign-in email and password changes, with current-password verification, Argon2id hashing, session rotation and direct 2FA access.
- Removed the credential editor from My Profile so profile information and account security are professionally separated.
- Rebuilt **Donation Verification** into a premium SaaS-style data workspace with status cards, keyword/date/status filters, server-side pagination and compact actions.
- Added full Admin **Donation CRUD**: Create, View, Edit/Update and Super Admin Delete, including receipt replacement and verification history.
- Added V4.8 static coverage checks for Account Security, Donation CRUD and existing core Admin CRUD resources.
- Existing V4.7 social icon, profile, Live Chat, Portal SPA, ID card and pagination improvements remain intact.

## V4.5 highlights

- Added an Admin-managed **Organization Website** field under Branding & SEO; the value updates official Member/Volunteer ID cards, downloaded PNG IDs, footer organization information and organization structured data.
- Public Foundation Members directory now uses compact professional cards with up to **5 members per row** on wide desktops and responsive 4/3/2/1-column breakpoints.
- Fixed and professionally redesigned the public Live Chat **closed conversation** UI so the closed notice is compact, styled, non-duplicative and includes a clear Start New Chat action.
- Replaced the oversized blocking Member Portal loading screen with a compact non-blocking SPA route loader.
- Preserved V4.3 CR80 printing/download, Portal SPA navigation, Admin chat-close workflow and V4.2 pagination.

## V4.3 highlights

- Volunteer administration now has two clearly separated People & Community submenu entries: **Volunteer List** and **Registration Management**.
- Removed emoji picker controls from both Admin Live Chat and the public website Live Chat composer.
- Added a dedicated **Close Chat** workflow: closing a conversation prevents further replies, writes a system notice into the thread, and professionally notifies the public visitor with a closed-conversation state and Start New Chat action.
- Administrator Profile now supports secure email changes and optional password changes after current-password verification, uniqueness checks, Argon2id hashing and session rotation.
- Slowed and refined nested Admin sidebar animation for smoother production SaaS behavior.
- Member/Volunteer Portal sidebar navigation now uses Fetch + History API SPA navigation with branded logo spinner and full-page fallback.
- Rebuilt Member/Volunteer ID into compact international **CR80 (85.60 × 53.98 mm)** proportions with Foundation logo, verification QR, Print ID and high-resolution Download PNG actions.
- V4.2 pagination remains standard across paginated Admin data areas.

## V4.2 highlights

- Standardized server-side pagination across all paginated Admin data modules with visible record ranges, page navigation, page-size selectors, and query/filter preservation.
- Live Chat conversation pagination now supports AJAX page-size changes without reloading.
- Public Live Chat widget now has two exclusive UI states: `start` and `active`; after Start Chat succeeds, the complete registration/welcome form disappears.
- Active Live Chat uses a fixed conversation workspace with message-only scrolling, fixed composer, date separators, timestamps, Enter-to-send and Shift+Enter new line.
- Admin sidebar expandable groups now animate smoothly with height, fade, slide and chevron transitions.
- Website Content Manager keeps the correct tab visible when using independent pagination for Pages, Content Sections or Hero Slides.

## V4.1 highlights


### V4.1 professional live-chat workspace

- One-screen fixed Admin Live Chat workspace with no browser-level scrolling on desktop
- Three-panel Conversations / Chat / Visitor Details SaaS layout with independent internal scrolling
- AJAX conversation switching, status filtering, search, pagination, replies, status changes, notes and deletion
- Automatic 4-second message refresh with scroll-to-latest restricted to the message area
- Presence tracking, shared support unread tracking and persistent internal support notes
- Enter-to-send, Shift+Enter new line, loading/disabled states and responsive panel switching
- Premium light nested Admin sidebar with expandable Fundraising, People & Community, Support & Messages, Content & Website and System groups
- Public footer developer credit updated to “Fully Developed by 97 Foundation IT Team.”

### Public website

- Premium responsive charity UI with glassmorphism, photographs, hover motion and branded loading animation
- SPA-like public navigation using Fetch + History API with full-page fallback
- Correct active state in the public navbar
- Admin-uploaded Foundation logo used across website/admin/portal
- Separate Cause Areas and Fundraising Campaigns
- Campaigns can be Draft / Upcoming / Active / Completed / Cancelled and Public / Hidden
- Gallery and Events pages
- **Foundation Members** page with admin-approved public member photos/details
- **News & Blog** navigation page plus automatic latest-post display on Home
- SEO: titles, descriptions, canonical URLs, Open Graph/Twitter metadata, sitemap and robots.txt
- AEO-friendly managed page answer summaries and structured data
- Responsive live chat widget connected to the Admin Panel

### Donations

- Admin-managed donation bank/account details and contact person
- Public Donate page intentionally displays the required bank/account fields without IBAN or SWIFT/BIC
- Campaign selection kept separate from account selection
- Secure JPG/PNG/PDF receipt/payment-proof upload
- Receipt/proof opens in a new browser page for authorized administrators
- Pending / Approved / Rejected / Refunded / Cancelled verification workflow
- **Only Approved donations count toward public fundraising totals**
- Financial donation records are retained for audit integrity rather than being casually hard-deleted

### Volunteers

- Registration requires email and password
- Account remains pending until administrator approval
- Admin can search, paginate, edit volunteer profile data, approve/reject/suspend/reactivate and (Super Admin only) delete
- Approval activates login, creates permanent `97V-YYYY-000001` style Volunteer ID and secure verification token
- Volunteer portal includes Dashboard, Profile, Messages, Notifications, Password Change and professional Digital ID
- Digital ID includes a **real locally generated QR code**
- Scanning QR opens the public verification page and reveals only approved safe identity/status information

### Foundation members

- Separate **Admin → Members** module
- Admin can create/update member account, member photograph, role/title, public bio, status and public-directory visibility
- Member IDs use `97M-YYYY-000001` style numbering
- Every member receives a secure verification token and QR-enabled digital member ID
- Scanning member QR opens the public member-verification page
- Members use the same secure `/login` portal and can message administrators

### News, blog and social media

- Full News & Blog CRUD in Admin
- News / Blog type, draft/published/archived state, publish date, featured image, excerpt, article text and SEO fields
- Published posts automatically surface on the homepage
- Public `/news` page and `/news/{slug}` article pages
- Full Social Media link CRUD with platform, label, URL, icon/letters, order and active/inactive state
- Active social links automatically appear in the website footer

### Live Chat

- Floating public chat widget
- Visitor name/email/phone/topic/message capture
- Secure random conversation token
- Admin conversation list with pagination and status filtering
- Admin reply, Open/Waiting/Closed state and Super Admin delete
- Visitor polling for replies without page refresh
- New-chat admin notifications

### Admin UX

- Correct sidebar active state based on actual current URL
- Pagination added to major data-heavy screens including Campaigns, Causes, Donations, Volunteers, Members, Gallery, Events, Portal Messages, Notifications, News/Blog, Social Links and Live Chat
- Responsive create/edit forms and cards
- Working Admin profile/avatar and notification centre
- Four-digit numeric CAPTCHA on Admin and Member/Volunteer login
- Optional TOTP administrator 2FA
- Non-IT friendly editors for content, pages, hero slides, branding, campaigns, events, gallery, donation settings, people, posts and social links

## Security controls

- PDO prepared statements with emulated prepares disabled
- CSRF checks on state-changing forms and chat writes
- Context-escaped HTML output
- Argon2id password hashing
- Numeric CAPTCHA and login throttling
- Session regeneration after login
- HttpOnly / SameSite cookies and Secure cookies over HTTPS
- Server-side role/permission enforcement
- Optional TOTP administrator 2FA
- Secure MIME-based uploads with randomized filenames
- Private receipt/payment-proof storage outside public uploads
- Authorization-protected receipt/proof viewing route with path validation
- Security headers / CSP
- Audit logs for sensitive actions
- QR contains a verification URL/token only — no password, email, telephone or private address

## Requirements

- PHP **8.3+**
- MySQL 8+ or compatible modern MariaDB
- PDO MySQL and `fileinfo`
- Apache `mod_rewrite`
- HTTPS for production

## New XAMPP installation

Extract to:

```text
C:\xampp\htdocs\97-foundation\
```

Import:

```text
database/schema.sql
```

Optional demonstration records:

```text
database/sample_data.sql
```

Create the first administrator:

```bash
php database/create_admin.php admin@example.com "Super Administrator" "A-Strong-Password!2026"
```

Open:

```text
http://localhost/97-foundation/
http://localhost/97-foundation/admin/login
http://localhost/97-foundation/login
```

The application automatically detects the XAMPP sub-folder for normal localhost use.

## Upgrade from Professional V2

**Back up the database, `public/uploads` and `storage/private` first.** Replace the application code with this V3 package while preserving your real upload/private files, then run from the project directory:

```bash
php database/upgrade_v3.php
```

The V3 upgrade adds Member verification/profile fields plus Social Links, News/Blog and Live Chat tables without intentionally deleting existing administrators, campaigns, donations, members or volunteers.

If upgrading from an older pre-V2 package, run the previous upgrade first:

```bash
php database/upgrade_v2.php
php database/upgrade_v3.php
```

After replacing files, use **Ctrl + F5** once.

## Recommended Admin setup order

```text
/admin/branding          Logo, Foundation contact and SEO
/admin/content           Managed pages, pictures, homepage content and hero slider
/admin/social            Social media links
/admin/causes            Cause areas
/admin/campaigns         Fundraising campaigns
/admin/posts             News & Blog
/admin/gallery           Gallery
/admin/events            Events
/admin/members           Foundation member directory + portal IDs
/admin/volunteers        Volunteer applications + portal IDs
/admin/live-chat         Website live chat
/admin/messages          Member/Volunteer portal messages
/admin/donation-settings Donation bank details + donation contact person
/admin/donations         Receipt/proof review and donation verification
/admin/security          Admin 2FA
```

## Production note

Application security is only one layer. For a public launch use HTTPS, patched server/PHP/database software, a least-privilege database account, protected backups, server monitoring/logs, upload malware scanning where available and an independent security assessment. See `docs/SECURITY.md` and `docs/DEPLOYMENT.md`.

## V4 upgrade
If you are upgrading the Professional V3 package, back up the database and uploads first, replace the application files, then run:

```bash
php database/upgrade_v4.php
```

For installations older than V3, run the upgrade scripts in order:

```bash
php database/upgrade_v2.php
php database/upgrade_v3.php
php database/upgrade_v4.php
```

V4 adds a campaign Active/Inactive state, administration indexes for large paginated lists, and re-checks the Live Chat tables so an incomplete older chat migration can be repaired.


## V5.0 Upgrade
For an existing database, run once:
```bash
php database/upgrade_v5.php
```
This normalizes currency data to LKR and creates broadcast-message history support.

## V5.4 Home Page Builder upgrade
For an existing V5.x database, run once:

```bash
php database/upgrade_v5_4.php
```

This adds the homepage section eyebrow and dynamic item-limit fields, then seeds the professional Home Page Builder defaults. Homepage About imagery, section content, visibility, calls-to-action and dynamic listing limits can then be managed from **Admin → Content & Website → Website Content Manager → Home Page**.


## V5.7 highlights

- Added secure public Member Registration with DOB, NIC/National ID, gender, contact, profile photo, username, email and Argon2id password handling.
- Generates a unique Member ID and QR verification token immediately after registration; new registrations enter an administrator approval workflow.
- Added a separate Member Login Portal supporting username or email sign-in and an upgraded member dashboard with ID/QR, personal information, notifications, announcements, events and causes.
- Admin Member Management now includes Registration Management, search by Member ID/NIC/username/email, approve/reject/activate/deactivate/suspend controls, QR download and secure password reset.
- Added an Admin-managed floating WhatsApp contact button on the public website.
- Hero Slider now supports separate desktop and mobile images, optional schedule dates, desktop/mobile preview and drag-and-drop display ordering.
- Added broad tablet/mobile responsive refinements across public inner pages, forms, cards, grids and the member portal.
- Existing databases must run `php database/upgrade_v5_7.php` once after updating.

## V6.2 — Secure Online Donations / Payment Gateway

V6.2 adds a modular Sri Lankan online donation flow with PayHere hosted checkout support, server-side callback verification, transaction management, dashboard statistics, receipts, status retrieval/refunds, and a separate **Developer Integration → Payment Gateway** admin section.

For an existing V6.1 database run once:

```bash
php database/upgrade_v6_2.php
```

Before storing payment secrets from Admin, configure a private server environment key of at least 32 characters:

```text
PAYMENT_ENCRYPTION_KEY=your-long-random-server-only-secret
```

See `docs/PAYMENT_GATEWAY.md` for sandbox, live-mode, callback, security and testing guidance.
