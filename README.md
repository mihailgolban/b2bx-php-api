## B2BX PHP API
PHP client library for accessing the B2BX's exchange API

### Install

To install b2bx api client, run the command below and you will get the latest version
```
composer require felex/b2bx-api
```

### Configuration
Set in the .env file the following keys:
```bash
#--------------------------------------------------------------------
# API
# https://docs.b2bx.exchange/en/_docs/api-reference.html#overview
#--------------------------------------------------------------------

B2BXAPI.BASE_URL="https://api.b2bx.exchange:8443/trading"
B2BXAPI.BASE2_URL="https://cmc-gate.b2bx.exchange/marketdata/cmc/v1"

#--------------------------------------------------------------------
# PRIVATE API
# https://my.b2bx.exchange/en/profile/api-key-management
#--------------------------------------------------------------------
B2BXAPI.PUBLIC_KEY="<Public API Key>"
B2BXAPI.PRIVATE_KEY="<Private API Key>"
```

### Usage
```php
$api = new \B2BX\Api();

$resp = $api->placeLimitOrder([
    'instrument' => 'btc_usd',
    'type' => 'buy',
    'amount' => "0.001",
    'price' => "20000"
]);

print_r($resp);
```

### License
The MIT License (MIT). Please see [License File](https://opensource.org/licenses/MIT) for more information.