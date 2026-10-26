## V7.2.1 — 2026-10-26 — Online payments switch and admin polish
- **Online card payments On / Off:** one switch, shown on Easy Payment Setup, Online Payment Transactions and Donation Page Settings (and summarised on the dashboard). It refuses to turn on until the PayHere Merchant ID and Secret are saved, and Live mode still requires HTTPS. Saving the setup form no longer changes the on/off state by accident.
- **Gallery and Cause Areas:** compact thumbnail grid instead of large photos, search and filters, visible/hidden counts, one-click show/hide, and the current image previewed when editing.
- **Events:** date-led event list with Upcoming / Past / Draft filters and a helpful empty state.
- **Portal Messages:** clean recipient list with initials, role badges, search, "Select shown", "Clear" and a selected count (fixes overlapping names and emails).
- **Live Chat:** fixed the oversized search box and page-size control, tidier header actions.
- **Payment setup steps and Branding & SEO:** fixed text that wrapped one word per line, the sticky Save bar now spans the page with a clear message, logo cards and checkboxes use the full width.
- **Fixes:** hidden gallery photos no longer disappear from the admin list; every "Clear" / "New" button resets its editor.

## V7.2 — 2026-10-26 — Homepage, donations, fundraising, News & SEO
- **Homepage redesign:** new section layouts, SVG icons instead of text symbols, real photography for demo images, one orchestrated hero entrance, staggered scroll reveals, count-up figures, animated campaign progress, parallax, gallery viewer with previous/next, smoother page transitions and a reading-progress bar. Motion turns off for visitors who prefer reduced motion. Inter is now self-hosted.
- **Mandatory receipts:** every bank-transfer donation needs a JPG, PNG, WEBP or PDF receipt (checked in the browser and on the server, up to the configured size). Drag-and-drop upload with preview and clear errors; typed details are kept if something needs correcting. Receipts stay private; admins see them on the donation page. Approval (payment verification) remains a separate step.
- **Fundraising switch:** Admin → Fundraising Campaigns → Public fundraising On / Paused. Paused hides campaign sections, blocks campaign donations (bank and online) and optionally keeps general donations open. No records are deleted.
- **News & Blog:** categories with their own pages, editable readable web addresses, one-click publish/unpublish, scheduling, search, formatting (headings, lists, links, quotes), SEO title/description with a search preview, redesigned listing and article pages with contents, reading time and related articles.
- **Sharing:** WhatsApp, Facebook, LinkedIn, X, Telegram, email, copy link and native device sharing on every article and campaign.
- **SEO:** article Open Graph tags, NewsArticle/BlogPosting and breadcrumb structured data, canonical pages for lists, noindex on sign-in and private pages, sitemap with last-modified dates, stricter robots.txt.
- **Admin dashboard:** receipts awaiting review, fundraising status, publishing summary and recent donations.
- **Fixes:** Escape now closes the desktop dropdowns and the mobile menu reliably; the homepage no longer reports the latest news post as its page type; footer no longer shows placeholder text or zero counts.
- **Tests:** `tests/v7_2_features_static.php` and a Playwright end-to-end suite in `tests/e2e/site.e2e.js`.

## V7.1.2 — 2026-10-25 — Administrators manager & sidebar profile
- New Admin → System → **Administrators** page (Super Admin only): add administrators, assign one of seven roles, set status, upload a profile photo, reset passwords with a strong-password generator, suspend/activate and delete accounts.
- Safeguards: you cannot change your own role, suspend or delete yourself, and at least one active Super Admin always remains. Every change is written to the audit log.
- Sidebar footer redesigned: profile card with photo or initials, name and role (opens My Profile) and a full-width Sign out button underneath.

## V7.1.1 — 2026-10-25 — Header logo size controls
- New Admin → Branding & SEO → **Header Logo & Size** card with live desktop/mobile preview, quick size presets, separate logo / text image / mobile heights, show-or-hide text image and compact-on-scroll options.
- Logos are now sized by height (no more square box), so wide or tall logo files display at full size.
- Uploaded logos can be trimmed of empty transparent space automatically (original file kept) and are stored web-sized.

## V7.1 — 2026-10-25 — Header, navigation, hero slider & professional Admin
- New top bar contacts (97found@gmail.com, +94 71 258 3411, Facebook page) with Material Symbols icons.
- New official logo and header foundation text image; Donate Now and Login buttons per Figma.
- New menu: Home · About Us (What We Do, 97 Foundation) · Members (Officials, Past Officials) · News & Blogs · Gallery · Volunteer · Contact Us, with accessible dropdowns and a mobile slide-in menu.
- Hero rebuilt as a five-image horizontal background loop with five centred pagination dots and a higher-opacity green overlay; fully managed from Website Content → Hero Banner.
- New What We Do, Officials and Past Officials pages, plus an Admin Officials manager.
- Professional Admin design layer (sidebar icons, breadcrumbs, readable typography, consistent forms, tables and badges).
- Run `php database/upgrade_v7_1.php` once, or use Admin → Branding & SEO → Apply Update.

