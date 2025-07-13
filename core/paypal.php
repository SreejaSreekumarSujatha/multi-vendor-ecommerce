<?php
// config/paypal.php

class PayPalConfig {
    // PayPal Sandbox (for testing)
    const SANDBOX_URL = 'https://www.sandbox.paypal.com/cgi-bin/webscr';
    const SANDBOX_BUSINESS_EMAIL = 'your-sandbox-business@example.com';
    
    // PayPal Live (for production)
    const LIVE_URL = 'https://www.paypal.com/cgi-bin/webscr';
    const LIVE_BUSINESS_EMAIL = 'your-live-business@example.com';
    
    // Set to true for testing, false for production
    const USE_SANDBOX = true;
    
    // Your website URLs
    const RETURN_URL = 'http://localhost/your-project/index.php?action=paypal-success';
    const CANCEL_URL = 'http://localhost/your-project/index.php?action=paypal-cancel';
    const NOTIFY_URL = 'http://localhost/your-project/index.php?action=paypal-ipn';
    
    public static function getPayPalURL() {
        return self::USE_SANDBOX ? self::SANDBOX_URL : self::LIVE_URL;
    }
    
    public static function getBusinessEmail() {
        return self::USE_SANDBOX ? self::SANDBOX_BUSINESS_EMAIL : self::LIVE_BUSINESS_EMAIL;
    }
}
?>