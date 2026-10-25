# 97 Foundation Online Payments — Easy Setup (V6.3)

## Fast setup

For normal PayHere hosted checkout, only two gateway values are required:

1. **Merchant ID**
2. **Merchant Secret**

Open **Admin → Developer Integration → Easy Payment Setup**.

1. Select **Sandbox / Test**.
2. Enter Merchant ID and Merchant Secret.
3. Set minimum/maximum LKR donation amount.
4. Turn on **Enable Online Payments** when ready.
5. Click **Save & Test Setup**.
6. Open the public Sandbox Donation Test and complete the checkout flow.

The exact webhook/notify URL is displayed on the setup page automatically.

## Advanced features are optional

Merchant API App ID and App Secret are **not required for normal hosted checkout**. Configure them only if you need:

- API transaction status synchronization
- Refund API requests

These fields are kept under **Advanced Settings** so the basic setup remains simple.

## Secret protection

The application never sends merchant secrets to frontend JavaScript or public HTML.

Credential encryption uses AES-256-GCM. Key priority:

1. `PAYMENT_ENCRYPTION_KEY`
2. `APP_KEY`
3. Automatically generated private key at `storage/private/payment.key`

The private-key file is outside the public assets/uploads area and is blocked by the application's storage access rules. Back up this private key together with the production configuration. If the key is lost, previously encrypted gateway secrets must be entered again.

You may still keep all gateway credentials in server environment variables:

```text
PAYHERE_MODE=sandbox
PAYHERE_MERCHANT_ID=...
PAYHERE_MERCHANT_SECRET=...
PAYHERE_APP_ID=...
PAYHERE_APP_SECRET=...
```

## Public donor flow

`Donate → Online Payment → Amount → Donor Details → Review → PayHere Hosted Checkout → Verified Callback → Confirmation`

The browser redirect is never trusted as proof of payment. A transaction becomes Successful only after the server verifies the gateway callback signature, merchant ID, order ID, amount and LKR currency.

## Live mode

Keep Sandbox selected until the full payment flow works. Live mode requires HTTPS. Test success, failure, cancellation, duplicate callback and mobile checkout before accepting real donations.

## Database

V6.3 adds no schema changes beyond V6.2. Existing databases only need:

```bash
php database/upgrade_v6_2.php
```