## V4.4 — 2026-08-12
- Admin-managed organization website on official IDs.
- Five-column compact public member directory.
- Professional public Live Chat closed state.
- Compact non-blocking Portal SPA route loader.

# Changelog

## 2026-08-12 — Professional V4.3 Portal, Identity & Support Workflow Update

- Renamed the volunteer Admin submenu into separate **Volunteer List** and **Registration Management** workflows.
- Removed emoji picker UI and JavaScript from both Admin and public Live Chat composers.
- Added a dedicated Close Chat action, server-side closed-state enforcement, system timeline notices, visitor closed-state notification and Start New Chat flow.
- Added secure Admin account email/password update controls using current-password authorization, unique-email validation, Argon2id password hashing and session ID rotation.
- Refined nested sidebar motion to a smoother ~380–420ms production transition.
- Added SPA navigation to the Member/Volunteer Portal using Fetch, History API, active-menu synchronization and branded logo loading spinner.
- Rebuilt the Member/Volunteer identity card at CR80 proportions (85.60 × 53.98 mm), using the configured Foundation logo, compact QR verification, exact-size print CSS and high-resolution PNG download.
- Preserved the V4.2 standardized pagination system across Admin data modules.


## 2026-08-12 — Professional V4.2 Pagination, Widget State & Sidebar Motion Update

- Standardized professional pagination controls across every Admin module that manages paginated datasets.
- Pagination now remains visible even on a single page, showing record range, total records, page number and rows-per-page selector.
- Added automatic query/filter preservation and independent rows-per-page parameters for multi-list modules.
- Added AJAX rows-per-page control to Admin Live Chat conversation pagination.
- Rebuilt the public Live Chat widget around exclusive `start` and `active` states so the registration form never appears together with an active conversation.
- Added fixed active-chat composition, internal message-only scrolling, auto-scroll to latest, timestamps/date separators and Enter-to-send.
- Added smooth 200–300ms Admin sidebar submenu height/fade/slide animations and chevron rotation.
- Preserved Website Content Manager tab selection while paging Pages, Content Sections and Hero Slides.


## 2026-08-12 — Professional V4.1 Live Chat & Navigation Update

- Rebuilt Admin Live Chat as a fixed-height, no-page-scroll professional support workspace.
- Added three independent panels for conversations, chat messages and visitor details.
- Added AJAX status tabs, search, pagination, conversation selection, replies, status changes, notes and Super Admin deletion.
- Added automatic message polling and message-area-only scroll-to-latest behavior.
- Added visitor presence tracking, shared unread tracking and persistent internal support notes.
- Added date separators, timestamps, sent indicators, responsive panel switching and keyboard send behavior.
- Reworked Admin sidebar into premium expandable nested navigation groups inspired by modern SaaS dashboards.
- Updated public footer credit to “Fully Developed by 97 Foundation IT Team.”
- Extended the idempotent V4 database upgrade with the V4.1 live-chat columns and presence index.


## 2026-08-12 — Professional V3 Members, QR, News, Chat & Admin CRUD Update

- Added server-side pagination to major Admin data lists.
- Fixed Admin sidebar active-state logic so Dashboard is no longer highlighted on child modules.
- Added protected donation receipt/proof viewing in a new browser page.
- Removed IBAN and SWIFT/BIC from public/admin donation-account UI.
- Added Social Media link CRUD with active/inactive/order control.
- Added News & Blog CRUD, public News/Blog pages and automatic homepage latest-post feed.
- Added Website Live Chat with public widget, admin replies, status workflow, pagination and notifications.
- Added separate public Foundation Members directory with photographs, roles and public bios.
- Expanded Admin Member management with verification tokens and public visibility.
- Added permanent Member ID generation and member QR verification.
- Added actual locally generated QR codes to Member and Volunteer digital identity cards.
- Added public member and volunteer verification pages with privacy-safe details.
- Added Volunteer admin search/filter/pagination, profile editing and Super Admin deletion.
- Added Admin notification pagination and dashboard live-chat/member metrics.
- Added local QRCode.js dependency with MIT license.
- Added V3 database upgrade helper.

## 2026-08-12 — Professional V2 Portal + CMS Update

