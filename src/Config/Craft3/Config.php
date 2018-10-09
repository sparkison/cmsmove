<?php

namespace BMM\CMSMove\Config\Craft3;

use Symfony\Component\Console\Question\Question;
use Symfony\Component\Console\Helper\QuestionHelper;

use BMM\CMSMove\Config\Config as BaseConfig;

class Config extends BaseConfig
{
    /**
     * Sync it all!
     */
    public function all()
    {
        // All core directories
        $this->core();

        // Public directory
        $this->www();

        // Push the config
        $this->env();
    } // END all() function

    /**
     * Sync the core directories
     */
    public function core()
    {
        $this->app();
        $this->templates();
        $this->modules();
        $this->config();
        $this->storage();
    } // END core() function

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
    } // END env() function

    /**
     * Sync the templates
     */
    public function templates()
    {
        /* Get the template directory from the config file */
        $templateDir = $this->configVars->mappings->templates;

        /* Get the remote directory */
        $remoteDir = $this->root . "/" . $templateDir;

        /* Sync it! */
        $this->syncIt($templateDir, $remoteDir, "templates");

    } // END templates() function

    /**
     * Sync the plugins
     */
    public function plugins()
    {
        /* Get the plugins directory from the config file */
        $pluginDir = $this->configVars->mappings->plugins;

        /* Get the remote directory */
        $remoteDir = $this->root . "/" . $pluginDir;

        /* Sync it! */
        $this->syncIt($pluginDir, $remoteDir, "plugins");

    } // END plugins() function

    /**
     * Sync the modules directory
     */
    public function modules()
    {
        /* Get the config directory from the config file */
        $configDir = $this->configVars->mappings->modules;

        /* Get the remote directory */
        $remoteDir = $this->root . "/" . $configDir;

        /* Sync it! */
        $this->syncIt($configDir, $remoteDir, "modules");

    } // END config() function

    /**
     * Sync the config directory
     */
    public function config()
    {
        /* Get the config directory from the config file */
        $configDir = $this->configVars->mappings->config;

        /* Get the remote directory */
        $remoteDir = $this->root . "/" . $configDir;

        /* Sync it! */
        $this->syncIt($configDir, $remoteDir, "config");

    } // END config() function

    /**
     * Sync the storage directory
     */
    public function storage()
    {
        /* Get the config directory from the config file */
        $configDir = $this->configVars->mappings->storage;

        /* Get the remote directory */
        $remoteDir = $this->root . "/" . $configDir;

        /* Sync it! */
        $this->syncIt($configDir, $remoteDir, "storage");
    } // END storage() function

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

}