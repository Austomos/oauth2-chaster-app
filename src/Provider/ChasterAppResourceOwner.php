<?php
/** @noinspection PhpIllegalPsrClassPathInspection */
namespace Austomos\OAuth2\Client\Provider;

use League\OAuth2\Client\Provider\ResourceOwnerInterface;
use League\OAuth2\Client\Tool\ArrayAccessorTrait;

/**
 * Data of auth/profile endpoint API request
 * @link https://api.chaster.app/api/#/Profile/AuthMeController_me
 */
class ChasterAppResourceOwner implements ResourceOwnerInterface
{
    use ArrayAccessorTrait;

    /**
     * Data of the user profile
     * @var array
     */
    protected array $response;

    public function __construct(array $response = [])
    {
        $this->response = $response;
    }

    /**
     * Provide data using array key of profile data
     * @param string $name
     *
     * @return mixed
     */
    public function __get(string $name): mixed
    {
        return $this->getValueByKey($this->response, $name);
    }

    /**
     * Get profile _id
     * @return string
     */
    public function getId(): string
    {
        return $this->getValueByKey($this->response, '_id', '');
    }

    /**
     * Return profile array
     * @return array
     */
    public function toArray(): array
    {
        return $this->response;
    }
}