# BigBite PHPCS Configuration

## Installation

Install the library via Composer:

```bash
$ composer require --dev bigbite/phpcs-config:dev-master
```

Create a `.phpcs.xml.dist` file in your project and add the following, replacing {PROJECT} with your project name:

```xml
<?xml version="1.0"?>
<ruleset name="{PROJECT} Rules">
	<rule ref="vendor/bigbite/phpcs-config" />
</ruleset>
```
