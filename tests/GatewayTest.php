<?php

namespace Omnipay\SslCommerz\Tests;

use Omnipay\Common\Message\NotificationInterface;
use Omnipay\SslCommerz\Gateway;
use Omnipay\SslCommerz\Helper\Signature;
use Omnipay\Tests\GatewayTestCase;

class GatewayTest extends GatewayTestCase
{
    /** @var Gateway */
    protected $gateway;

    /** @var array */
    protected $options;

    public function setUp(): void
    {
        parent::setUp();

        $this->gateway = new Gateway($this->getHttpClient(), $this->getHttpRequest());
        $this->gateway->initialize([
            'storeId' => 'testbox',
            'storePassword' => 'qwerty',
            'testMode' => true,
        ]);

        $this->options = [
            'amount' => '100.00',
            'currency' => 'BDT',
            'transactionId' => 'ORDER-1001',
            'returnUrl' => 'https://example.com/success',
            'failUrl' => 'https://example.com/fail',
            'cancelUrl' => 'https://example.com/cancel',
            'notifyUrl' => 'https://example.com/ipn',
            'card' => [
                'name' => 'John Doe',
                'email' => 'john@example.com',
                'phone' => '01711111111',
                'address1' => 'House 1, Road 2',
                'city' => 'Dhaka',
                'postcode' => '1207',
                'country' => 'Bangladesh',
            ],
        ];
    }

    /**
     * Data SSLCommerz posts to success_url, signed like the real gateway.
     */
    protected function signedPost(array $data)
    {
        $data['verify_key'] = implode(',', array_keys($data));
        $data['verify_sign'] = Signature::generate($data, 'qwerty');

        return $data;
    }

    protected function successPost()
    {
        return $this->signedPost([
            'tran_id' => 'ORDER-1001',
            'val_id' => '2610291450120sxbW1o2Ab5zH3G',
            'amount' => '100.00',
            'card_type' => 'BKASH-BKash',
            'store_amount' => '97.50',
            'bank_tran_id' => '2610291450121qGvNTd6QqnFd8P',
            'status' => 'VALID',
            'tran_date' => '2026-10-29 14:50:12',
            'currency' => 'BDT',
            'currency_type' => 'BDT',
            'currency_amount' => '100.00',
            'risk_level' => '0',
            'risk_title' => 'Safe',
        ]);
    }

    public function testGatewayName()
    {
        $this->assertSame('SSLCommerz', $this->gateway->getName());
        $this->assertSame('SslCommerz', $this->gateway->getShortName());
    }

    public function testSupportsMethods()
    {
        $this->assertTrue($this->gateway->supportsPurchase());
        $this->assertTrue($this->gateway->supportsCompletePurchase());
        $this->assertTrue($this->gateway->supportsRefund());
        $this->assertTrue($this->gateway->supportsFetchTransaction());
        $this->assertTrue($this->gateway->supportsAcceptNotification());
        $this->assertFalse($this->gateway->supportsAuthorize());
    }

    public function testPurchaseSuccess()
    {
        $this->setMockHttpResponse('PurchaseSuccess.txt');

        $response = $this->gateway->purchase($this->options)->send();

        $this->assertFalse($response->isSuccessful());
        $this->assertTrue($response->isRedirect());
        $this->assertSame('GET', $response->getRedirectMethod());
        $this->assertSame(
            'https://sandbox.sslcommerz.com/EasyCheckOut/testcdeF650E87F4A4E1D6B3F4E2B1D3C5A7E90',
            $response->getRedirectUrl()
        );
        $this->assertSame([], $response->getRedirectData());
        $this->assertSame('F650E87F4A4E1D6B3F4E2B1D3C5A7E90', $response->getTransactionReference());
        $this->assertArrayHasKey('mobilebanking', $response->getGateways());
        $this->assertCount(1, $response->getPaymentMethods());

        $request = $this->getMockedRequests()[0];
        $this->assertSame('POST', $request->getMethod());
        $this->assertSame('https://sandbox.sslcommerz.com/gwprocess/v4/api.php', (string) $request->getUri());

        parse_str((string) $request->getBody(), $body);
        $this->assertSame('testbox', $body['store_id']);
        $this->assertSame('100.00', $body['total_amount']);
        $this->assertSame('ORDER-1001', $body['tran_id']);
        $this->assertSame('https://example.com/ipn', $body['ipn_url']);
        $this->assertSame('John Doe', $body['cus_name']);
    }

