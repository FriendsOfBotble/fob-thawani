<?php

namespace FriendsOfBotble\Thawani\Http\Controllers;

use Botble\Base\Http\Controllers\BaseController;
use Botble\Base\Http\Responses\BaseHttpResponse;
use Botble\Payment\Enums\PaymentStatusEnum;
use Botble\Payment\Supports\PaymentHelper;
use FriendsOfBotble\Thawani\Services\Thawani;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Throwable;

class ThawaniController extends BaseController
{
    public function getCallback(Request $request, BaseHttpResponse $response, Thawani $thawani): BaseHttpResponse
    {
        $orderIds = (array) $request->input('order_ids');

        $referenceId = $request->input('reference_id');

        try {
            $result = $thawani->callAPI('/checkout/reference/' . $referenceId, [], 'GET');

            $data = $result['data'];

            if ($result['success'] && in_array($data['payment_status'], ['paid', 'unpaid'])) {
                $paymentResponse = $thawani->callAPI('/payments?checkout_invoice=' . $data['invoice'], [], 'GET');

                do_action(PAYMENT_ACTION_PAYMENT_PROCESSED, [
                    'amount' => $data['total_amount'] / 1000,
                    'currency' => $data['currency'],
                    'charge_id' => $paymentResponse['data'][0]['payment_id'],
                    'payment_channel' => THAWANI_PAYMENT_METHOD_NAME,
                    'status' => PaymentStatusEnum::COMPLETED,
                    'customer_id' => Arr::get($data, 'metadata.customer_id'),
                    'customer_type' => Arr::get($data, 'metadata.customer_type'),
                    'payment_type' => 'direct',
                    'order_id' => $orderIds,
                ], $request);

                $nextUrl = PaymentHelper::getRedirectURL();

                if (is_plugin_active('job-board') || is_plugin_active('real-estate')) {
                    $nextUrl = $nextUrl . '?charge_id=' . $paymentResponse['data'][0]['payment_id'];
                }

                return $response
                    ->setNextUrl($nextUrl)
                    ->setMessage(__('Checkout successfully!'));
            }

            if (Arr::get($data, 'payment_status') === 'cancelled') {
                $data['message'] = __('Payment cancelled!');
            }

            return $response
                ->setError()
                ->setNextUrl(PaymentHelper::getCancelURL())
                ->setMessage($data['message'] ?? __('Payment failed!'));
        } catch (Throwable $exception) {
            return $response
                ->setError()
                ->setNextUrl(PaymentHelper::getCancelURL())
                ->setMessage($exception->getMessage());
        }
    }

    public function getCancel(BaseHttpResponse $response): BaseHttpResponse
    {
        return $response
            ->setError()
            ->setNextUrl(PaymentHelper::getCancelURL())
            ->setMessage(__('Payment failed!'));
    }
}
