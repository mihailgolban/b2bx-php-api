<?php

namespace B2BX;

use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Uri;
use GuzzleHttp\Psr7\Utils;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

class Api
{
    /** @var Client */
    private $client;

    public function __construct(array $config = [])
    {
        $this->client = new Client($config);
    }

    private function sendHTTPRequest(RequestInterface $request): ?ResponseInterface
    {
        $request = $request->withHeader('user-agent', 'Mozilla/5.0 (Windows NT 6.1; Win64; x64; rv:47.0) Gecko/20100101 Firefox/47.0');
        return $this->client->send($request);
    }

    public function sendPrivateHTTPRequest(RequestInterface $request, array $data = []): ?ResponseInterface
    {
        $ts = gmdate('Y-m-d\TH:i:s');
        $nonce = hexdec(uniqid());

        if ('POST' === $request->getMethod()) {
            $payload = json_encode(array_merge(['ts' => $ts, 'nonce' => $nonce], $data));
            $request = $request->withBody(Utils::streamFor($payload));
        } else {
            $query = http_build_query(['ts' => $ts, 'nonce' => $nonce]);
            $uriWithoutQuery = explode('?', $request->getUri())[0];
            $payload = '?'.$request->getUri()->getQuery()."&{$query}";
            $request = $request->withUri(new Uri($uriWithoutQuery.$payload));
        }

        $hash = strtoupper(hash_hmac('sha512', $payload, $_ENV['B2BXAPI.PRIVATE_KEY']));

        $request = $request->withHeader('Key', $_ENV['B2BXAPI.PUBLIC_KEY']);
        $request = $request->withHeader('Sign', $hash);
        $request = $request->withHeader('Content-Type', 'application/json');

        return $this->sendHTTPRequest($request);
    }

    /**
     * https://docs.b2bx.exchange/en/_docs/api-reference.html#supported-instruments.
     */
    public function getSupportedInstruments(): ?array
    {
        $url = base_url('/frontoffice/api/info');
        $request = new Request('GET', $url);
        $response = $this->sendHTTPRequest($request)->getBody();

        return json_decode($response, true);
    }

    /**
     * https://docs.b2bx.exchange/en/_docs/api-reference.html#order-book-snapshot.
     *
     * @param string $instrument Instrument identifier: {baseAsset}_{quoteAsset}
     */
    public function getOrderBookSnapshot(string $instrument): ?array
    {
        $instrument = strtolower($instrument);
        $url = base_url("/marketdata/instruments/{$instrument}/depth");
        $request = new Request('GET', $url);
        $response = $this->sendHTTPRequest($request)->getBody();

        return json_decode($response, true);
    }

    /**
     * https://docs.b2bx.exchange/en/_docs/api-reference.html#instrument-candles.
     *
     * @param string $instrument Instrument identifier: {baseAsset}_{quoteAsset}
     * @param string $startDate  Date and time of period start: YYYY-MM-DDThh:mm:ss
     * @param string $endDate    Date and time of period end: YYYY-MM-DDThh:mm:ss
     * @param string $type       Timeframe, the following values are avaliable: 1m — 1 Minute, 5m — 5 Minutes, 15m — 15 Minutes,
     *                           30m — 30 Minutes, 1h — 1 Hour, 12h — 12 Hours, 1d — 1 Day, 1w — 1 Week, 1M — 1 Month
     * @param int    $count      Number of candles to return, defaults to 1000 (maximum value)
     */
    public function getInstrumentCandles(string $instrument, string $startDate, string $endDate, string $type = '1d', int $count = 1000): ?array
    {
        $instrument = strtolower($instrument);
        $url = base_url("/marketdata/instruments/{$instrument}/history?startDate={$startDate}&endDate={$endDate}&type={$type}&count={$count}");
        $request = new Request('GET', $url);
        $response = $this->sendHTTPRequest($request)->getBody();

        return json_decode($response, true);
    }

    /**
     * https://docs.b2bx.exchange/en/_docs/api-reference.html#asset-info.
     */
    public function getAssetInfo(): ?array
    {
        $url = base2_url('/asset');
        $request = new Request('GET', $url);
        $response = $this->sendHTTPRequest($request)->getBody();

        return json_decode($response, true);
    }

    /**
     * https://docs.b2bx.exchange/en/_docs/api-reference.html#summary.
     */
    public function getSummary(): ?array
    {
        $url = base2_url('/summary');
        $request = new Request('GET', $url);
        $response = $this->sendHTTPRequest($request)->getBody();

        return json_decode($response, true);
    }

    /**
     * https://docs.b2bx.exchange/en/_docs/api-reference.html#ticker-info.
     */
    public function getTickerInfo(): ?array
    {
        $url = base2_url('/ticker');
        $request = new Request('GET', $url);
        $response = $this->sendHTTPRequest($request)->getBody();

        return json_decode($response, true);
    }

    /**
     * https://docs.b2bx.exchange/en/_docs/api-reference.html#trades-info.
     */
    public function getTradesInfo(string $instrument): array
    {
        $instrument = strtolower($instrument);
        $url = base2_url("/trades/{$instrument}");
        $request = new Request('GET', $url);
        $response = $this->sendHTTPRequest($request)->getBody();

        return json_decode($response, true);
    }

