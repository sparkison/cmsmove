# cmsmove
Automated deployment for popular content management systems

Currently supported systems:
- [Craft CMS](https://craftcms.com/)
- [ExpressionEngine (1 and 2)](http://expressionengine.com/)

Planned systems (not yet implemented):
- [WordPress](https://wordpress.org/)

## Requirements
- [Composer](https://getcomposer.org/) (_optional if you opt to use the pre-compiled `phar` file in the [releases](https://github.com/sparkison/cmsmove/releases) tab_)

## Installation instructions

1. Ensure that the `~/.composer/vendor/bin` is available in your terminal PATH.

	1. `sudo nano ~/.bash_profile`
    2. Make sure you have a line similar to the following: `export PATH="~/.composer/vendor/bin:$PATH"`

2. Require `cmsmove` globally by using this command:

	`composer global require sparkison/cmsmove`

3. Change to a project directory, such as `~/Sites/mysite.dev`, and run `cmsmove config <framework>` where `<framework>` is one of the configured CMS for cmsmove<sup>*</sup>

<sup>*</sup>See _Usage_ below for more information

## Usage

#### Getting started
After installing either the `phar` file or using the global `composer` install, ensure you can issue the `cmsmove` from the command line.

To use **cmsmove** you must first add a config file and an ignore file. To bootstrap these items, `cd` into your projects root working directory and issue:
- `cmsmove config <framework>` where `<framework>` is one of the supported CMS systems
    - Currently support options for `<framework>` are `craft`, `ee2` and `ee3`

This will generate the required `moveConfig.json` and `rsync.ignore` files. The `moveConfig.json` file will contain all the needed variables for pushing and pulling. The `rsync.ignore` file will contain a list of files/folders to ignore. Add items to this list as needed.

#### Configure moveConfig.json
This is a `JSON` file use to get the needed variables for accessing the local installation and database as well the configured remote hosts

Example config file:
```json 
{
  "type": "craft",
  "mappings": {
    "app": "craft",
    "www": "public",
    "config": "config",
    "plugins": "plugins",
    "templates": "templates",
    "custom": {
      "uploads": {
        "type": "public",
        "directory": "uploads"
      },
      "assets": {
        "type": "public",
        "directory": "dist"
      }
    }
  },
  "environments": {
    "local": {
      "root": "/Users/MY_USER_NAME/Sites/mysite.dev",
      "db": "local_db_dev",
      "dbHost": "localhost",
      "dbUser": "root",
      "dbPass": "root",
      "dbPort": "3306"
    },
    "staging": {
      "host": "REMOTE HOST OR IP",
      "root" : "/home/user",
      "public": "public_html",
      "user": "SSH USER NAME",
      "password": "SSH PASSWORD",
      "keyfile": "SSH KEY FILE (takes precedence over the password field)",
      "port": "22",
      "db": "REMOTE DATABASE NAME",
      "dbHost": "localhost",
      "dbUser": "REMOTE DATABASE USER",
      "dbPass": "REMOTE DATABASE PASSWORD",
      "dbPort": "3306"
    },
    "production": {
      "host": "",
      "root" : "/home/mysite",
      "public": "public_html",
      "user": "",
      "password": "",
      "keyfile": "",
      "port": "22",
      "db": "",
      "dbHost": "localhost",
      "dbUser": "",
      "dbPass": "",
      "dbPort": "3306"
    }
  }
}
```

##### Configuration notes:

**Required:** The following fields are required:
- `type` tells **cmsmove** what framework is being used.
- `mappings` contains information for the local setup; this section is typically framework specific
    - `app` the application main folder
    - `www` the public folder
    - The rest of the items in this list are framework specific
- `environments` the various environments (e.g. local, staging, production)
    - `local` this is the only required environment for accessing the local database, all other environments are optional, and can be named whatever you like

**App not above root:** If the app and public folder are one in the same simply set `mappings.www` as an empty string, and set `mappings.app` as the public folder.
Additionally, set the `environments.<your_environment>.root` to your remote host public folder and leave `environments.<your_environment>.public` as an empty string.
 
**Adding custom directories for syncing:**
Within the `mappings.custom` array of the config file add a `key => array` mapping for your custom directory.
The custom directory must contain two key/value pairs: `type` and `directory`.

Issuing `cmsmove push staging custom` will present you with a prompt of your configured custom directories to choose from
E.g.

```json
...
    "custom": {
      "uploads": {
        "type": "public",
        "directory": "uploads"
      },
      "assets": {
        "type": "public",
        "directory": "dist"
      }
    }
...
```
Using the example above, issuing `cmsmove push staging custom` will give a prompt similar to the following:
```
[0] uploads
[1] assets
>
```
Entering 0 at the prompt would push the "uploads" directory from the local to the remote configured public directory. While entering 1 would push the assets directory.

## Updating

Simply issue the following command: `composer global update sparkison/cmsmove`
To view the currently installed version issue: `cmsmove --version`

### Contributing

Pull requests, contributions, issues and feature requests are always welcome... Although I would prefer a pull request for new features... ;)
    