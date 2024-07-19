@php $thawaniStatus = get_payment_setting('status', THAWANI_PAYMENT_METHOD_NAME); @endphp

<table class="table payment-method-item">
    <tbody>
    <tr class="border-pay-row">
        <td class="border-pay-col"><i class="fa fa-theme-payments"></i></td>
        <td style="width: 20%;">
            <img class="filter-black" src="{{ url('vendor/core/plugins/thawani/images/logo.svg') }}"
                 alt="Thawani">
        </td>
        <td class="border-right">
            <ul>
                <li>
                    <a href="https://thawani.om" target="_blank">{{ __('Thawani') }}</a>
                    <p>{{ __('Customer can buy product and pay directly using Visa, Credit card via :name', ['name' => 'Thawani']) }}</p>
                </li>
            </ul>
        </td>
    </tr>
    <tr class="bg-white">
        <td colspan="3">
            <div class="float-start" style="margin-top: 5px;">
                <div
                    class="payment-name-label-group @if (get_payment_setting('status', THAWANI_PAYMENT_METHOD_NAME) == 0) hidden @endif">
                    <span class="payment-note v-a-t">{{ trans('plugins/payment::payment.use') }}:</span> <label
                        class="ws-nm inline-display method-name-label">{{ get_payment_setting('name', THAWANI_PAYMENT_METHOD_NAME) }}</label>
                </div>
            </div>
            <div class="float-end">
                <a class="btn btn-secondary toggle-payment-item edit-payment-item-btn-trigger @if ($thawaniStatus == 0) hidden @endif">{{ trans('plugins/payment::payment.edit') }}</a>
                <a class="btn btn-secondary toggle-payment-item save-payment-item-btn-trigger @if ($thawaniStatus == 1) hidden @endif">{{ trans('plugins/payment::payment.settings') }}</a>
            </div>
        </td>
    </tr>
    <tr class="paypal-online-payment payment-content-item hidden">
        <td class="border-left" colspan="3">
            {!! Form::open() !!}
            {!! Form::hidden('type', THAWANI_PAYMENT_METHOD_NAME, ['class' => 'payment_type']) !!}
            <div class="row">
                <div class="col-sm-6">
                    <ul>
                        <li>
                            <label>{{ trans('plugins/payment::payment.configuration_instruction', ['name' => 'Thawani']) }}</label>
                        </li>
                        <li class="payment-note">
                            <p>{{ trans('plugins/payment::payment.configuration_requirement', ['name' => 'Thawani']) }}:</p>
                            <ul class="m-md-l" style="list-style-type:decimal">
                                <li style="list-style-type:decimal">
                                    <a href="https://thawani.om" target="_blank">
                                        {{ __('Register an account on :name', ['name' => 'Thawani']) }}
                                    </a>
                                </li>
                                <li style="list-style-type:decimal">
                                    <p>{{ __('After registration at :name, you will have API & Secret keys', ['name' => 'Thawani Checkout']) }}</p>
                                </li>
                                <li style="list-style-type:decimal">
                                    <p>{{ __('Enter API key, Secret into the box in right hand') }}</p>
                                </li>
                            </ul>
                        </li>
                    </ul>
                </div>
                <div class="col-sm-6">
                    <div class="well bg-white">
                        <div class="form-group mb-3">
                            <label class="text-title-field"
                                   for="thawani_name">{{ trans('plugins/payment::payment.method_name') }}</label>
                            <input type="text" class="next-input" name="payment_{{ THAWANI_PAYMENT_METHOD_NAME }}_name"
                                   id="thawani_name" data-counter="400"
                                   value="{{ get_payment_setting('name', THAWANI_PAYMENT_METHOD_NAME, __('Online payment via :name', ['name' => 'Thawani'])) }}">
                        </div>

                        <div class="form-group mb-3">
                            <label class="text-title-field" for="payment_{{ THAWANI_PAYMENT_METHOD_NAME }}_description">{{ trans('core/base::forms.description') }}</label>
                            <textarea class="next-input" name="payment_{{ THAWANI_PAYMENT_METHOD_NAME }}_description" id="payment_{{ THAWANI_PAYMENT_METHOD_NAME }}_description">{{ get_payment_setting('description', THAWANI_PAYMENT_METHOD_NAME) }}</textarea>
                        </div>

                        <p class="payment-note">
                            {{ trans('plugins/payment::payment.please_provide_information') }} <a target="_blank" href="https://thawani.om">Thawani</a>:
                        </p>
                        <div class="form-group mb-3">
                            <label class="text-title-field" for="{{ THAWANI_PAYMENT_METHOD_NAME }}_publishable_key">{{ __('Publishable Key') }}</label>
                            <input type="text" class="next-input"
                                   name="payment_{{ THAWANI_PAYMENT_METHOD_NAME }}_publishable_key" id="{{ THAWANI_PAYMENT_METHOD_NAME }}_publishable_key"
                                   value="{{ get_payment_setting('publishable_key', THAWANI_PAYMENT_METHOD_NAME) }}">
                        </div>
                        <div class="form-group mb-3">
                            <label class="text-title-field" for="{{ THAWANI_PAYMENT_METHOD_NAME }}_secret_key">{{ __('Secret Key') }}</label>
                            <input type="text" class="next-input"
                                   name="payment_{{ THAWANI_PAYMENT_METHOD_NAME }}_secret_key" id="{{ THAWANI_PAYMENT_METHOD_NAME }}_secret_key"
                                   value="{{ get_payment_setting('secret_key', THAWANI_PAYMENT_METHOD_NAME) }}">
                        </div>
                        <div class="form-group mb-3">
                            {!! Form::hidden('payment_' . THAWANI_PAYMENT_METHOD_NAME . '_mode', 1) !!}
                            <label class="next-label">
                                <input type="checkbox" value="0" name="payment_{{ THAWANI_PAYMENT_METHOD_NAME }}_mode" @if (setting('payment_' . THAWANI_PAYMENT_METHOD_NAME . '_mode') == 0) checked @endif>
                                {{ trans('plugins/payment::payment.sandbox_mode') }}
                            </label>
                        </div>

                        {!! apply_filters(PAYMENT_METHOD_SETTINGS_CONTENT, null, THAWANI_PAYMENT_METHOD_NAME) !!}
                    </div>
                </div>
            </div>
            <div class="col-12 bg-white text-end">
                <button class="btn btn-warning disable-payment-item @if ($thawaniStatus == 0) hidden @endif"
                        type="button">{{ trans('plugins/payment::payment.deactivate') }}</button>
                <button
                    class="btn btn-info save-payment-item btn-text-trigger-save @if ($thawaniStatus == 1) hidden @endif"
                    type="button">{{ trans('plugins/payment::payment.activate') }}</button>
                <button
                    class="btn btn-info save-payment-item btn-text-trigger-update @if ($thawaniStatus == 0) hidden @endif"
                    type="button">{{ trans('plugins/payment::payment.update') }}</button>
            </div>
            {!! Form::close() !!}
        </td>
    </tr>
    </tbody>
</table>
