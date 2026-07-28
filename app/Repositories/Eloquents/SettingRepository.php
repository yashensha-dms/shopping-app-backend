<?php

namespace App\Repositories\Eloquents;

use Exception;
use App\Models\Setting;
use App\Models\Currency;
use App\Helpers\Helpers;
use Illuminate\Support\Arr;
use App\Enums\PaymentMethod;
use App\Enums\FrontSettingsEnum;
use Illuminate\Support\Facades\DB;
use App\GraphQL\Exceptions\ExceptionHandler;
use Jackiedo\DotenvEditor\Facades\DotenvEditor;
use Prettus\Repository\Eloquent\BaseRepository;

class SettingRepository extends BaseRepository
{
    protected $currency;

    function model()
    {
        $this->currency = new Currency();
        return Setting::class;
    }

    public function frontSettings()
    {
        try {

            $settingValues = Helpers::getSettings();
            $paymentMethods = PaymentMethod::ALL_PAYMENT_METHODS;

            foreach ($paymentMethods as $paymentMethod) {
                $paymentMethodStatus[] = [
                    "name" => $paymentMethod,
                    "title" => $settingValues['payment_methods'][$paymentMethod]['title'],
                    "status" => $settingValues['payment_methods'][$paymentMethod]['status']
                ];
            }

            $settings['values'] = Arr::only($settingValues, array_column(FrontSettingsEnum::cases(), 'value'));
            $settings['values']['payment_methods'] = $paymentMethodStatus;

            return $settings;

        } catch (Exception $e) {

            throw new ExceptionHandler($e->getMessage(), $e->getCode());
        }
    }

    public function update($request, $id)
    {
        DB::beginTransaction();
        try {

            $settings = $this->model->first();
            $settings->update($request);
            $settings = $settings->fresh();
            $this->env($request['values']);

            DB::commit();
            return $settings;

        } catch (Exception $e) {

            DB::rollback();
            throw new ExceptionHandler($e->getMessage(), $e->getCode());
        }
    }

    public function setDefaultCurrencyBasePrice($settings)
    {
        $currency = $this->currency->findOrFail($settings['general']['default_currency_id']);
        $currency->update([
            'exchange_rate' => true
        ]);
    }

    public function env($value)
    {
        try {
            if (isset($value['email'])) {
                DotenvEditor::setKeys([
                    'MAIL_MAILER' => $value['email']["mail_mailer"] ?? null,
                    'MAIL_HOST' => $value['email']["mail_host"] ?? null,
                    'MAIL_PORT' => $value['email']["mail_port"] ?? null,
                    'MAIL_USERNAME' => $value['email']["mail_username"] ?? null,
                    'MAIL_PASSWORD' => $value['email']["mail_password"] ?? null,
                    'MAIL_ENCRYPTION' => $value['email']["mail_encryption"] ?? null,
                    'MAIL_FROM_ADDRESS' => $value['email']["mail_from_address"] ?? null,
                    'MAIL_FROM_NAME' => $value['email']["mail_from_name"] ?? null,
                    'MAILGUN_DOMAIN' => $value['email']["mailgun_domain"] ?? null,
                    'MAILGUN_SECRET' => $value['email']["mailgun_secret"] ?? null,
                ]);

                DotenvEditor::save();
            }

            if (isset($value['payment_methods'])) {
                $paypal_mode = ($value['payment_methods']['paypal']["sandbox_mode"] ?? null) ? 'sandbox' : 'live';
                DotenvEditor::setKeys([
                    'PAYPAL_MODE' =>  $paypal_mode,
                    'PAYPAL_CLIENT_ID' => $value['payment_methods']['paypal']["client_id"] ?? null,
                    'PAYPAL_CLIENT_SECRET' => $value['payment_methods']['paypal']["client_secret"] ?? null,
                    'STRIPE_API_KEY' => $value['payment_methods']['stripe']["key"] ?? null,
                    'STRIPE_SECRET_KEY' => $value['payment_methods']['stripe']["secret"] ?? null,
                    'RAZORPAY_KEY' => $value['payment_methods']['razorpay']["key"] ?? null,
                    'RAZORPAY_SECRET' => $value['payment_methods']['razorpay']["secret"] ?? null,
                    'PHONEPE_MERCHANT_ID' => $value['payment_methods']['phonepe']["merchant_id"] ?? null,
                    'PHONEPE_SALT_KEY' => $value['payment_methods']['phonepe']["salt_key"] ?? null,
                    'PHONEPE_SALT_INDEX' => $value['payment_methods']['phonepe']["salt_index"] ?? null,
                    'INSTAMOJO_SANDBOX_MODE' => $value['payment_methods']['instamojo']["sandbox_mode"] ?? null,
                    'INSTAMOJO_CLIENT_ID' => $value['payment_methods']['instamojo']["client_id"] ?? null,
                    'INSTAMOJO_CLIENT_SECRET' => $value['payment_methods']['instamojo']["client_secret"] ?? null,
                    'INSTAMOJO_SALT_KEY' => $value['payment_methods']['instamojo']["salt_key"] ?? null,
                ]);

                DotenvEditor::save();
            }

        } catch (Exception $e) {
            DB::rollback();
            throw new ExceptionHandler($e->getMessage(), $e->getCode());
        }
    }
}
