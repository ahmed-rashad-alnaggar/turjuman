# Turjuman - Laravel Route Localization Package

Turjuman is a Laravel route localization package designed to simplify the process of generating localized routes effortlessly. The name "turjuman" is derived from the Arabic word **تُرجُمان**, meaning the person who translates or localizes. This term holds historical significance as it was the name of one of  [alsahaba](https://en.wikipedia.org/wiki/Companions_of_the_Prophet) (companions) of the [Prophet Muhammad](https://en.wikipedia.org/wiki/Muhammad) may Allah bless him and grant him peace, Abdullah bin Abbas. He earned the title of "turjuman" (the interpreter) of the holy [Qur’an](https://en.wikipedia.org/wiki/Quran) due to his renowned skills in interpreting the holy Qur’an, despite being one of the youngest companions.

## Table of Contents

- [Installation](#installation)
- [Configuration](#configuration)
- [Middlewares](#middlewares)
- [Features](#features)
- [Usage](#usage)
- [Contributing](#contributing)
- [License](#license)

## Installation

To install Turjuman, you can use Composer:

```bash
composer require alnaggar/turjuman
```

Next, publish the configuration file:

```bash
php artisan vendor:publish --provider="Alnaggar/Turjuman/TurjumanServiceProvider"
```

Then, register these middlewares in your `App\Http\Kernel` before the `\Illuminate\Routing\Middleware\SubstituteBindings` middleware

```php
 'web' => [
    // ...

    \Alnaggar\Turjuman\Middleware\SetLocale::class,
    \Alnaggar\Turjuman\Middleware\LocalizeRoutes::class,
    \Illuminate\Routing\Middleware\SubstituteBindings::class,
],
```

## Configuration

The Configuration file (`config/turjuman.php`) provides flexibility in defining supported locales, default locale, display options, hiding the default locale, and route aliases.

### Supported Locales

The `supported_locales` key in allows you to specify the locales you want to support. With a vast array of +800 locales available, you can uncomment or add new ones based on your project needs. Each locale can be customized with additional properties such as currency, country, etc., making them accessible through the corresponding `Locale` instance.

### Default Locale

The `default_locale` key sets the code for the default locale. Turjuman will fallback to this locale when no locale is detected.

### Display Locale in URL

The `display_location` key controls how the locale is displayed in the URL. It can be a positive integer, indicating the segment to display, a string specifying the query name for display, or null to hide the locale from the URL while setting it internally.

### Hide Default Locale

The `hide_default` key, when set to true, determines whether to display the default locale in the URL if it's displayable.

### Routes Aliases

The `routes_aliases` key allows you to translate specific URLs in different locales. For instance, you can provide translations for URLs by adding them under the corresponding locale keys.

## Middlewares

### `SetLocale.php` Middleware

This middleware is responsible for setting the current locale based on various sources in a predefined order, such as request input, URL, session, cookies, browser languages, and user preferences to determine the current locale.

This middleware includes a function `fetchUserLocale(): ?string` that you can override. This function allows you to retrieve the user's preferred locale from the database or any other source if it is not determined in the predefined sources.

### `LocalizeRoutes.php` Middleware

This middleware is responsible for handling localization of routes based on the current locale settings. It checks if the request is a GET request and if current route is localized. It ensures that the URL matches the localized URL, and if not, it redirects to the correct URL.

## Features

### Route Localization

Easily create localized routes for different languages or regions by utilizing the group function.

```php
//  routes/web.php
use Alnaggar\Turjuman\Turjuman;

Turjuman::group(function(){
    Route::get('/', function(){
        return view('welcome');
    });

    Route::get('/show/{id}', ['\App\Http\Controllers\UserController::class', 'index']);

    Route::post('/edit/{id}', ['\App\Http\Controllers\UserController::class', 'update']);

    // ...
});
```

### URL Generation

Generate localized URLs based on configured locales and group attributes.

```php
use Alnaggar\Turjuman\Turjuman;

Turjuman::group(function () {
    // Define your localized routes here
}, [
    'routes_aliases' => [
        'fr' => [
            '/products' => '/produits'
        ]
    ]
]);
```

```php
use Alnaggar\Turjuman\Turjuman;

$url = Turjuman::getLocalizedUrl('/products', 'fr');
// Returns the URL for the 'fr' locale: '/fr/produits'
```

### Group Attributes

Define custom attributes for route groups to control display type, default locale, and more.

```php
use Alnaggar\Turjuman\Turjuman;

Turjuman::group(function () {
    // Define your localized routes here
}, [
    'supported_locales' => [...], // Specify supported locales
    'default_locale' => 'en', // Set the default locale
    // Add more group attributes as needed
]);
```

### Extensible and Macroable

Extend the functionality of Turjuman by adding macros and mixins.

```php
use Alnaggar\Turjuman\Turjuman;

// Adding Macros
Turjuman::macro('customMethod', function () {
    // Your custom logic here
});

/// Adding Mixins
Turjuman::mixin(new MyCustomMixin());
```

`Alnaggar\Turjuman\Locale` and `Alnaggar\Turjuman\GroupAttributes` are utilizing `Alnaggar\Turjuman\Traits\Extensible` triat, making them macroable and may have additional properties, this properties may be accessed by array-access, property-access, or get-method-access.

```php
// config/turjuman.php

'supported_locales' => [
    'ar_DZ' => ['name' => 'Arabic (Algeria)', 'native' => 'العربية (الجزائر)', 'script' => 'Arab', 'currency' => 'DZD'],
    'ar_EG' => ['name' => 'Arabic (Egypt)', 'native' => 'العربية (مصر)', 'script' => 'Arab', 'currency' => 'EGP']
]

// ... other configurations
```

```php
// routes/web.php

use Alnaggar\Turjuman\Turjuman;

Turjuman::group(function () {
    // Define your localized routes here
}, [
    'continent' => 'Africa',
    'common_language' => 'Arabic'
]);
```

```php
// YourClass.php

use Alnaggar\Turjuman\Turjuman;

public function getDetails()
{
    $currency = Turjuman::getCurrentLocale()['currency'];
    $continent = Turjuman::getCurrentAttributes()->continent;
    $commonLanguage = Turjuman::getCurrentAttributes()->getCommonLanguage();

    // ...
}
```

> [!NOTE]
> The custom properties must be named using snake_case.

### Redirecting Only When Needed

The redirection mechanism is selectively applied, exclusively triggering when a GET request is encountered. For other types of requests, the locale is internally set without initiating any redirection.

### Standard Route Caching

Feel free to employ any of Laravel's standard route caching commands as usual.

## Usage

### Route Grouping

#### `group(\Closure $routes, array $attributes = []): \Alnaggar\Turjuman\Group`

The `group` function is a powerful feature allowing you to group routes for localization. It takes a closure containing the routes to localize and optional attributes. You can add custom attributes overriding the conifg ones, making it ideal for multi-tenancy applications with different locales.

```php
use Alnaggar\Turjuman\Facades\Turjuman;

Turjuman::group(function () {
    // Define your localized routes here
}, [
    'default_locale' => 'en',
    'display_location' => 'lang',
    // More custom attributes...
]);
```

#### `ignore(\Closure|\Illuminate\Routing\Route $routes): void`

Use the `ignore` function within the `group` closure to exclude specific routes from localization. It takes a closure or a single route to ignore.

```php
use Alnaggar\Turjuman\Turjuman;

Turjuman::group(function () {
    // Define your localized routes here

    Turjuman::ignore(function() {
        // Define your non-localized routes here
    });
});
```

### Configuration Management

#### `addConfigLocales(array $locales): \Alnaggar\Turjuman\Localizer`

Add additional locales to the supported locales in the configuration.

Example:

```php
// App/Providers/AppServiceProvider.php

use \Alnaggar\Turjuman\Locale;
use \Alnaggar\Turjuman\Turjuman;

$locales = [
    new Locale(['code' => 'fr', 'name' => 'French', 'native' => 'français', 'script' => 'Latn']),
    new Locale(['code' => 'es', 'name' => 'Spanish', 'native' => 'español', 'script' => 'Latn']),
];

Turjuman::addConfigLocales($locales);
```

#### `setConfigLocales(array $locales): \Alnaggar\Turjuman\Localizer`

Set the supported locales in the configuration.

Example:

```php
// App/Providers/AppServiceProvider.php

use \Alnaggar\Turjuman\Locale;
use \Alnaggar\Turjuman\Turjuman;

$locales = [
    // Define your locales.
];

Turjuman::setConfigLocales($locales);
```

#### `setConfigDefaultLocale(string $locale): \Alnaggar\Turjuman\Localizer`

Set the default locale in the configuration.

Example:

```php
// App/Providers/AppServiceProvider.php

use \Alnaggar\Turjuman\Turjuman;

Turjuman::setConfigDefaultLocale('fr');
```

#### `getConfigAttributes(): \Alnaggar\Turjuman\GroupAttributes`

Retrieve the configuration attributes.

Example:

```php
use \Alnaggar\Turjuman\Turjuman;

$configAttributes = Turjuman::getConfigAttributes();
```

### Group Information

#### `getCurrentGroup(): ?\Alnaggar\Turjuman\Group`

Retrieve the `Alnaggar\Turjuman\Group` instance responsible for localizing the current route. Returns `null` if not found.

Example:

```php
use \Alnaggar\Turjuman\Turjuman;

$currentGroup = Turjuman::getCurrentGroup();
```

#### `getCurrentAttributes(): \Alnaggar\Turjuman\GroupAttributes`

Retrieve the current group attributes.

Example:

```php
use \Alnaggar\Turjuman\Turjuman;

$currentAttributes = Turjuman::getCurrentAttributes();
```

### Locale Management

#### `setCurrentLocale(string $locale): \Alnaggar\Turjuman\Locale`

Set the current locale.

Example:

```php
use \Alnaggar\Turjuman\Turjuman;

Turjuman::setCurrentLocale('es');
```

#### `getLocale(string $locale): ?\Alnaggar\Turjuman\Locale`

Retrieve an `Alnaggar\Turjuman\Locale` instance based on its code. Returns `null` if the locale is not supported.

Example:

```php
use \Alnaggar\Turjuman\Turjuman;

$arabicLocale = Turjuman::getLocale('ar');
```

#### `getSupportedLocales(): array`

Retrieve the current group's supported locales.

Example:

```php
use \Alnaggar\Turjuman\Turjuman;

$supportedLocales = Turjuman::getSupportedLocales();
```

#### `getDefaultLocale(): \Alnaggar\Turjuman\Locale`

Retrieve the current group's default locale instance.

Example:

```php
use \Alnaggar\Turjuman\Turjuman;

$defaultLocale = Turjuman::getDefaultLocale();
```

#### `getCurrentLocale(): ?\Alnaggar\Turjuman\Locale`

Retrieve the current locale instace. Returns `null` if the current route is not localized.

Example:

```php
use \Alnaggar\Turjuman\Turjuman;

$currentLocale = Turjuman::getCurrentLocale();
```

### URL Localization

#### `getLocalizedUrl(string $url, string $locale = null): ?string`

Get the localized URL for the provided URL and locale. Returns `null` if the provided URL is external or not localized.

Example:

```php
use \Alnaggar\Turjuman\Turjuman;

$spanishUrl = Turjuman::getLocalizedUrl('/about', 'es');
```

#### `getNonLocalizedUrl(string $url): ?string`

Get the non-localized URL for the provided URL. Returns `null` if the provided URL is external.

Example:

```php
use \Alnaggar\Turjuman\Turjuman;

$originalUrl = Turjuman::getNonLocalizedUrl('/acerca-de');
```

### Localization Validation

#### `isLocalizedUrl(string $url, string $method = 'GET'): bool`

Check if the given URL is localized in any of the registered groups.

Example:

```php
use \Alnaggar\Turjuman\Turjuman;

$isLocalizedUrl = Turjuman::isLocalizedUrl('/contact');
```

#### `isLocalizedRoute(string|\Illuminate\Routing\Route $route = null): bool`

Check if the provided route is localized within any of the registered groups. The check is done against the current route if `null` is passed.

Example:

```php
use \Alnaggar\Turjuman\Turjuman;

$isLocalizedUrl = Turjuman::isLocalizedRoute('main.contact');
```

### Locale Validation

#### `isSupportedLocale(string $locale): bool`

Check if the provided locale is supported within the current group.

Example:

```php
use \Alnaggar\Turjuman\Turjuman;

$isSupported = Turjuman::isSupportedLocale('es');
```

#### `isCurrentLocale(string $locale): bool`

Check if the provided locale is the current locale.

Example:

```php
use \Alnaggar\Turjuman\Turjuman;

$isCurrent = Turjuman::isCurrentLocale('fr');
```

#### `isDefaultLocale(string $locale): bool`

Check if the provided locale is the default locale.

Example:

```php
use \Alnaggar\Turjuman\Turjuman;

$isDefault = Turjuman::isDefaultLocale('en');
```

### Helpers

#### `getLocalesByProperty(string $property, mixed $default = null): string`

Retrieve an associative array of locale codes and their corresponding property values based on the current group's supported locales.

The `$default` optional parameter specifies the value to be returned if the property does not exist for a certain locale instance. If a closure is passed, the locale instance will be provided as a parameter.
This function is useful for obtaining specific information or settings for each supported locale within the currently defined group. It provides flexibility by allowing a default value or behavior to be specified in case the requested property is not available for a particular locale.

Example:

```php
use \Alnaggar\Turjuman\Turjuman;

$localesCurrencies = Turjuman::getLocalesByProperty('currency', 'USD');
```

#### `getLocalizedPagePath(string $path, string $locale = null): string`

Get the localized page path for the provided path and locale. It works for Laravel views and [Inertia](https://inertiajs.com/) pages.

Example:

```php
use \Alnaggar\Turjuman\Turjuman;

$localizedPath = Turjuman::getLocalizedPagePath('contact', 'fr');
```

> [!NOTE]
> Before using functions that rely on the current group or current attributes, it is essential to include an `if` statement to check whether the current route is localized.

### Helper Function

The `turjuman` helper function can be used to localize URLs:

```php
// Get the localized URL for a given path and locale
$url = turjuman('/about', 'ar');
```

## Contributing

If you find any issues or have suggestions for improvements, feel free to open an issue or submit a pull request on the GitHub repository.

## License

Turjuman is open-sourced software licensed under the [MIT license](LICENSE).
