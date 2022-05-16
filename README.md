# Chaster App Provider for OAuth 2.0 Client
[![Latest Version](https://img.shields.io/github/release/austomos/oauth2-chaster-app.svg?style=flat-square)](https://github.com/austomos/oauth2-chaster-app/releases)
[![Software License](https://img.shields.io/badge/license-MIT-brightgreen.svg?style=flat-square)](LICENSE.md)

This package provides Github OAuth 2.0 support for the PHP League's [OAuth 2.0 Client](https://github.com/thephpleague/oauth2-client).

## Installation

To install, use composer:

```
composer require austomos/oauth2-chaster-app
```

## Usage

Usage is the same as The League's OAuth client, using `\Austomos\OAuth2\Client\Provider\ChasterApp` as the provider.

### Authorization Code Flow

```php
$provider = new League\OAuth2\Client\Provider\ChasterApp([
    'clientId'          => '{chaster-client-id}',
    'clientSecret'      => '{chaster-client-secret}',
    'redirectUri'       => 'https://example.com/callback-url',
]);

if (!isset($_GET['code'])) {

    // If we don't have an authorization code then get one
    $authUrl = $provider->getAuthorizationUrl();
    $_SESSION['oauth2state'] = $provider->getState();
    header('Location: '.$authUrl);
    exit;

// Check given state against previously stored one to mitigate CSRF attack
} elseif (empty($_GET['state']) || ($_GET['state'] !== $_SESSION['oauth2state'])) {

    unset($_SESSION['oauth2state']);
    exit('Invalid state');

} else {

    // Try to get an access token (using the authorization code grant)
    $token = $provider->getAccessToken('authorization_code', [
        'code' => $_GET['code']
    ]);

    // Optional: Now you have a token you can look up a users profile data
    try {

        // We got an access token, let's now get the user's details
        $user = $provider->getResourceOwner($token);

        // Use these details to create a new profile
        printf('Hello %s!', $user->getUsername());

    } catch (Exception $e) {

        // Failed to get user details
        exit('Oh dear...');
    }

    // Use this to interact with an API on the users behalf
    echo $token->getToken();
}
```

### Managing Scopes

When creating your Chaster authorization URL, you can specify scopes your application may authorize.

```php
$options = [
    'scope' => ['profile', 'locks', '...'] // array or string
];

$authorizationUrl = $provider->getAuthorizationUrl($options);
```
If neither are defined, the provider will utilize internal defaults.

At the time of authoring this documentation, the [following scopes are available](https://docs.chaster.app/api-scopes).

| Scope              | Description                    |
|--------------------|--------------------------------|
| ```profile```      | Access the username and email  |
| ```locks```        | View and edit locks            |
| ```shared_locks``` | View and manage shared locks   |
| ```keyholder```    | View and manage locked users   |
| ```messaging```    | View and send private messages |

## Testing

``` bash
$ ./vendor/bin/phpunit
```

## Contributing

Please see [CONTRIBUTING](https://github.com/austomos/oauth2-chaster-app/blob/main/CONTRIBUTING.md) for details.


## Credits

- [Ben Hyr](https://github.com/austomos)
- [All Contributors](https://github.com/austomos/oauth2-chaster-app/contributors)


## License

The MIT License (MIT). Please see [License File](https://github.com/austomos/oauth2-chaster-app/blob/main/LICENSE) for more information.