    public function testPurchaseFailure()
    {
        $this->setMockHttpResponse('PurchaseFailure.txt');

        $response = $this->gateway->purchase($this->options)->send();

        $this->assertFalse($response->isSuccessful());
        $this->assertFalse($response->isRedirect());
        $this->assertNull($response->getRedirectUrl());
        $this->assertSame('Store Credential Error Or Store is De-active', $response->getMessage());
    }

    public function testCompletePurchaseSuccess()
    {
        $this->setMockHttpResponse('ValidationValid.txt');
        $this->getHttpRequest()->request->replace($this->successPost());

        $response = $this->gateway->completePurchase([
            'transactionId' => 'ORDER-1001',
            'amount' => '100.00',
            'currency' => 'BDT',
        ])->send();

        $this->assertTrue($response->isSuccessful());
        $this->assertFalse($response->isRedirect());
        $this->assertFalse($response->isRisky());
        $this->assertSame('ORDER-1001', $response->getTransactionId());
        $this->assertSame('2610291450120sxbW1o2Ab5zH3G', $response->getTransactionReference());
        $this->assertSame('2610291450121qGvNTd6QqnFd8P', $response->getBankTransactionId());
        $this->assertSame('97.50', $response->getStoreAmount());
        $this->assertSame('VALID', $response->getStatus());

        $request = $this->getMockedRequests()[0];
        $this->assertSame('GET', $request->getMethod());
        $this->assertStringStartsWith(
            'https://sandbox.sslcommerz.com/validator/api/validationserverAPI.php?val_id=2610291450120sxbW1o2Ab5zH3G',
            (string) $request->getUri()
        );
    }

    public function testCompletePurchaseInvalidTransaction()
    {
        $this->setMockHttpResponse('ValidationInvalid.txt');
        $this->getHttpRequest()->request->replace($this->successPost());

        $response = $this->gateway->completePurchase()->send();

        $this->assertFalse($response->isSuccessful());
        $this->assertSame('INVALID_TRANSACTION', $response->getStatus());
    }

    public function testCompletePurchaseCancelledDoesNotCallApi()
    {
        $this->getHttpRequest()->request->replace($this->signedPost([
            'tran_id' => 'ORDER-1001',
            'status' => 'CANCELLED',
            'error' => 'Cancelled by User',
        ]));

        $response = $this->gateway->completePurchase()->send();

        $this->assertFalse($response->isSuccessful());
        $this->assertTrue($response->isCancelled());
        $this->assertSame('Cancelled by User', $response->getMessage());
        $this->assertCount(0, $this->getMockedRequests());
    }

    public function testCompletePurchaseFailed()
    {
        $this->getHttpRequest()->request->replace($this->signedPost([
            'tran_id' => 'ORDER-1001',
            'status' => 'FAILED',
            'error' => 'Insufficient balance',
        ]));

        $response = $this->gateway->completePurchase()->send();

        $this->assertFalse($response->isSuccessful());
        $this->assertFalse($response->isCancelled());
        $this->assertSame('Insufficient balance', $response->getMessage());
    }

    public function testCompletePurchaseRejectsTamperedSignature()
    {
        $post = $this->successPost();
        $post['amount'] = '1.00';
        $this->getHttpRequest()->request->replace($post);

        $this->expectException('Omnipay\Common\Exception\InvalidResponseException');
        $this->expectExceptionMessage('Invalid SSLCommerz signature');

        $this->gateway->completePurchase()->send();
    }

