<?php
/** @noinspection PhpIllegalPsrClassPathInspection */
namespace Austomos\OAuth2\Client\Provider;

use League\OAuth2\Client\Provider\ResourceOwnerInterface;
use League\OAuth2\Client\Tool\ArrayAccessorTrait;

class ChasterAppResourceOwner implements ResourceOwnerInterface
{
    use ArrayAccessorTrait;

    protected array $response;

    public function __construct(array $response = [])
    {
        $this->response = $response;
    }

    public function getId(): string
    {
        return $this->getValueByKey($this->response, '_id');
    }

    public function toArray()
    {
        return $this->response;
    }
}