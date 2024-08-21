<?php

namespace FriendsOfBotble\Thawani\Providers;

use FriendsOfBotble\Thawani\Services\Gateways\ThawaniPaymentService;
use FriendsOfBotble\Thawani\Services\Thawani;
use Botble\Ecommerce\Models\Currency as CurrencyEcommerce;
use Botble\JobBoard\Models\Currency as CurrencyJobBoard;
use Botble\RealEstate\Models\Currency as CurrencyRealEstate;
use Botble\Hotel\Models\Currency as CurrencyHotel;
use Botble\Payment\Enums\PaymentMethodEnum;
use Exception;
use Html;
use Illuminate\Http\Request;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use PaymentMethods;
use Throwable;

class HookServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        add_filter(PAYMENT_FILTER_ADDITIONAL_PAYMENT_METHODS, [$this, 'registerThawaniMethod'], 19, 2);

        $this->app->booted(function () {
            add_filter(PAYMENT_FILTER_AFTER_POST_CHECKOUT, [$this, 'checkoutWithThawani'], 19, 2);
        });

        add_filter(PAYMENT_METHODS_SETTINGS_PAGE, [$this, 'addPaymentSettings'], 93, 1);

        add_filter(BASE_FILTER_ENUM_ARRAY, function ($values, $class) {
            if ($class == PaymentMethodEnum::class) {
                $values['THAWANI'] = THAWANI_PAYMENT_METHOD_NAME;
            }

            return $values;
        }, 32, 2);

        add_filter(BASE_FILTER_ENUM_LABEL, function ($value, $class) {
            if ($class == PaymentMethodEnum::class && $value == THAWANI_PAYMENT_METHOD_NAME) {
                $value = 'Thawani';
            }

            return $value;
        }, 32, 2);

        add_filter(BASE_FILTER_ENUM_HTML, function ($value, $class) {
            if ($class == PaymentMethodEnum::class && $value == THAWANI_PAYMENT_METHOD_NAME) {
                $value = Html::tag(
                    'span',
                    PaymentMethodEnum::getLabel($value),
                    ['class' => 'label-success status-label']
                )
                    ->toHtml();
            }

            return $value;
        }, 32, 2);

        add_filter(PAYMENT_FILTER_GET_SERVICE_CLASS, function ($data, $value) {
            if ($value == THAWANI_PAYMENT_METHOD_NAME) {
                $data = ThawaniPaymentService::class;
            }

            return $data;
        }, 32, 2);

        add_filter(PAYMENT_FILTER_PAYMENT_INFO_DETAIL, function ($data, $payment) {
            if ($payment->payment_channel == THAWANI_PAYMENT_METHOD_NAME) {
                $paymentService = (new ThawaniPaymentService());
                $paymentDetail = $paymentService->getPaymentDetails($payment);
                if ($paymentDetail) {
                    $data = view(
                        'plugins/thawani::detail',
                        ['payment' => $paymentDetail, 'paymentModel' => $payment]
                    )->render();
                }
            }

            return $data;
        }, 32, 2);
    }

    public function addPaymentSettings(?string $settings): string
    {
        return $settings . view('plugins/thawani::settings')->render();
    }

    public function registerThawaniMethod(?string $html, array $data): string
    {
        PaymentMethods::method(THAWANI_PAYMENT_METHOD_NAME, [
            'html' => view('plugins/thawani::methods', $data)->render(),
        ]);

        return $html;
    }

    public function checkoutWithThawani(array $data, Request $request): array
    {
        if ($data['type'] !== THAWANI_PAYMENT_METHOD_NAME) {
            return $data;
        }

        $currentCurrency = get_application_currency();

        $paymentData = apply_filters(PAYMENT_FILTER_PAYMENT_DATA, [], $request);

        if (strtoupper($currentCurrency->title) !== 'OMR') {
            $currency = match (true) {
                is_plugin_active('ecommerce') => CurrencyEcommerce::class,
                is_plugin_active('job-board') => CurrencyJobBoard::class,
                is_plugin_active('real-estate') => CurrencyRealEstate::class,
                is_plugin_active('hotel') => CurrencyHotel::class,
                default => null,
            };

            $supportedCurrency = $currency::query()->where('title', 'OMR')->first();

            if ($supportedCurrency) {
                $paymentData['currency'] = strtoupper($supportedCurrency->title);
                if ($currentCurrency->is_default) {
                    $paymentData['amount'] = $paymentData['amount'] * $supportedCurrency->exchange_rate;
                } else {
                    $paymentData['amount'] = format_price(
                        $paymentData['amount'] / $currentCurrency->exchange_rate,
                        $currentCurrency,
                        true
                    );
                }
            }
        }

        $supportedCurrencies = (new ThawaniPaymentService())->supportedCurrencyCodes();

        if (! in_array($paymentData['currency'], $supportedCurrencies)) {
            $data['error'] = true;
            $data['message'] = __(
                ":name doesn't support :currency. List of currencies supported by :name: :currencies.",
                [
                    'name' => 'Thawani',
                    'currency' => $data['currency'],
                    'currencies' => implode(', ', $supportedCurrencies),
                ]
            );

            return $data;
        }

        $orderIds = (array)$request->input('order_id', []);

        $products = [];

        foreach ($paymentData['products'] as $product) {
            $products[] = [
                'unit_amount' => (int)($product['price_per_order'] / $product['qty'] * get_current_exchange_rate(
                ) * 1000),
                'quantity' => $product['qty'],
                'name' => Str::limit($product['name'], 30),
            ];
        }

        $shippingAmount = (int)($paymentData['shipping_amount'] * 1000);

        if ($shippingAmount) {
            $products[] = [
                'unit_amount' => $shippingAmount,
                'quantity' => 1,
                'name' => __('Shipping fee'),
            ];
        }

        try {
            $thawani = new Thawani();

            $customerResponse = $thawani->callAPI(
                '/customers',
                ['client_customer_id' => $paymentData['address']['email']]
            );

            if (! $customerResponse['success']) {
                throw new Exception($customerResponse['message']);
            }

            $customerId = $customerResponse['data']['id'];

            $checkoutToken = $paymentData['checkout_token'];

            $referenceId = $checkoutToken . '-' . time();

            $params = [
                'client_reference_id' => $referenceId,
                'mode' => 'payment',
                'products' => $products,
                'total_amount' => $paymentData['amount'],
                'currency' => $paymentData['currency'],
                'customer_id' => $customerId,
                'success_url' => route('thawani.payment.callback', [
                    'checkout_token' => $checkoutToken,
                    'order_ids' => $orderIds,
                    'reference_id' => $referenceId,
                ]),
                'cancel_url' => route('thawani.payment.cancel'),
                'metadata' => [
                    'order_ids' => json_encode($orderIds),
                    'checkout_token' => $checkoutToken,
                    'customer_id' => $paymentData['customer_id'],
                    'customer_type' => $paymentData['customer_type'],
                ],
            ];

            $response = $thawani->callAPI('/checkout/session', $params);

            if ($response['success']) {
                $data['checkoutUrl'] = $thawani->getEndpoint(
                    sprintf('/pay/%s?key=%s', $response['data']['session_id'], $thawani->getPublishableKey())
                );

                return $data;
            }

            $data['error'] = true;
            $data['message'] = $response['message'];
        } catch (Throwable $exception) {
            $data['error'] = true;
            $data['message'] = json_encode($exception->getMessage());
        }

        return $data;
    }
}
