# 97 Foundation Professional V3 — Admin Guide

The V3 Admin Panel is intended for routine use by non-technical Foundation staff. Normal content updates do not require PHP editing.

## Branding & logo

Open **Admin → Branding & SEO**. Upload/change the Foundation logo, Foundation identity/contact information and global SEO/social-sharing values. The enabled logo is reused in the website, Admin Panel, portal and branded loader.

## Public content

Open **Admin → Website Content** for managed pages, homepage sections and hero slides. Pages support feature photographs, headings, body text, SEO metadata, AEO Quick Answer, display order and active/inactive state.

## Cause Areas and Campaigns

Cause Areas describe the types of help provided. Fundraising Campaigns are separate financial campaigns with target amounts, workflow state and Public/Hidden visibility. Campaigns with donation history should normally be hidden/completed instead of deleted.

## Donation settings

Open **Admin → Donation Settings**. Manage account title, bank, account name, account number, branch, currency, instructions and donation contact person. V3 intentionally does **not** display IBAN or SWIFT/BIC on the public donation account card.

## Donation receipt/proof review

Open **Admin → Donations**. Click **View Receipt ↗** to open the uploaded protected receipt/proof in a new browser page. Approve or reject pending records. Only approved donations contribute to the public raised amount.

## Foundation Members

Open **Admin → Members**. You can:

- Search and paginate members
- Create/edit member name, email, mobile and portal password
- Upload member photograph
- Set official member ID (or let the system generate one)
- Add member role/title and short public bio
- Set Active/Inactive/Suspended
- Show/hide the profile in the public Members directory
- Open the public verification page
- Delete a member account as Super Administrator

Active members receive a Member Portal and QR-enabled digital Member ID. The QR opens `/verify/member/{secure-token}`.

## Volunteers

Open **Admin → Volunteers**. The list supports search, status filtering and pagination. You can edit volunteer profile data and manage the approval workflow. Approval activates portal access, creates the permanent Volunteer ID and enables QR verification.

The QR opens `/verify/volunteer/{secure-token}` and public verification intentionally excludes email, mobile, address and login data.

## News & Blog

Open **Admin → News & Blog** for full CRUD. Choose News or Blog, Draft/Published/Archived, upload a feature image, schedule publish time and set SEO fields. Published items appear automatically on the public News & Blog page and latest items appear on the homepage.

## Social Media

Open **Admin → Social Media**. Add/edit/delete platform links, change order and activate/deactivate them. Active entries automatically appear in the footer.

## Gallery and Events

Gallery and Events both provide create/edit/delete controls, active/publish state and responsive paginated listing where applicable.

## Website Live Chat

Open **Admin → Website Live Chat**. Conversations are paginated and filterable by Open, Waiting or Closed. Open a conversation to read messages, reply, change status or (Super Admin) delete it. New public chats create administrator notifications.

## Portal messages

Use **Admin → Portal Messages** to communicate privately with approved member/volunteer accounts. Recipients receive portal notifications and can reply from their portal.

## Profile and notifications

The top-right administrator identity opens **My Profile**, including avatar upload. The bell opens the notification centre; lists are paginated and opening the page marks unread items as read.

## Sidebar active state

The Admin sidebar resolves the selected state from the actual route. Dashboard is active only on the Admin dashboard, and the matching child module is highlighted on other pages.

## Pagination

Major growing Admin datasets use server-side pagination. Existing search/status query values are preserved when changing pages.

## Login protection

Admin and portal login display an automatically generated **4-digit numeric CAPTCHA**. Administrator TOTP 2FA can also be enabled from **Security & 2FA**.
