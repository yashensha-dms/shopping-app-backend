<?php

namespace Database\Seeders;

use App\Helpers\Helpers;
use App\Models\Setting;
use App\Models\Currency;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SettingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */

    public function run()
    {
        $currency_id = Currency::where('status', true)->first()->id;
        $values = [
            'general' => [
                'light_logo_image_id' => Helpers::getAttachmentId('logo-white.png'),
                'dark_logo_image_id' => Helpers::getAttachmentId('logo-dark.png'),
                'tiny_logo_image_id' => Helpers::getAttachmentId('tiny-logo.png'),
                'favicon_image_id' => Helpers::getAttachmentId('favicon.png'),
                'site_title' => 'Mstore Marketplace: Where Vendors Shine Together',
                'site_tagline' => "Shop Unique, Sell Exceptional – Mstore's Multi-Vendor Universe.",
                'site_name' => 'Mstore',
                'site_url' => '',
                'default_timezone' => 'Asia/Kolkata',
                'default_currency_id' => $currency_id,
                'admin_site_language_direction' => 'ltr',
                'min_order_amount' => 0,
                'min_order_free_shipping' => 50,
                'product_sku_prefix' => 'FS',
                'mode' => 'light-only',
                'copyright' => 'Copyright 2023 © Mstore theme by DMSG',
            ],
            'activation' => [
                'multivendor' => true,
                'point_enable' => true,
                'coupon_enable' => true,
                'wallet_enable' => true,
                'stock_product_hide' => false,
                'store_auto_approve' => true,
                'product_auto_approve' => true,
            ],
            'wallet_points' => [
                'signup_points' => 100,
                'min_per_order_amount' => 100,
                'point_currency_ratio' => 30,
                'reward_per_order_amount' => 10,
            ],
            'vendor_commissions' => [
                'status' => true,
                'min_withdraw_amount' => 500,
                'default_commission_rate' => 10,
                'is_category_based_commission' => true,
            ],
            'email' => [
                'mail_host' => '',
                'mail_port' => '',
                'mail_mailer' => 'smtp',
                'mail_username' => '',
                'mail_password' => '',
                'mail_encryption' => '',
                'mail_from_name' => 'no-reply',
                'mail_from_address' => '',
                'mailgun_domain' => null,
                'mailgun_secret' => null
            ],
            'refund' => [
                'status' => true,
                "refundable_days" => 7,
            ],
            'newsletter' => [
                'status' => false,
                'mailchip_api_key' => '',
                'mailchip_list_id' => '',
            ],
            'delivery' => [
                'default_delivery'=> 1,
                'default' => [
                    'title' => 'Standard Delivery',
                    'description' => 'Approx 5 to 7 Days'
                ],
                'same_day_delivery' => true,
                'same_day' => [
                    'title' => 'Express Delivery',
                    'description' => 'Schedule'
                ],
                'same_day_intervals' => [
                    [
                        'title' => 'Morning',
                        'description' => '8.00 AM - 12.00 AM',
                    ],
                    [
                        'title' => 'Noon',
                        'description' => '12.00 PM - 2.00 PM'
                    ],
                    [
                        'title' => 'Afternoon',
                        'description' => '02.00 PM - 05.00 PM',
                    ],
                    [
                        'title' => 'Evening',
                        'description' => '05.00 PM - 08.00 PM'
                    ]
                ]
            ],
            'payment_methods' => [
                'cod' => [
                    'status' => true
                ],
                'paypal' => [
                    'title' => 'PayPal',
                    'client_id' => 'AWSvIg3u2s-p7g2RYkcktJLjtn3Rsw0LZAm0CoS6WeYtEoYmSzRC01bT0wVxz4whG3eN4bCu1vparBbp',
                    'client_secret' => 'EPtAGaQiNig5iYMuxtoFs_kVimBODw7axl7hSjn21YLPi6aCRJymPoU2n9GtLWNVqXGWj155XRK7Kpcm',
                    'status' => true,
                    'sandbox_mode' => true,
                ],
                'stripe' => [
                    'title' => 'Stripe',
                    'key' => 'pk_test_51MmTx1SHGHXeqsVlOWH2cwf42zty7jStl9ngvASN79Vri7bwGsbOSTGFTf17O2r5PiCIinh6vmO5FGrU5B2ymW7L00OcvpXwT3',
                    'secret' => 'sk_test_51MmTx1SHGHXeqsVlAbforUpNIqByURbQy2xKZLlDrSNUvtvbgjywaaEZfGsbcQxIh0ggazGXrfnZBy0rQSLCqvzo00PyWPfbne',
                    'status' => true,
                ],
                'razorpay' => [
                    'title' => 'RazorPay',
                    'key' => 'rzp_test_iV7SM01Wb7wvhv',
                    'secret' => 'gjdchqP3v7shiW7SRKo2xecV',
                    'status' => true
                ],
                'mollie' => [
                    'title' => 'Mollie',
                    'secret_key' => 'test_pKDxyTWpj6bFDuy67DBq4KHFqWSCEf',
                    'status' => true,
                ],
                'ccavenue' => [
                    'title' => 'CCAvenue',
                    'merchant_id' => '',
                    'working_key' => '',
                    'access_code' => '',
                    'status' => true,
                    'sandbox_mode' => true,
                ],
                'phonepe' => [
                    'title' => 'PhonePe',
                    'merchant_id' => 'PGTESTPAYUAT',
                    'salt_key' => '099eb0cd-02cf-4e2a-8aca-3e6c6aff0399',
                    'salt_index' => 1,
                    'status' => true,
                    'sandbox_mode' => true,
                ],
                'instamojo' => [
                    'title' => 'InstaMojo',
                    'client_id' => '',
                    'client_secret' => '',
                    'salt_key' => '',
                    'status' => true,
                    'sandbox_mode' => true,
                ],
            ],
            'google_reCaptcha' => [
                'secret' => '',
                'site_key' => '',
                'status' => false,
            ],
            'maintenance' => [
                'title' => "We'll be back Soon..",
                'maintenance_mode' => false,
                'maintenance_image_id' => Helpers::getAttachmentId('maintainance.jpg'),
                'description' => "We are busy to updating our store for you."
            ]
        ];

        Setting::updateOrCreate(['values' => $values]);
        DB::table('seeders')->updateOrInsert([
            'name' => 'SettingSeeder',
            'is_completed' => true
        ]);
    }
}
