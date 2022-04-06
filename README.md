# laravel-validation-rules
[![Tests](https://github.com/vlauciani/laravel-validation-rules/actions/workflows/phpunit.yml/badge.svg)](https://github.com/vlauciani/laravel-validation-rules/actions)
[![Packagist License](https://poser.pugx.org/vlauciani/laravel-validation-rules/license.png)](http://choosealicense.com/licenses/mit/)
[![Total Downloads](https://poser.pugx.org/vlauciani/laravel-validation-rules/d/total.png)](https://packagist.org/packages/vlauciani/laravel-validation-rules)

## Installation
```
composer require vlauciani/laravel-validation-rules:^1.0.0
```

## Available Rules

### RFC3339Extended
PHP doesn't validate correctly RFC3339: https://github.com/laravel/framework/issues/35387
* for example `2020-12-21T23:59:59+00:00` or `2020-12-21T23:59:59Z` return `false` but it is `true`.

this *Rule* uniform the date in format: `YYYY-MM-DDThh:mm:ss.mmm+nn:nn`
         
## Usage
```
<?php
namespace App\Api\Controllers;
use App\Http\Controllers\Controller;
use VLauciani\LaravelValidationRules\Rules\RFC3339ExtendedRule;

class MyController extends Controller
{
    public function index()
    {
        $myData = ['starttime' => '2022-04-06T12:00:00.123+00:00']
        Validator::make($myData, [
            'starttime'           => [new RFC3339ExtendedRule()],
        ])->validate();
    }
}
```

## Contribute
Thanks to your contributions!

Here is a list of users who already contributed to this repository:
<a href="https://github.com/vlauciani/laravel-validation-rules/graphs/contributors">
  <img src="https://contrib.rocks/image?repo=vlauciani/laravel-validation-rules" />
</a>

## Author
(c) 2022 Valentino Lauciani vlauciani[at]gmail.com
