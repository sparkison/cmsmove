![version](https://img.shields.io/github/tag/strongloop/express.svg)

# cmsmove
Automated deployment for popular content management systems

## Requirements
- [Composer](https://getcomposer.org/)

## Installation instructions
1. Download the latest `cmsmove.phar` release from the [releases tab](https://github.com/sparkison/cmsmove/releases)
2. Copy the file to your composer **bin** folder
    1. `mv cmsmove.phar ~/.composer/vendor/bin/cmsmove`
2. Make sure it's executable
    1. `chmod +x ~/.composer/vendor/bin/cmsmove`
3. Add the **composer** bin folder to your PATH variables (if not already done)
    1. `sudo nano ~/.bash_profile`
    2. Add the following line: `export PATH="~/.composer/vendor/bin:$PATH"`
4. Confirm you can execute the **cmsmove** command
    1. `cmsmove --version`
    
That's it! You're ready to go

## Next steps
Add to official Composer library to make installation easier: e.g. `composer global require "sparkison/cmsmove=dev-master"`