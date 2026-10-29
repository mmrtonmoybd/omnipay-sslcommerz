# Omnipay: SSLCommerz

**SSLCommerz driver for the Omnipay PHP payment processing library**

[![CI](https://github.com/mmrtonmoybd/omnipay-sslcommerz/actions/workflows/ci.yml/badge.svg)](https://github.com/mmrtonmoybd/omnipay-sslcommerz/actions/workflows/ci.yml)
[![Latest Stable Version](https://poser.pugx.org/mmrtonmoybd/omnipay-sslcommerz/version)](https://packagist.org/packages/mmrtonmoybd/omnipay-sslcommerz)
[![Total Downloads](https://poser.pugx.org/mmrtonmoybd/omnipay-sslcommerz/d/total)](https://packagist.org/packages/mmrtonmoybd/omnipay-sslcommerz)
[![License](https://poser.pugx.org/mmrtonmoybd/omnipay-sslcommerz/license)](https://packagist.org/packages/mmrtonmoybd/omnipay-sslcommerz)

[Omnipay](https://github.com/thephpleague/omnipay) is a framework agnostic, multi-gateway payment
processing library for PHP. This package implements [SSLCommerz](https://www.sslcommerz.com/) support for Omnipay.

SSLCommerz is the largest payment gateway aggregator in Bangladesh. One integration gives your
customers local and international cards (Visa, Mastercard, Amex, DBBL Nexus), internet banking,
and mobile financial services such as bKash, Nagad, Rocket and Upay.

## Installation

Omnipay is installed via [Composer](https://getcomposer.org/). To install, require `league/omnipay` and
`mmrtonmoybd/omnipay-sslcommerz` with Composer:

```
composer require league/omnipay mmrtonmoybd/omnipay-sslcommerz
```

Requires PHP 7.2+ and Omnipay 3.x.

## Basic Usage

The following gateways are provided by this package:

* SslCommerz

For general usage instructions, please see the main [Omnipay](https://github.com/thephpleague/omnipay)
repository.

### Supported methods

| Omnipay method          | SSLCommerz API                                    |
|-------------------------|---------------------------------------------------|
| `purchase()`            | Create session (`/gwprocess/v4/api.php`)          |
| `completePurchase()`    | Signature check + Order Validation API            |
| `acceptNotification()`  | IPN (Instant Payment Notification) parsing        |
| `validateTransaction()` | Order Validation API (`validationserverAPI.php`)  |
| `fetchTransaction()`    | Transaction query by `tran_id` or `sessionkey`    |
| `refund()`              | Initiate refund                                   |
| `fetchRefund()`         | Refund status query                               |

### Credentials and test mode

Create a free sandbox store at <https://developer.sslcommerz.com/registration/>. You will receive a
**Store ID** and **Store Password** by email; the sandbox merchant panel is at
<https://sandbox.sslcommerz.com/manage/>.

```php
use Omnipay\Omnipay;

$gateway = Omnipay::create('SslCommerz');
$gateway->setStoreId('your_store_id');
$gateway->setStorePassword('your_store_password');
$gateway->setTestMode(true); // sandbox.sslcommerz.com; false = securepay.sslcommerz.com
```

### Purchase (redirect to the hosted payment page)

```php
$response = $gateway->purchase([
    'amount'        => '1500.00',
    'currency'      => 'BDT',           // BDT, USD, EUR, GBP, SGD, INR, MYR ...
    'transactionId' => 'ORDER-1001',    // your unique order id (tran_id)
    'description'   => 'Order #1001',   // sent as product_name
    'returnUrl'     => 'https://example.com/payment/success', // success_url
    'failUrl'       => 'https://example.com/payment/fail',    // fail_url (defaults to cancelUrl)
    'cancelUrl'     => 'https://example.com/payment/cancel',  // cancel_url
    'notifyUrl'     => 'https://example.com/payment/ipn',     // ipn_url (server to server)
    'card' => [
        'name'     => 'John Doe',
        'email'    => 'john@example.com',
        'phone'    => '01711111111',
        'address1' => 'House 1, Road 2, Dhanmondi',
        'city'     => 'Dhaka',
        'postcode' => '1209',
        'country'  => 'Bangladesh',
    ],
])->send();

if ($response->isRedirect()) {
    // Save $response->getTransactionReference() (the SSLCommerz sessionkey) if you like
    $response->redirect();
} else {
    echo $response->getMessage(); // e.g. "Store Credential Error Or Store is De-active"
}
```

The `card` parameter is only used for customer details (`cus_*`) and, when a shipping address is set,
shipping details (`ship_*`). No card data is sent; the customer enters payment details on the
SSLCommerz page.

#### Optional parameters

| Parameter          | SSLCommerz field              | Notes                                                     |
|--------------------|-------------------------------|-----------------------------------------------------------|
| `items`            | `cart`, `num_of_item`         | Omnipay `ItemBag` or array of items                      |
| `productName`      | `product_name`                | Defaults to `description`, item names, or `Order {id}`    |
| `productCategory`  | `product_category`            | Default `general`                                         |
| `productProfile`   | `product_profile`             | `general`, `physical-goods`, `non-physical-goods`, `airline-tickets`, `travel-vertical`, `telecom-vertical` |
| `shippingMethod`   | `shipping_method`             | `NO` (default), `YES`, `Courier`                          |
| `numOfItem`        | `num_of_item`                 |                                                           |
| `valueA` - `valueD`| `value_a` - `value_d`         | Custom data, returned to you unchanged                    |
| `multiCardName`    | `multi_card_name`             | Limit methods, e.g. `bkash,nagad` or `visacard,mastercard`|
| `allowedBin`       | `allowed_bin`                 | Restrict card BINs                                        |
| `emiOption`        | `emi_option`                  | `true` to enable EMI                                      |
| `emiMaxInstOption` | `emi_max_inst_option`         | Max installments (3, 6, 9 ...)                            |
| `emiSelectedInst`  | `emi_selected_inst`           |                                                           |
| `emiAllowOnly`     | `emi_allow_only`              |                                                           |
| `extraParameters`  | any                           | Array of raw fields (airline/travel/telecom vertical fields) |

### Complete purchase (success / fail / cancel URLs)

SSLCommerz POSTs the result to `success_url`, `fail_url` or `cancel_url`. Call `completePurchase()` on all
three. It verifies the `verify_sign` hash, and for `VALID` payments calls the **Order Validation API**
so the result cannot be forged. Pass your stored order values so the driver also checks that
`tran_id`, amount and currency match:

```php
$response = $gateway->completePurchase([
    'transactionId' => $order->id,
    'amount'        => $order->amount,
    'currency'      => $order->currency,
])->send();

if ($response->isSuccessful()) {
    if ($response->isRisky()) {
        // risk_level = 1: hold the order and verify the customer first
    }

    $order->markPaid(
        $response->getTransactionReference(), // val_id
        $response->getBankTransactionId()     // bank_tran_id, needed for refunds
    );
} elseif ($response->isCancelled()) {
    // customer cancelled
} else {
    echo $response->getMessage(); // failure reason
}
```

An `Omnipay\Common\Exception\InvalidResponseException` is thrown when the signature is invalid or the
paid `tran_id` / amount does not match the order.

`isAlreadyValidated()` is `true` when SSLCommerz returns `VALIDATED`, meaning the same `val_id` was
validated before (for example by your IPN handler). Use it to avoid processing an order twice.

Other getters: `getTransactionId()`, `getAmount()`, `getStoreAmount()`, `getCurrency()`,
`getCurrencyAmount()`, `getCardType()`, `getCardBrand()`, `getCardIssuer()`, `getRiskLevel()`,
`getRiskTitle()`, `getTransactionDate()`, `getStatus()`, `getData()`.

### IPN (Instant Payment Notification)

Set `notifyUrl` in `purchase()` (or configure the IPN URL in the merchant panel). In the IPN handler
the simplest secure option is to use `completePurchase()` exactly as above. If you only need to read
the notification, use `acceptNotification()`:

```php
$notification = $gateway->acceptNotification();

if ($notification->isValid()) { // verify_sign check
    $status = $notification->getTransactionStatus(); // NotificationInterface::STATUS_COMPLETED|PENDING|FAILED
    $orderId = $notification->getTransactionId();    // tran_id
    $valId = $notification->getTransactionReference(); // val_id
}
```

`acceptNotification()` only checks the signature. Confirm a completed payment with
`validateTransaction()` (or `completePurchase()`) before fulfilling the order.

### Validate a payment by val_id

```php
$response = $gateway->validateTransaction(['transactionReference' => $valId])->send();
$response->isSuccessful(); // status VALID or VALIDATED
```

### Fetch a transaction

```php
// by your order id
$response = $gateway->fetchTransaction(['transactionId' => 'ORDER-1001'])->send();

// or by the session key returned from purchase()
$response = $gateway->fetchTransaction(['transactionReference' => $sessionKey])->send();

$response->isSuccessful();        // a VALID / VALIDATED attempt exists
$response->getStatus();           // VALID, VALIDATED, PENDING, FAILED, INVALID ...
$response->getTransactions();     // every attempt for this tran_id
```

### Refund

```php
$response = $gateway->refund([
    'transactionReference' => $bankTranId,   // bank_tran_id from completePurchase()
    'amount'               => '500.00',      // full or partial
    'description'          => 'Customer request', // refund_remarks
    'refundReference'      => 'REFUND-1',    // optional refe_id
])->send();

if ($response->isSuccessful()) {
    $refundRefId = $response->getTransactionReference(); // refund_ref_id
} elseif ($response->isPending()) {
    // already being processed
} else {
    echo $response->getMessage();
}
```

Refund API access must be enabled for your store, and SSLCommerz requires the calling server IP to be
whitelisted in live mode.

### Refund status

```php
$response = $gateway->fetchRefund(['transactionReference' => $refundRefId])->send();

$response->isSuccessful(); // status "refunded"
$response->isPending();    // status "processing"
$response->getRefundedOn();
```

### Status reference

| SSLCommerz status           | Response                                   |
|-----------------------------|--------------------------------------------|
| `VALID`                     | `isSuccessful()`                           |
| `VALIDATED`                 | `isSuccessful()` and `isAlreadyValidated()`|
| `PENDING`, `UNATTEMPTED`    | `isPending()`                              |
| `CANCELLED`                 | `isCancelled()`                            |
| `FAILED`, `EXPIRED`, `INVALID_TRANSACTION` | not successful              |

### Sandbox test data

In sandbox mode no real money moves. Use the test cards and mobile banking test accounts shown on the
SSLCommerz sandbox payment page and in the
[SSLCommerz developer documentation](https://developer.sslcommerz.com/doc/v4/).

## Testing

```
composer install
composer test
```

The unit tests use mocked HTTP responses. To also run the tests against the real SSLCommerz sandbox:

```
SSLCOMMERZ_STORE_ID=your_sandbox_store SSLCOMMERZ_STORE_PASSWORD=your_password vendor/bin/phpunit --group integration
```

## Support

If you are having general issues with Omnipay, we suggest posting on
[Stack Overflow](https://stackoverflow.com/). Be sure to add the
[omnipay tag](https://stackoverflow.com/questions/tagged/omnipay) so it can be easily found.

If you believe you have found a bug, please report it using the
[GitHub issue tracker](https://github.com/mmrtonmoybd/omnipay-sslcommerz/issues),
or better yet, fork the library and submit a pull request.

SSLCommerz API documentation: <https://developer.sslcommerz.com/doc/v4/>

## Security

If you discover a security issue, please email rmedha037@gmail.com instead of using the issue tracker.

## License

The MIT License (MIT). Please see [LICENSE](LICENSE) for more information.