- Reworked public UI toward the supplied 97 Foundation reference style with professional glassmorphism and responsive animation.
- Added SPA-like public navigation with proper active navbar state and branded loading overlay.
- Added Gallery + admin gallery CRUD + category filtering/lightbox.
- Added Member Portal architecture and admin member management.
- Upgraded volunteer registration to require email/password; approval activates portal access.
- Added Volunteer Portal dashboard, profile, messages, notifications, password change and ID/QR page.
- Added admin ↔ volunteer/member secure messaging.
- Fixed admin profile and notification centre functionality.
- Added auto-generated 4-digit numeric CAPTCHA to admin and portal login.
- Added Page CMS feature-image upload, SEO metadata and AEO Quick Answer fields.
- Added admin-managed logo, social links and Open Graph/social image.
- Added Cause Areas as a separate module from Fundraising Campaigns.
- Added campaign public visibility (publish/hide) and status management.
- Added Events management and homepage event presentation.
- Connected homepage reusable content sections to Admin CMS.
- Added safer upgrade helper for previous database installations.
- Hardened Apache access to source/database/private folders.

## 2026-08-12 — Secure UI Update

- Added initial 97 Foundation public/admin UI and donation settings workflow.
- Added secure donation-proof upload and donation verification.
- Added approved-only public fundraising totals.
- Added initial volunteer workflow, audit logging, RBAC, security headers and optional TOTP 2FA.

## V4 — Professional UX, Chat & Administration Update
- Rebuilt the public footer as a responsive light glassmorphism footer with dynamic Foundation counts and a real donation QR code.
- Forced compact navbar labels such as “About Us”, “News & Blog”, and “Donate Now” to remain on one line on desktop.
- Added separate campaign Active/Inactive state in addition to lifecycle status and Public/Hidden visibility.
- Added quick Activate/Deactivate controls for cause areas.
- Expanded pagination to donation accounts and the multi-list Website Content Manager; existing large Admin modules remain paginated.
- Redesigned Donation Verification with search, status filters, page-size control, queue counters, proof viewing, and focused approve/reject controls.
- Added a dedicated Volunteer Applications page under a persistent expandable Volunteers sidebar menu.
- Rebuilt Live Chat client error handling, endpoint resolution, secure JSON responses, retries/polling behavior, and professional chat icon.
- Redesigned the Admin Live Chat workspace with conversation list, full message workspace, customer details, status controls and pagination.
- Added `database/upgrade_v4.php` for safe upgrades from V3.


## V4.5
- Upgraded portal My Profile UI with a cleaner professional identity summary card and removed the oversized role badge.
- Refined the public member/volunteer verification page into a premium glassmorphism layout.
- Redesigned the Admin My Profile and Account Security screens with a more professional SaaS-style layout.
- Applied additional glassmorphism styling polish to the admin / portal shell, topbar, sidebar, and cards.


## V4.6
- Redesigned the Admin My Profile page to match a premium professional SaaS layout with a stronger visual hierarchy.
- Added breadcrumb-style page context, enhanced profile summary card, current photo row, and improved account security form layout.


## V4.7
- Unified Admin Edit Profile and Account Security into one cohesive component.
- Added secure password visibility and password-match UI feedback.
- Rebuilt Admin and public footer social media icons with automatic professional SVG icons, tooltips and consistent brand-aware styling.

## V4.8
- Added a dedicated System → Account Security workspace and moved credential updates out of My Profile.
- Redesigned Donation Verification with professional filters, stat cards and compact record actions.
- Added full Admin Donation CRUD: create, view, edit/update and Super Admin delete.
- Added donation verification history to the record details screen.
- Added V4.8 static coverage tests for Account Security, Donation CRUD and existing core Admin CRUD modules.


## V4.9
- Fixed critical HTTP 500 regression on Admin Donations caused by overriding the framework Controller::view() renderer with an incompatible action signature.
- Fixed stuck Admin loading overlay when returning with browser Back/Forward navigation.
- Added runtime class-loading regression tests for controller inheritance compatibility.


## V5.0
- LKR-only currency system and upgrade migration.
- Professional multi-recipient/category broadcast messaging.
- Real dashboard column chart.
- Admin-managed favicon/browser tab logo.
- Professional donation verification status tag.
- Website image size guide and frame-fit standardization.


## V5.2
- Rebuilt homepage Hero Slider for finished 1920×900 artwork: full viewport width, responsive cover rendering, no duplicate content overlay, no large navigation arrows, subtle dots/progress, auto-slide, keyboard/swipe accessibility and optimized first-image loading.


## V5.3
- Restored the original-style hero text, CTA buttons and supporter badge while retaining full-width responsive hero imagery.
- Reworked hero autoplay to reliably rotate multiple active slides every 5.6 seconds.
- Kept arrowless navigation with professional dots, keyboard controls and touch swipe.
- Restored editable hero overlay fields in Admin → Content & Website → Hero Slider.

