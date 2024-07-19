<?php

namespace FriendsOfBotble\Thawani\Services\Abstracts;

use FriendsOfBotble\Thawani\Services\Thawani;
use Botble\Payment\Models\Payment;
use Botble\Payment\Repositories\Interfaces\PaymentInterface;
use Botble\Payment\Services\Traits\PaymentErrorTrait;
use Botble\Support\Services\ProduceServiceInterface;
use GuzzleHttp\Client;
use Illuminate\Http\Request;
use Throwable;

abstract class ThawaniPaymentAbstract implements ProduceServiceInterface
{
    use PaymentErrorTrait;

    protected string $paymentCurrency;

    protected Client $client;

    protected bool $supportRefundOnline;

    public function __construct()
    {
        $this->paymentCurrency = config('plugins.payment.payment.currency');

        $this->supportRefundOnline = true;
    }

    public function getPaymentDetails(Payment $payment): array
    {
        try {
            return (new Thawani())->callAPI('/payments/' . $payment->charge_id, [], 'GET');
        } catch (Throwable $exception) {
            $this->setErrorMessageAndLogging($exception, 1);

            return [];
        }
    }

    public function execute(Request $request)
    {
        try {
            return $this->makePayment($request);
        } catch (Throwable $exception) {
            $this->setErrorMessageAndLogging($exception, 1);

            return false;
        }
    }

    abstract public function makePayment(Request $request);

    abstract public function afterMakePayment(Request $request);

    public function getSupportRefundOnline(): bool
    {
        return $this->supportRefundOnline;
    }

    public function refundOrder($paymentId, $totalAmount, array $options = [])
    {
        $payment = app(PaymentInterface::class)->getFirstBy([
            'charge_id' => $paymentId,
        ]);

        if (! $payment) {
            return [
                'error' => true,
                'message' => __('Payment not found!'),
            ];
        }

        if ($totalAmount < $payment->amount) {
            return [
                'error' => true,
                'message' => __('This payment gateway is not allowed partial refund!'),
            ];
        }

        try {
            $thawani = new Thawani();

            $response = $thawani->callAPI('/refunds', [
                'payment_id' => $paymentId,
                'reason' => $options['refund_note'] ?: 'Refund order #' . $payment->order_id,
                'metadata' => $options,
            ]);

            if ($response['success']) {
                return [
                    'error' => false,
                    'message' => $response['description'],
                    'data' => $response,
                ];
            }

            return [
                'error' => true,
                'message' => trans('plugins/payment::payment.status_is_not_completed'),
            ];
        } catch (Throwable $exception) {
            return [
                'error' => true,
                'message' => $exception->getMessage(),
            ];
        }
    }
}
