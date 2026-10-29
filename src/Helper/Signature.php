<?php

namespace Omnipay\SslCommerz\Helper;

/**
 * Verifies the verify_sign hash SSLCommerz attaches to the data it posts to
 * success_url, fail_url, cancel_url and ipn_url.
 */
class Signature
{
    /**
     * Build the expected signature for the given posted data.
     *
     * @param array  $data          Posted data (must contain verify_key)
     * @param string $storePassword Store password
     * @return string|null          Null when verify_key is missing
     */
    public static function generate(array $data, $storePassword)
    {
        if (empty($data['verify_key'])) {
            return null;
        }

        $fields = [];
        foreach (explode(',', $data['verify_key']) as $key) {
            $key = trim($key);
            if ($key === '') {
                continue;
            }
            $fields[$key] = isset($data[$key]) ? $data[$key] : '';
        }

        $fields['store_passwd'] = md5((string) $storePassword);
        ksort($fields);

        $pairs = [];
        foreach ($fields as $key => $value) {
            $pairs[] = $key . '=' . $value;
        }

        return md5(implode('&', $pairs));
    }

    /**
     * @param array  $data
     * @param string $storePassword
     * @return bool
     */
    public static function verify(array $data, $storePassword)
    {
        if (empty($data['verify_sign']) || empty($data['verify_key'])) {
            return false;
        }

        return hash_equals((string) static::generate($data, $storePassword), (string) $data['verify_sign']);
    }
}
