<?php

namespace Omnipay\SslCommerz\Tests\Message;

use Omnipay\SslCommerz\Message\PurchaseRequest;
use Omnipay\Tests\TestCase;

class PurchaseRequestTest extends TestCase
{
    /** @var PurchaseRequest */
    protected $request;

    public function setUp(): void
    {
        parent::setUp();

        $this->request = new PurchaseRequest($this->getHttpClient(), $this->getHttpRequest());
        $this->request->initialize([
            'storeId' => 'testbox',
            'storePassword' => 'qwerty',
            'testMode' => true,
            'amount' => '1500.50',
            'currency' => 'BDT',
            'transactionId' => 'ORDER-1002',
            'returnUrl' => 'https://example.com/success',
            'cancelUrl' => 'https://example.com/cancel',
            'card' => [
                'firstName' => 'Jane',
                'lastName' => 'Doe',
                'email' => 'jane@example.com',
                'billingPhone' => '01811111111',
                'billingAddress1' => 'Gulshan 1',
                'billingCity' => 'Dhaka',
                'billingCountry' => 'Bangladesh',
                'shippingAddress1' => 'Banani 11',
                'shippingCity' => 'Dhaka',
                'shippingCountry' => 'Bangladesh',
                'shippingPostcode' => '1213',
            ],
        ]);
    }

    public function testGetDataDefaults()
    {
        $data = $this->request->getData();

        $this->assertSame('testbox', $data['store_id']);
        $this->assertSame('qwerty', $data['store_passwd']);
        $this->assertSame('1500.50', $data['total_amount']);
        $this->assertSame('BDT', $data['currency']);
        $this->assertSame('ORDER-1002', $data['tran_id']);
        $this->assertSame('https://example.com/success', $data['success_url']);
        $this->assertSame('https://example.com/cancel', $data['fail_url'], 'fail_url falls back to cancelUrl');
        $this->assertSame('https://example.com/cancel', $data['cancel_url']);
        $this->assertArrayNotHasKey('ipn_url', $data);

        $this->assertSame('Jane Doe', $data['cus_name']);
        $this->assertSame('jane@example.com', $data['cus_email']);
        $this->assertSame('01811111111', $data['cus_phone']);
        $this->assertSame('Gulshan 1', $data['cus_add1']);
        $this->assertSame('Dhaka', $data['cus_city']);
        $this->assertSame('Bangladesh', $data['cus_country']);

        $this->assertSame('Banani 11', $data['ship_add1']);
        $this->assertSame('1213', $data['ship_postcode']);

        $this->assertSame('NO', $data['shipping_method']);
        $this->assertSame('general', $data['product_category']);
        $this->assertSame('general', $data['product_profile']);
        $this->assertSame('Order ORDER-1002', $data['product_name']);
        $this->assertArrayNotHasKey('cart', $data);
    }

    public function testGetDataWithItemsAndOptions()
    {
        $this->request->setItems([
            ['name' => 'T-Shirt', 'quantity' => 2, 'price' => '500.00'],
            ['name' => 'Cap', 'quantity' => 1, 'price' => '500.50'],
        ]);
        $this->request->setFailUrl('https://example.com/fail');
        $this->request->setNotifyUrl('https://example.com/ipn');
        $this->request->setProductCategory('clothing');
        $this->request->setProductProfile('physical-goods');
        $this->request->setShippingMethod('Courier');
        $this->request->setValueA('customer-42');
        $this->request->setValueD('meta');
        $this->request->setMultiCardName('bkash,nagad');
        $this->request->setEmiOption(true);
        $this->request->setEmiMaxInstOption(6);
        $this->request->setExtraParameters(['hours_till_departure' => '24 hrs']);

        $data = $this->request->getData();

        $this->assertSame('https://example.com/fail', $data['fail_url']);
        $this->assertSame('https://example.com/ipn', $data['ipn_url']);
        $this->assertSame('T-Shirt, Cap', $data['product_name']);
        $this->assertSame('clothing', $data['product_category']);
        $this->assertSame('physical-goods', $data['product_profile']);
        $this->assertSame('Courier', $data['shipping_method']);
        $this->assertSame(2, $data['num_of_item']);
        $this->assertSame('customer-42', $data['value_a']);
        $this->assertSame('meta', $data['value_d']);
        $this->assertArrayNotHasKey('value_b', $data);
        $this->assertSame('bkash,nagad', $data['multi_card_name']);
        $this->assertSame(1, $data['emi_option']);
        $this->assertSame(6, $data['emi_max_inst_option']);
        $this->assertSame('24 hrs', $data['hours_till_departure']);

        $cart = json_decode($data['cart'], true);
        $this->assertSame([
            ['product' => 'T-Shirt', 'quantity' => 2, 'amount' => '1000.00'],
            ['product' => 'Cap', 'quantity' => 1, 'amount' => '500.50'],
        ], $cart);
    }

    public function testProductNameFromDescription()
    {
        $this->request->setDescription('Premium plan');

        $this->assertSame('Premium plan', $this->request->getData()['product_name']);
    }

    public function testRequiresTransactionId()
    {
        $this->request->setTransactionId(null);

        $this->expectException('Omnipay\Common\Exception\InvalidRequestException');

        $this->request->getData();
    }

    public function testRequiresCredentials()
    {
        $this->request->setStoreId(null);

        $this->expectException('Omnipay\Common\Exception\InvalidRequestException');

        $this->request->getData();
    }

    public function testEndpoint()
    {
        $this->assertSame('https://sandbox.sslcommerz.com', $this->request->getBaseEndpoint());

        $this->request->setTestMode(false);
        $this->assertSame('https://securepay.sslcommerz.com', $this->request->getBaseEndpoint());
    }
}