    /**
     * Places a new order.
     *
     * https://docs.b2bx.exchange/en/_docs/api-reference.html#place-an-order
     *
     * @param array $order
     *                     $order = [
     *                     'instrument'    => (string, required) Order instrument identifier: {baseAsset}_{quoteAsset}
     *                     'type'          => (string, required) Order side: "buy" or "sell"
     *                     'amount'        => (float, required) Order amount, must be greater than 0
     *                     'price'         => (float) Order price. Required for limit orders, optional for market orders. If isLimit is true, must be greater than 0, in other cases can be equal to 0
     *                     'isLimit'       => (bool) If true, the order is limit. If false, the order is market.
     *                     'isFok'       => (bool) If true, the order must be executed immediately or be cancelled if not filled.
     *                     'clientOrderId' => (float) Client provided order identifier: any UUID, except for 00000000-0000-0000-0000-000000000000
     *                     ]
     */
    public function placeOrder(array $order): ?array
    {
        $url = base_url('/frontoffice/api/order');
        $request = new Request('POST', $url);
        $response = $this->sendPrivateHTTPRequest($request, ['order' => $order])->getBody();

        return json_decode($response, true);
    }

    /**
     * Place Limit order.
     *
     * https://docs.b2bx.exchange/en/_docs/api-reference.html#place-an-order
     *
     * @param array $order
     *                     $order = [
     *                     'instrument'    => (string, required) Order instrument identifier: {baseAsset}_{quoteAsset}
     *                     'type'          => (string, required) Order side: "buy" or "sell"
     *                     'amount'        => (float, required) Order amount, must be greater than 0
     *                     'price'         => (float, required) Order price, must be greater than 0,
     *                     'clientOrderId' => (float) Client provided order identifier: any UUID, except for 00000000-0000-0000-0000-000000000000
     *                     ]
     */
    public function placeLimitOrder(array $order): ?array
    {
        $order['isLimit'] = true;
        $order['isStop'] = false;
        $order['isFok'] = false;

        return $this->placeOrder($order);
    }

    /**
     * Market order (Immediate-or-Cancel market order) — order must be executed immediately and cancel any unfilled portion.
     *
     * @param array $order
     *                     $order = [
     *                     'instrument'    => (string, required) Order instrument identifier: {baseAsset}_{quoteAsset}
     *                     'type'          => (string, required) Order side: "buy" or "sell"
     *                     'amount'        => (float, required) Order amount, must be greater than 0
     *                     'clientOrderId' => (float) Client provided order identifier: any UUID, except for 00000000-0000-0000-0000-000000000000
     *                     ]
     */
    public function placeImmediateOrCancelMarketOrder(array $order): ?array
    {
        $order['isLimit'] = false;
        $order['isStop'] = false;
        $order['isFok'] = false;

        return $this->placeOrder($order);
    }

    /**
     * Fill-or-Kill market order — order must be executed immediately or cancelled if not filled.
     *
     * @param array $order
     *                     $order = [
     *                     'instrument'    => (string, required) Order instrument identifier: {baseAsset}_{quoteAsset}
     *                     'type'          => (string, required) Order side: "buy" or "sell"
     *                     'amount'        => (float, required) Order amount, must be greater than 0
     *                     'clientOrderId' => (float) Client provided order identifier: any UUID, except for 00000000-0000-0000-0000-000000000000
     *                     ]
     */
    public function placeFillOrKillMarketOrder(array $order): ?array
    {
        $order['isLimit'] = false;
        $order['isStop'] = false;
        $order['isFok'] = true;

        return $this->placeOrder($order);
    }

    /**
     * Cancel an order.
     *
     * https://docs.b2bx.exchange/en/_docs/api-reference.html#cancel-an-order.
     *
     * @param string $id Order identifier: either orderId — same as in the response message generated with Place an Order request,
     *                   or clientOrderId — same as in the Place an Order request
     */
    public function cancelOrder(string $id): ?array
    {
        $url = base_url("/frontoffice/api/orders?orderId={$id}");
        $request = new Request('DELETE', $url);
        $response = $this->sendPrivateHTTPRequest($request)->getBody();

        return json_decode($response, true);
    }

    /**
     * https://docs.b2bx.exchange/en/_docs/api-reference.html#orders-history.
     */
    public function getOrderHistory(array $filters = []): ?array
    {
        $query = http_build_query([
            'market' => $filters['market'] ?? null,
            'side' => $filters['side'] ?? null,
            'status' => $filters['status'] ?? null,
            'startDate' => $filters['startData'] ?? null,
            'endDate' => $filters['endDate'] ?? null,
        ]);
        $query = $query ? "?{$query}" : '';
        $url = base_url("/frontoffice/api/order_history{$query}");
        $request = new Request('GET', $url);
        $response = $this->sendPrivateHTTPRequest($request)->getBody();

        return json_decode($response, true);
    }

    /**
     * https://docs.b2bx.exchange/en/_docs/api-reference.html#trades-history.
     */
    public function getTradesHistory(array $filters = []): ?array
    {
        $query = http_build_query([
            'orderId' => $filters['orderId'] ?? null,
            'market' => $filters['market'] ?? null,
            'side' => $filters['side'] ?? null,
            'startDate' => $filters['startData'] ?? null,
            'endDate' => $filters['endDate'] ?? null,
        ]);
        $query = $query ? "?{$query}" : '';
        $url = base_url("/frontoffice/api/trade_history{$query}");
        $request = new Request('GET', $url);
        $response = $this->sendPrivateHTTPRequest($request)->getBody();

        return json_decode($response, true);
    }

    /**
     * https://docs.b2bx.exchange/en/_docs/api-reference.html#user-balance.
     */
    public function getUserBalance(): ?array
    {
        $url = base_url('/frontoffice/api/balances');
        $request = new Request('GET', $url);
        $response = $this->sendPrivateHTTPRequest($request)->getBody();

        return json_decode($response, true);
    }
}
