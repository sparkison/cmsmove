<?php

namespace BMM\CMSMove\Config\Laravel53;

use Symfony\Component\Console\Question\Question;
use Symfony\Component\Console\Helper\QuestionHelper;
use phpseclib\Net\SSH2 as Net_SSH2;
use phpseclib\Crypt\RSA as Crypt_RSA;

use BMM\CMSMove\Config\Config as BaseConfig;

class Config extends BaseConfig
{


    /**
     * Sync the entire application directory
     */
    public function all()
    {
        /* Upload the main application folders and files needed to run */
        $directories = [
            // Sync the app directory
            $this->configVars->mappings->app => $this->root . '/' . $this->configVars->mappings->app,
            // Sync the public directory
            $this->configVars->mappings->www => $this->root . '/' . $this->public,
            // Sync the bootstrap directory
            'bootstrap' => $this->root . '/bootstrap',
            // Sync the config directory
            'config' => $this->root . '/config',
            // Sync the database folder
            'database' => $this->root . '/database',
            // Sync the resources folder
            'resources' => $this->root . '/resources',
            // Sync the routes folder
            'routes' => $this->root . '/routes',
            // Sync the storage folder
            'storage' => $this->root . '/storage',
            // Sync the tests folder
            'tests' => $this->root . '/tests',
            // Sync the vendor folder
            'vendor' => $this->root . '/vendor',
        ];

        /* Loop through and sync the directories */
        foreach($directories as $source => $destination) {
            /* Sync it! */
            $this->syncIt($source, $destination, "all");
        }

        /* Add files */
        $files = [
            // Sync the env file
            '.env' => $this->root . '/.env',
            // Sync the artisan file
            'artisan' => $this->root . '/artisan',
            // Sync the server file
            'server.php' => $this->root . '/server.php'
        ];
        /* Loop through and sync the files */
        foreach($files as $source => $destination) {
            /* Sync it! */
            $this->syncIt($source, $destination, "all", true, true);
        }


    } // END all() function

    /**
     * Sync the core directories as these are likely the ones that will change the most often
     */
    public function core()
    {
        /* Upload the main application folders and files needed to run */
        $directories = [
            // Sync the app directory
            $this->configVars->mappings->app => $this->root . '/' . $this->configVars->mappings->app,
            // Sync the public directory
            $this->configVars->mappings->www => $this->root . '/' . $this->public,
            // Sync the config directory
            'config' => $this->root . '/config',
            // Sync the database folder
            'database' => $this->root . '/database',
            // Sync the resources folder
            'resources' => $this->root . '/resources',
            // Sync the routes folder
            'routes' => $this->root . '/routes',
            // Sync the vendor folder
            'vendor' => $this->root . '/vendor',
        ];

        /* Loop through and sync the directories */
        foreach($directories as $source => $destination) {
            /* Sync it! */
            $this->syncIt($source, $destination, "core");
        }
    }

    /**
     * Sync the env file
     * Will look for env.{environment} and copy to the {environment} as .env
     */
    public function env()
    {
        $env = '.env.' . $this->environment;
        if(is_file($env)) {
            $this->syncIt($env, $this->root . '/.env', "env", true, true);
        } else {
            $this->io->error('Unable to locate file "' . $env . '".');
            die();
        }
    }

    /**
     * Sync the templates
     */
    public function resources()
    {
        /* Get the template directory from the config file */
        $templateDir = 'resources';

        /* Get the remote directory */
        $remoteDir = $this->root . "/" . $templateDir;

        /* Sync it! */
        $this->syncIt($templateDir, $remoteDir, "resources");

    } // END templates() function

    /**
     * Sync the plugins
     */
    public function vendor()
    {
        /* Get the plugins directory from the config file */
        $pluginDir = 'vendor';

        /* Get the remote directory */
        $remoteDir = $this->root . "/" . $pluginDir;

        /* Sync it! */
        $this->syncIt($pluginDir, $remoteDir, "vendor");

    } // END plugins() function

    /**
     * Sync the config directory
     */
    public function config()
    {
        /* Get the config directory from the config file */
        $configDir = 'config';

        /* Get the remote directory */
        $remoteDir = $this->root . "/" . $configDir;

        /* Sync it! */
        $this->syncIt($configDir, $remoteDir, "config");

    } // END config() function

    /**
     * Sync the app directory
     */
    public function app()
    {
        /* Get the app directory from the config file */
        $appDir = $this->configVars->mappings->app;

        /* Get the remote directory */
        $remoteDir = $this->root . "/" . $appDir;

        /* Sync it! */
        $this->syncIt($appDir, $remoteDir, "app");

    } // END app() function

    /**
     * Sync the www directory
     */
    public function www()
    {
        /* Get the public directory from the config file */
        $publicDir = $this->configVars->mappings->www;

        /* Get the remote directory */
        $remoteDir = $this->root . "/" . $this->public;

        /* Sync it! */
        $this->syncIt($publicDir, $remoteDir, "public");

    } // END www() function

    /**
     * Sync the migrations directory
     */
    public function migrations()
    {
        /* Get the migrations directory from the config file */
        $migrationDir = 'database/migrations';

        /* Get the remote directory */
        $remoteDir = $this->root . "/" . $migrationDir;

        /* Sync it! */
        $this->syncIt($migrationDir, $remoteDir, "migrations");

    } // END migrations() function

    /**
     * Sync the routes directory
     */
    public function routes()
    {
        /* Get the routes directory from the config file */
        $routesDir = 'routes';

        /* Get the remote directory */
        $remoteDir = $this->root . "/" . $routesDir;

        /* Sync it! */
        $this->syncIt($routesDir, $remoteDir, "routes");

    } // END routes() function

    /**
     * Sync the storage directory
     */
    public function storage()
    {
        /* Get the storage directory from the config file */
        $storageDir = 'storage';

        /* Get the remote directory */
        $remoteDir = $this->root . "/" . $storageDir;

        /* Sync it! */
        $this->syncIt($storageDir, $remoteDir, "storage");

    } // END storage() function

    /**
     * Issue the migrate command on the specified environment
     *
     * @param string $migrate_options
     * @return mixed
     */
    public function migrate($migrate_options)
    {
        /* Setup ssh class for making the connection */
        $ssh = new Net_SSH2($this->host, $this->sshPort);

        /* Enable quite mode to prevent printing of "stdin: is not a tty" line in the output stream */
        $ssh->enableQuietMode();
        if (!empty($this->sshKeyFile)) {
            $key = new Crypt_RSA();
            $key->loadKey(file_get_contents($this->sshKeyFile));
            if (!$ssh->login($this->sshUser, $key, Net_SSH2::LOG_SIMPLE)) {
                $this->io->error('Unable to login into remote host using ssh key');
                die();
            }
        } else {
            if (!$ssh->login($this->sshUser, $this->sshPass, Net_SSH2::LOG_SIMPLE)) {
                $this->io->error('Unable to login into remote host using password');
                die();
            }
        }

        /* Output the ssh log, if anything */
        if(!empty($ssh->message_log))
            $this->io->note($ssh->message_log);

        /* If here, connected successfully to remote host! */
        $command = $migrate_options ? "cd {$this->root} && php artisan migrate:" . $migrate_options : "cd {$this->root} && php artisan migrate";
        $this->io->text("<remote>Executing remote command:</remote> " . $command);
        $this->io->text($ssh->exec($command));

    } // END migrate() function

}