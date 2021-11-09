# BigBite PHPCS Configuration

## Installation

Add the following to your `composer.json`:

```json
{
	"require-dev": {
		"bigbite/phpcs-config": "dev-main"
	},
	"repositories": [
		{
			"type": "vcs",
			"url": "git@github.com:bigbite/phpcs-config.git"
		}
	]
}
```
Then run
```bash
$ composer update
```

Create a `.phpcs.xml.dist` file in your project and add the following, replacing {PROJECT} with your project name:

```xml
<?xml version="1.0"?>
<ruleset name="{PROJECT} Rules">
	<rule ref="vendor/bigbite/phpcs-config" />
</ruleset>
```
