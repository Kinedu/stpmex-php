<?php

namespace Kinedu\STP;

use Kinedu\STP\Request\{
    RestHttpClient,
    SoapHttpClient
};
use Kinedu\STP\Service\{
    AccountService,
    CatalogueService,
    OrderService
};

class Client
{
    /** @var string */
    protected $key;

    /** @var string */
    protected $passphrase;

    /** @var string */
    public $accountKey;

    /** @var bool */
    public $live;

    /** @var array */
    protected $services = [
        'account' => AccountService::class,
        'catalogue' => CatalogueService::class,
        'order' => OrderService::class,
    ];

    /** @var array */
    protected $restServices = [
        'order',
    ];

    /**
     * Create a client instance.
     *
     * @param  string  $key
     * @param  string  $passphrase
     * @param  string  $accountKey
     * @param  bool  $live
     *
     * @return void
     */
    public function __construct(string $key, string $accountKey, string $passphrase, bool $live = false)
    {
        $this->key = $key;
        $this->passphrase = $passphrase;
        $this->accountKey = $accountKey;
        $this->live = $live;
    }

    public function getSignature(string $data = null): string
    {
        $pkey = $this->getCertificate();
        openssl_sign($data ?? $this->accountKey, $signature, $pkey, OPENSSL_ALGO_SHA256);
        openssl_free_key($pkey);
        return base64_encode($signature);
    }

    public function getCertificate()
    {
        return openssl_get_privatekey($this->key, $this->passphrase);
    }

    protected function httpClient(string $service)
    {
        return (in_array($service, $this->restServices))
            ? new RestHttpClient($this)
            : new SoapHttpClient($this);
    }

    /**
     * Magically handle calls to certain methods and properties.
     *
     * @param  string  $method
     * @param  array  $params
     *
     * @return mixed
     */
    public function __call(string $method, array $params)
    {
        if (array_key_exists($method, $services = $this->services)) {
            return new $services[$method]($this->httpClient($method));
        }
    }
}
