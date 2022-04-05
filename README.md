# BigBite PHPCS Configuration

## Installation

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
	<rule ref="BigBite" />
</ruleset>
```

## Developing

Clone this repository, and then run `composer install && composer install-cs`.  
Please run the following command prior to creating a PR, and ensure that there are no errors:
- `composer run all-checks`

If you're feeling especially nice, you can run this command instead:
- `composer run all-checks-strict`
