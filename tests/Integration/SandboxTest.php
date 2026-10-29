<?php

namespace Omnipay\SslCommerz\Tests\Integration;

use Omnipay\Omnipay;
use PHPUnit\Framework\TestCase;

/**
 * Hits the real SSLCommerz sandbox. Skipped unless credentials are set:
 *
 *   SSLCOMMERZ_STORE_ID=testbox SSLCOMMERZ_STORE_PASSWORD=qwerty vendor/bin/phpunit --group integration
 */
#[\PHPUnit\Framework\Attributes\Group('integration')]
class SandboxTest extends TestCase
{
    protected $gateway;

    protected function setUp(): void
    {
        $storeId = getenv('SSLCOMMERZ_STORE_ID');
        $storePassword = getenv('SSLCOMMERZ_STORE_PASSWORD');

        if (!$storeId || !$storePassword) {
            $this->markTestSkipped('Set SSLCOMMERZ_STORE_ID and SSLCOMMERZ_STORE_PASSWORD to run sandbox tests.');
        }

        $this->gateway = Omnipay::create('SslCommerz');
        $this->gateway->initialize([
            'storeId' => $storeId,
            'storePassword' => $storePassword,
            'testMode' => true,
        ]);
    }

    public function testPurchaseAndQuery()
    {
        $transactionId = 'OMNIPAY-' . uniqid();

        $response = $this->gateway->purchase([
            'amount' => '100.00',
            'currency' => 'BDT',
            'transactionId' => $transactionId,
            'returnUrl' => 'https://example.com/success',
            'cancelUrl' => 'https://example.com/cancel',
            'card' => [
                'name' => 'Omnipay Test',
                'email' => 'test@example.com',
                'phone' => '01711111111',
                'address1' => 'Dhaka',
                'city' => 'Dhaka',
                'country' => 'Bangladesh',
            ],
        ])->send();

        $this->assertTrue($response->isRedirect(), (string) $response->getMessage());
        $this->assertStringStartsWith('https://sandbox.sslcommerz.com/', $response->getRedirectUrl());
        $this->assertNotEmpty($response->getTransactionReference());

        $query = $this->gateway->fetchTransaction(['transactionId' => $transactionId])->send();
        $this->assertFalse($query->isSuccessful(), 'An unpaid session must not be reported as successful');
    }

    public function testValidateUnknownValId()
    {
        $response = $this->gateway->validateTransaction(['transactionReference' => 'unknown'])->send();

        $this->assertFalse($response->isSuccessful());
        $this->assertSame('INVALID_TRANSACTION', $response->getStatus());
    }

    public function testFetchUnknownRefund()
    {
        $response = $this->gateway->fetchRefund(['transactionReference' => 'unknown'])->send();

        $this->assertFalse($response->isSuccessful());
    }
}