    public function testCompletePurchaseRejectsAmountMismatch()
    {
        $this->setMockHttpResponse('ValidationValid.txt');
        $this->getHttpRequest()->request->replace($this->successPost());

        $this->expectException('Omnipay\Common\Exception\InvalidResponseException');
        $this->expectExceptionMessage('amount does not match');

        $this->gateway->completePurchase(['amount' => '500.00', 'currency' => 'BDT'])->send();
    }

    public function testCompletePurchaseRejectsTransactionIdMismatch()
    {
        $this->setMockHttpResponse('ValidationValid.txt');
        $this->getHttpRequest()->request->replace($this->successPost());

        $this->expectException('Omnipay\Common\Exception\InvalidResponseException');
        $this->expectExceptionMessage('tran_id does not match');

        $this->gateway->completePurchase(['transactionId' => 'ORDER-9999'])->send();
    }

    public function testCompletePurchaseWithoutData()
    {
        $this->expectException('Omnipay\Common\Exception\InvalidRequestException');

        $this->gateway->completePurchase()->send();
    }

    public function testCompletePurchaseWithExplicitRequestData()
    {
        $this->setMockHttpResponse('ValidationValid.txt');

        $response = $this->gateway->completePurchase(['requestData' => $this->successPost()])->send();

        $this->assertTrue($response->isSuccessful());
    }

    public function testValidate()
    {
        $this->setMockHttpResponse('ValidationValid.txt');

        $response = $this->gateway->validateTransaction(['transactionReference' => '2610291450120sxbW1o2Ab5zH3G'])->send();

        $this->assertTrue($response->isSuccessful());
        $this->assertSame('ORDER-1001', $response->getTransactionId());
    }

    public function testAcceptNotification()
    {
        $this->getHttpRequest()->request->replace($this->successPost());

        $notification = $this->gateway->acceptNotification();

        $this->assertTrue($notification->isValid());
        $this->assertSame(NotificationInterface::STATUS_COMPLETED, $notification->getTransactionStatus());
        $this->assertSame('ORDER-1001', $notification->getTransactionId());
        $this->assertSame('2610291450120sxbW1o2Ab5zH3G', $notification->getTransactionReference());
        $this->assertSame($notification, $notification->send());
    }

    public function testAcceptNotificationInvalidSignature()
    {
        $post = $this->successPost();
        $post['verify_sign'] = md5('forged');
        $this->getHttpRequest()->request->replace($post);

        $notification = $this->gateway->acceptNotification();

        $this->assertFalse($notification->isValid());
        $this->assertSame(NotificationInterface::STATUS_FAILED, $notification->getTransactionStatus());
    }

    public function testAcceptNotificationFailed()
    {
        $this->getHttpRequest()->request->replace($this->signedPost([
            'tran_id' => 'ORDER-1001',
            'status' => 'FAILED',
        ]));

        $this->assertSame(
            NotificationInterface::STATUS_FAILED,
            $this->gateway->acceptNotification()->getTransactionStatus()
        );
    }

    public function testFetchTransactionByTransactionId()
    {
        $this->setMockHttpResponse('FetchTransactionByTranId.txt');

        $response = $this->gateway->fetchTransaction(['transactionId' => 'ORDER-1001'])->send();

        $this->assertTrue($response->isSuccessful());
        $this->assertSame('VALIDATED', $response->getStatus());
        $this->assertTrue($response->isAlreadyValidated());
        $this->assertSame('2610291450120sxbW1o2Ab5zH3G', $response->getTransactionReference());
        $this->assertSame(2, $response->getNumberOfTransactions());
        $this->assertCount(2, $response->getTransactions());

        $uri = (string) $this->getMockedRequests()[0]->getUri();
        $this->assertStringStartsWith(
            'https://sandbox.sslcommerz.com/validator/api/merchantTransIDvalidationAPI.php?tran_id=ORDER-1001',
            $uri
        );
    }

