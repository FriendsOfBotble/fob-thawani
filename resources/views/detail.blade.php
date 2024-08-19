@if ($payment)
    <div class="mt-4">
        <p>
            <span>{{ trans('plugins/payment::payment.payment_id') }}: </span>
            {{ $payment['data']['payment_id'] }}
        </p>
        <p>{{ trans('plugins/payment::payment.amount') }}: {{ $payment['data']['amount'] / 1000 }}</p>
        <hr>

        @if ($payment['data']['refunded'] && count($payment['data']['refunds']))
            <br />
            <h6 class="alert-heading">{{ trans('plugins/payment::payment.refunds.title') . ' (' . ($payment['data']['amount'] / 1000) . ')'}}</h6>
            <hr class="m-0 mb-4">
            @foreach ($payment['data']['refunds'] as $item)
                <div class="alert alert-warning" role="alert">
                    <p>{{ trans('plugins/payment::payment.refunds.id') }}: {{ $item['refund_id'] }}</p>
                    <p>{{ trans('plugins/payment::payment.amount') }}: {{ $item['amount'] / 1000 }} </p>
                    <p>{{ __('Refund reason') }}: {{ $item['reason'] }}</p>
                    <p>{{ trans('plugins/payment::payment.refunds.status') }}: {{ strtoupper($item['status']) }}</p>
                    <p>{{ trans('plugins/payment::payment.refunds.create_time') }}: {{ now()->parse($item['created_at']) }}</p>
                </div>
                <br />
            @endforeach
        @endif

        @include('plugins/payment::partials.view-payment-source')
    </div>
@endif
