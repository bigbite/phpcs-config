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
	<rule ref="vendor/bigbite/phpcs-config/BigBite" />
</ruleset>
```

### Continuous Integration

In order to pull the library in via composer in a CI environment, there are a few steps you'll need to take:  
1. give read access to the CI GitHub user in your project repo (or write access if you need to push back to the repo).  
2. as the CI GitHub user, generate a new personal access token [here](https://github.com/settings/tokens/new).  
3. provide that token to your CI environment.  
4. add this command to your CI build procedure `composer config github-oauth.github.com "$GH_TOKEN"`, where `$GH_TOKEN` is the environment variable.  

**NB**: step #4 assumes that your CI environment will obfuscate the output of commands that use secure environment variables (e.g. Travis CI).  
If your CI tool does not support this, then you may need to provide composer with an `auth.json` file, or other alternative means as listed [here](https://getcomposer.org/doc/articles/authentication-for-private-packages.md).