## V5.4
- Added a dedicated Admin Home Page Builder with professional per-section editing, visibility controls, CTA management, About image upload, and dynamic item-count controls.
- Homepage Causes, Campaigns, Gallery, Events and News headings/content can now be managed centrally while their cards and images remain controlled by the respective CRUD modules.
- Published Events and News/Blog entries automatically feed the homepage; upcoming events are prioritized and recent published events remain eligible.
- Replaced footer contact glyphs with consistent professional inline SVG icons for address, phone, email, website and opening hours.
- Standardized homepage image fitting to cover/center without stretching.


## V5.7
- Added secure member self-registration, username/email login, unique Member ID and QR verification.
- Added Admin member registration approval/rejection, detailed profile/QR view, secure password reset and expanded search.
- Added WhatsApp floating public contact button managed through Branding & SEO.
- Added responsive hero desktop/mobile image variants, scheduling, preview and drag-and-drop ordering.
- Added member dashboard announcements/events/causes and a comprehensive tablet/mobile responsive pass.


## V5.8
- Rebuilt Member Registration and Member Login into a premium, responsive NGO membership experience.
- Fixed Member directory Register/Login button spacing and WhatsApp placement at the bottom-left on desktop and mobile.
- Admin 2FA is now a clean second-step challenge shown only for accounts that enabled 2FA; the optional 2FA field is removed from the normal sign-in form.
- Refined the authenticated Member Portal dashboard and navigation into a professional responsive workspace.
- Added asset cache-busting based on file modification time to prevent old CSS/JS from being served after updates.


## V5.9
- Professionalized Foundation identity fallbacks and login screens with Admin-managed portal/member/admin backgrounds.
- Added Sri Lanka / Polonnaruwa / Thambala SEO+AEO defaults and structured data enhancements.
- Improved favicon/touch-icon handling and campaign image refresh/preview behavior.
- Added `database/upgrade_v5_9.php` for existing databases.


## V6.0
- Added professional notification drawers across Admin and Portal with AJAX read-state management, filtering, lazy pagination and dynamic unread badges.
- Redesigned notification centre pages and stopped automatic mark-all behavior on page open.
- No database migration required.

## V6.1
- Compact notification drawer; keeps header/bell and underlying page visible while open, with refined desktop/mobile sizing and spacing.

## V6.2
- Added modular Sri Lankan online donation/payment gateway architecture with PayHere hosted checkout adapter.
- Added secure server callback verification, online transaction records, receipts, status retrieval/refund operations and Admin reporting.
- Added separate Developer Integration → Payment Gateway configuration and Fundraising → Online Payments operations page.
- Added online payment Dashboard KPIs, recent transactions, CSV export, LKR min/max controls and sandbox/live mode.

## V6.3
- Simplified PayHere integration into a three-step Easy Payment Setup wizard.
- Basic hosted checkout now requires only Merchant ID and Merchant Secret; API App credentials are optional/advanced.
- Added Save & Test Setup and clearer readiness indicators.
- Added secure automatic private encryption-key generation when no server key is configured.
- Simplified public online donation entry to amount + essential donor details + review/payment.
- No additional database migration beyond V6.2.

## V6.5 — Live Session, CSRF & Favicon Reliability
- Hardened PHP sessions for shared-hosting/subdomain deployments by using protected application session storage when writable, aligning server GC lifetime with the configured session lifetime, and improving HTTPS detection behind reverse proxies/CDNs.
- Added signed double-submit CSRF fallback while retaining session-backed CSRF validation, preventing false 419 errors when a shared host rotates PHP session storage between form display and submission.
- Dynamic application pages now send private no-store headers so proxies/CDNs cannot cache HTML containing another session's CSRF token.
- Added a professional 419 recovery screen instead of a raw token error.
- Added `/favicon.ico` handling that serves the Admin-managed favicon/Foundation logo or a safe 97 fallback, eliminating the browser favicon 404.
- No database migration is required.

## V6.6
- Fixed live `/portal` HTTP 500 caused by selecting a non-existent `events.featured_image` column; the portal now uses `events.image_path`.
- Added fault isolation around optional dashboard content feeds so portal access remains available if an optional content query fails during deployment.
- Added a portal SQL/schema regression test.


## V6.7 — Member Production Readiness
- Fixed member registration/login and Admin member save/approval workflow for production use.
- Changed member password policy to 8–12 characters with Argon2id hashing.
- Rebuilt member profile persistence using explicit transactions instead of a fragile database upsert.
- Approval publishes; reject/inactive/suspended/pending unpublish.
- Hardened CSRF against shared-host session rotation and excluded member auth forms from SPA caching.
- Added safe production error references/logging, responsive member/mobile improvements, and a member production checker.


## V6.9
- Optimized public member images and redesigned the member directory cards with non-cropping responsive media frames, WebP variants, skeleton loading and better mobile/tablet layout.
