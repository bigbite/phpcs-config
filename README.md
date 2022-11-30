# BigBite PHPCS Configuration

This package contains the PHPCS configuration [Big Bite](https://bigbite.net) use for all projects.
It is primarily based upon [WPCS](https://github.com/WordPress/WordPress-Coding-Standards) and [VIP WPCS](https://github.com/Automattic/VIP-Coding-Standards), but is more strict, and contains additional Sniffs not included by those standards.


## Installation

Currently, this standard is only compatible with PHP version 8.0; due to limitations in the project's dependencies.

Run the following command in terminal:
```bash
composer require --dev bigbite/phpcs-config
```

Then run:
```bash
$ composer update
```

Create a `.phpcs.xml.dist` file in your project and add the following, replacing {PROJECT} with your project name:

```xml
<?xml version="1.0"?>
<ruleset name="{PROJECT} Rules">
	<rule ref="./vendor/bigbite/phpcs-config/BigBite" />
</ruleset>
```

## Developing

Please note that the PHPUnit test suite is not yet compatible with PHP 8.*.

Clone this repository, and then run `composer install && composer install-cs`.  
Please run the following command prior to creating a PR, and ensure that there are no errors:
- `composer run all-checks`

If you're feeling especially nice, you can run this command instead:
- `composer run all-checks-strict`
