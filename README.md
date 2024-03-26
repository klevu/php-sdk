# klevu/php-sdk

The Klevu PHP-SDK is a small package designed to simplify communicating with Klevu API services.

## Getting Started

1. **[Create an account with Klevu](https://box.klevu.com/merchant/signup)**.  
   Free trial, with full access to all features is available.
2. Retrieve your **JS API Key** and **REST AUTH Key** from Store Settings > Store Info in your account.
3. **Install the SDK** in your application using composer.  
```
composer require klevu/php-sdk
```
4. **Start building!**  
   There are some quick-start examples below, as well as detailed guides in our [developer documentation](https://docs.klevu.com/php-sdk).

## System Requirements

To use this library, you must be running **PHP 8.1** compiled with the `libxml` and `simplexml` extensions. 
See the [PHP docs](https://www.php.net/manual/en/simplexml.installation.php) for more information.

You will also require a [PSR-18 compatible](https://www.php-fig.org/psr/psr-18/) HTTP client, such as `guzzlehttp/guzzle`,
which provides `psr/http-client-implementation` support. 
A [list of compatible libraries can be found on Packagist](https://packagist.org/providers/psr/http-client-implementation).

We also recommend a [PSR-3 compatible](https://www.php-fig.org/psr/psr-3/) logger library, such as `monolog/monolog`.
This will allow the SDK to write activity it performs to a location of your choice.

## Quick Start Guide

### Account Credentials

The [AccountCredentials](src/Model/AccountCredentials.php) object is required by all services connecting with Klevu.  
You will need both your JS API Key (in the format `klevu-xxxxxxx`) and your REST AUTH Key from your Klevu account.

> When saving and accessing these credentials within your application, please treat the REST AUTH Key in the same
> manner as any other sensitive information, such as passwords.  
> If you need to change your REST AUTH Key, please [contact our support team](https://help.klevu.com/support/tickets/new).

Create a new `Klevu\PhpSDK\Model\AccountCredentials` object. Note, account credentials objects are immutable.

```php
<?php

declare(strict_types=1);

// Include the composer autoloader - you may need to change the directory path
require_once 'vendor/autoload.php';

use Klevu\PhpSDK\Model\AccountCredentials;

$accountCredentials = new AccountCredentials(
    jsApiKey: '[Your JS API Key]',
    restAuthKey: '[Your REST AUTH KEY]',
);
```

### Retrieving Account Details

> _You can find a more complete example of retrieving account information in [the examples section](examples/account/lookup-account.php)_

The [AccountLookupService](src/Api/Service/Account/AccountLookupServiceInterface.php) lets you retrieve details about
your Klevu account, including hostnames required to push and pull data to other services.

```php
<?php

declare(strict_types=1);

// Include the composer autoloader - you may need to change the directory path
require_once 'vendor/autoload.php';

use Klevu\PhpSDK\Model\AccountCredentials;
use Klevu\PhpSDK\Service\Account\AccountLookupService;

$accountLookupService = new AccountLookupService();
$accountCredentials = new AccountCredentials(
    jsApiKey: '[Your JS API Key]',
    restAuthKey: '[Your REST AUTH KEY]',
);

$account = $accountLookupService->execute($accountCredentials);

var_export($account);
```

Which will return an object as follows.

```text
Klevu\PhpSDK\Model\Account::__set_state(array(
   'jsApiKey' => 'klevu-1234567890',
   'restAuthKey' => 'ABCDE1234567890',
   'platform' => 'custom',
   'isActive' => true,
   'companyName' => 'Klevu',
   'email' => 'contact@klevu.com',
   'indexingUrl' => 'indexing.ksearchnet.com',
   'searchUrl' => 'cs.ksearchnet.com',
   'smartCategoryMerchandisingUrl' => 'cn.ksearchnet.com',
   'analyticsUrl' => 'stats.klevu.com',
   'jsUrl' => 'js.klevu.com',
   'tiersUrl' => 'tiers.klevu.com',
   'accountFeatures' => NULL,
))
```

Each property listed is private and accessed through getters, as follows
```php
$account->getJsApiKey();
```

> Note, in the example above we instantiate the service implementation rather than the interface.  
> If you are using dependency injection, we recommend configuring the `Klevu\PhpSDK\Api\Interface\Service\Account\AccountLookupService`
> interface to `Klevu\PhpSDK\Service\Account\AccountLookupService`, along with any constructor arguments.  
> For example:

```php
$accountLookupService = $container->get(\Klevu\PhpSDK\Api\Service\Account\AccountLookupServiceInterface::class);
```

### Retrieving Account Features

In the example above, the returned model's `accountFeatures` property is empty. In order to load your account's
enabled features, you will need to invoke the
[AccountFeaturesService](src/Api/Service/Account/AccountFeaturesServiceInterface.php), as shown.

```php
<?php

declare(strict_types=1);

// Include the composer autoloader - you may need to change the directory path
require_once 'vendor/autoload.php';

use Klevu\PhpSDK\Model\AccountCredentials;
use Klevu\PhpSDK\Service\Account\AccountFeaturesService;

$accountFeaturesService = new AccountFeaturesService();
$accountCredentials = new AccountCredentials(
    jsApiKey: '[Your JS API Key]',
    restAuthKey: '[Your REST AUTH KEY]',
);

$accountFeatures = $accountFeaturesService->execute($accountCredentials);

var_export($account);
```

Which will return an immutable object as follows.

```text
Klevu\PhpSDK\Model\Account\AccountFeatures::__set_state(array(
   'smartCategoryMerchandising' => true,
   'smartRecommendations' => true,
   'preserveLayout' => true,
))
```

Each property is public and can be accessed directly, but not modified.

You can also attach this to your loaded account model by setting the `accountFeatures` property. This is not required
but you may find it useful to have all data in a single model within your application.

```php
$account->setAccountFeatures($accountFeatures);

var_export($account);
```

```text
Klevu\PhpSDK\Model\Account::__set_state(array(
   'jsApiKey' => 'klevu-1234567890',
   'restAuthKey' => 'ABCDE1234567890',
   'platform' => 'custom',
   'isActive' => true,
   'companyName' => 'Klevu',
   'email' => 'contact@klevu.com',
   'indexingUrl' => 'indexing.ksearchnet.com',
   'searchUrl' => 'cs.ksearchnet.com',
   'smartCategoryMerchandisingUrl' => 'cn.ksearchnet.com',
   'analyticsUrl' => 'stats.klevu.com',
   'jsUrl' => 'js.klevu.com',
   'tiersUrl' => 'tiers.klevu.com',
   'accountFeatures' => Klevu\PhpSDK\Model\Account\AccountFeatures::__set_state(array(
       'smartCategoryMerchandising' => true,
       'smartRecommendations' => true,
       'preserveLayout' => true,
    )),
))
```

> Note: as with the previous example, we instantiated the implementation rather than the interface in our example.
> Again, we recommend mapping with dependency injection if your application supports this.
 


