    public function testFetchTransactionBySessionKey()
    {
        $this->setMockHttpResponse('FetchTransactionBySession.txt');

        $response = $this->gateway->fetchTransaction([
            'transactionReference' => 'F650E87F4A4E1D6B3F4E2B1D3C5A7E90',
        ])->send();

        $this->assertTrue($response->isSuccessful());
        $this->assertSame('F650E87F4A4E1D6B3F4E2B1D3C5A7E90', $response->getSessionKey());
        $this->assertStringContainsString(
            'sessionkey=F650E87F4A4E1D6B3F4E2B1D3C5A7E90',
            (string) $this->getMockedRequests()[0]->getUri()
        );
    }

    public function testFetchTransactionNotFound()
    {
        $this->setMockHttpResponse('FetchTransactionNotFound.txt');

        $response = $this->gateway->fetchTransaction(['transactionId' => 'NOPE'])->send();

        $this->assertFalse($response->isSuccessful());
        $this->assertSame('INVALID', $response->getStatus());
        $this->assertSame(0, $response->getNumberOfTransactions());
    }

    public function testFetchTransactionRequiresIdentifier()
    {
        $this->expectException('Omnipay\Common\Exception\InvalidRequestException');

        $this->gateway->fetchTransaction()->send();
    }

    public function testRefundSuccess()
    {
        $this->setMockHttpResponse('RefundSuccess.txt');

        $response = $this->gateway->refund([
            'transactionReference' => '2610291450121qGvNTd6QqnFd8P',
            'amount' => '50.00',
            'description' => 'Customer request',
            'refundReference' => 'REF-1',
        ])->send();

        $this->assertTrue($response->isSuccessful());
        $this->assertSame('5d8f6b6c1b0e3', $response->getTransactionReference());
        $this->assertSame('ORDER-1001', $response->getTransactionId());

        parse_str((string) parse_url((string) $this->getMockedRequests()[0]->getUri(), PHP_URL_QUERY), $query);
        $this->assertSame('2610291450121qGvNTd6QqnFd8P', $query['bank_tran_id']);
        $this->assertSame('50.00', $query['refund_amount']);
        $this->assertSame('Customer request', $query['refund_remarks']);
        $this->assertSame('REF-1', $query['refe_id']);
    }

    public function testRefundFailure()
    {
        $this->setMockHttpResponse('RefundFailure.txt');

        $response = $this->gateway->refund([
            'transactionReference' => 'x',
            'amount' => '10.00',
        ])->send();

        $this->assertFalse($response->isSuccessful());
        $this->assertSame('Invalid Request', $response->getMessage());
    }

    public function testFetchRefundSuccess()
    {
        $this->setMockHttpResponse('FetchRefundSuccess.txt');

        $response = $this->gateway->fetchRefund(['transactionReference' => '5d8f6b6c1b0e3'])->send();

        $this->assertTrue($response->isSuccessful());
        $this->assertSame('2026-10-29 15:10:00', $response->getRefundedOn());
        $this->assertStringContainsString(
            'refund_ref_id=5d8f6b6c1b0e3',
            (string) $this->getMockedRequests()[0]->getUri()
        );
    }

    public function testFetchRefundFailure()
    {
        $this->setMockHttpResponse('FetchRefundFailure.txt');

        $response = $this->gateway->fetchRefund(['refundReference' => 'x'])->send();

        $this->assertFalse($response->isSuccessful());
        $this->assertSame('Unknown Refund Ref ID', $response->getMessage());
    }

    public function testInvalidJsonResponse()
    {
        $this->setMockHttpResponse('InvalidJson.txt');

        $this->expectException('Omnipay\Common\Exception\InvalidResponseException');

        $this->gateway->purchase($this->options)->send();
    }

    public function testLiveEndpoint()
    {
        $this->setMockHttpResponse('PurchaseSuccess.txt');
        $this->gateway->setTestMode(false);

        $this->gateway->purchase($this->options)->send();

        $this->assertSame(
            'https://securepay.sslcommerz.com/gwprocess/v4/api.php',
            (string) $this->getMockedRequests()[0]->getUri()
        );
    }
}
