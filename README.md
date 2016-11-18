# cmsmove
Automated deployment for popular content management systems

## Requirements
- [Composer](https://getcomposer.org/)

## Installation instructions

1. Ensure that the `~/.composer/vendor/bin` is available in your terminal PATH.

	1. `sudo nano ~/.bash_profile`
    2. Make sure you have a line similar to the following: `export PATH="~/.composer/vendor/bin:$PATH"`

2. Require `cmsmove` globally by using this command:

	`composer global require "sparkison/cmsmove"`

3. Change to a project directory, such as `~/Sites/mysite.dev`, and run `cmsmove config <cms>` where `<cms>` is one of the configured CMS for cmsmove<sup>*</sup>

<sup>*</sup> _currently have ee3 and craft as available pre-configured CMS for cmsmove_

Further instructions to come...

### Contributing

Pull requests, contributions, issues and feature requests are always welcome... Although I would prefer a pull request for new features... ;)
    