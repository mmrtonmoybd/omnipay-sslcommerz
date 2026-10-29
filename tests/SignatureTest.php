<?php

namespace Omnipay\SslCommerz\Tests;

use Omnipay\SslCommerz\Helper\Signature;
use PHPUnit\Framework\TestCase;

class SignatureTest extends TestCase
{
    protected function data()
    {
        $data = [
            'tran_id' => 'T1',
            'val_id' => 'V1',
            'amount' => '100.00',
            'status' => 'VALID',
            'value_a' => 'not-signed',
            'verify_key' => 'amount,status,tran_id,val_id',
        ];

        // Reference implementation, independent of the helper.
        $expected = md5('amount=100.00&status=VALID&store_passwd=' . md5('qwerty') . '&tran_id=T1&val_id=V1');
        $data['verify_sign'] = $expected;

        return $data;
    }

    public function testGenerateMatchesSslCommerzAlgorithm()
    {
        $data = $this->data();

        $this->assertSame($data['verify_sign'], Signature::generate($data, 'qwerty'));
    }

    public function testVerify()
    {
        $this->assertTrue(Signature::verify($this->data(), 'qwerty'));
    }

    public function testVerifyFailsWithWrongPassword()
    {
        $this->assertFalse(Signature::verify($this->data(), 'wrong'));
    }

    public function testVerifyFailsWhenSignedFieldChanges()
    {
        $data = $this->data();
        $data['amount'] = '1.00';

        $this->assertFalse(Signature::verify($data, 'qwerty'));
    }

    public function testUnsignedFieldsAreIgnored()
    {
        $data = $this->data();
        $data['value_a'] = 'changed';

        $this->assertTrue(Signature::verify($data, 'qwerty'));
    }

    public function testVerifyFailsWithoutSignature()
    {
        $data = $this->data();
        unset($data['verify_sign']);

        $this->assertFalse(Signature::verify($data, 'qwerty'));
        $this->assertNull(Signature::generate(['status' => 'VALID'], 'qwerty'));
    }
}
