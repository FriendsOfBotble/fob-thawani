<?php

namespace FriendsOfBotble\Thawani\Services\Gateways;

use FriendsOfBotble\Thawani\Services\Abstracts\ThawaniPaymentAbstract;
use Illuminate\Http\Request;

class ThawaniPaymentService extends ThawaniPaymentAbstract
{
    public function makePayment(Request $request)
    {
    }

    public function afterMakePayment(Request $request)
    {
    }

    public function supportedCurrencyCodes(): array
    {
        return [
            'OMR',
        ];
    }
}
