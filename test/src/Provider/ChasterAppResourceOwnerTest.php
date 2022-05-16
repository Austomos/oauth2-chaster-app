<?php

namespace Austomos\OAuth2\Client\Test\Provider;

use Austomos\OAuth2\Client\Provider\ChasterAppResourceOwner;
use PHPUnit\Framework\TestCase;

class ChasterAppResourceOwnerTest extends TestCase
{
    /** @noinspection PhpUndefinedFieldInspection */
    public function testNamedFieldWithValidData(): void
    {
        $profile = new ChasterAppResourceOwner([
            '_id' => 'mock_id',
            'username' => 'mock_username',
            'email' => 'mock_email',
            'mock' => 'mock_value'
        ]);
        $this->assertEquals('mock_id', $profile->getId());
        $this->assertEquals('mock_id', $profile->toArray()['_id']);
        $this->assertEquals('mock_id', $profile->_id);

        $this->assertEquals('mock_username', $profile->getUsername());
        $this->assertEquals('mock_username', $profile->toArray()['username']);
        $this->assertEquals('mock_username', $profile->username);

        $this->assertEquals('mock_email', $profile->getEmail());
        $this->assertEquals('mock_email', $profile->toArray()['email']);
        $this->assertEquals('mock_email', $profile->email);

        $this->assertEquals('mock_value', $profile->toArray()['mock']);
        $this->assertEquals('mock_value', $profile->mock);
        $this->assertEquals(null, $profile->invalidMock);
    }

    /** @noinspection PhpUndefinedFieldInspection */
    public function testNamedFieldWithInvalidData(): void
    {
        $profile = new ChasterAppResourceOwner([]);
        $this->assertEquals('', $profile->getId());
        $this->assertArrayNotHasKey('_id', $profile->toArray());
        $this->assertEquals(null, $profile->_id);

        $this->assertEquals('', $profile->getUsername());
        $this->assertArrayNotHasKey('username', $profile->toArray());
        $this->assertEquals(null, $profile->username);

        $this->assertEquals('', $profile->getEmail());
        $this->assertArrayNotHasKey('email', $profile->toArray());
        $this->assertEquals(null, $profile->email);

        $this->assertArrayNotHasKey('mock', $profile->toArray());
        $this->assertEquals(null, $profile->mock);
    }
}
