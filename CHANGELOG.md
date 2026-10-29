# Changelog

## v1.0.0 - 2026-10-29

- Initial release.
- `purchase()` creates an SSLCommerz checkout session and redirects to the hosted page.
- `completePurchase()` verifies `verify_sign`, calls the Order Validation API, and checks `tran_id`, amount and currency.
- `acceptNotification()` for IPN.
- `validateTransaction()`, `fetchTransaction()` (by `tran_id` or `sessionkey`), `refund()` and `fetchRefund()`.
- Sandbox and live endpoints via `testMode`.
