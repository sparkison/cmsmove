<?php

namespace BMM\CMSMove\Config\Laravel53;

use Symfony\Component\Console\Question\Question;
use Symfony\Component\Console\Helper\QuestionHelper;

use BMM\CMSMove\Config\Config as BaseConfig;

class Config extends BaseConfig
{

    /**
     * Sync the templates
     */
    public function resources()
    {
        /* Get the template directory from the config file */
        $templateDir = $this->configVars->mappings->resources;

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
        $pluginDir = $this->configVars->mappings->vendor;

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
        $configDir = $this->configVars->mappings->config;

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
        $migrationDir = 'database/' . $this->configVars->mappings->migrations;

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
        $routesDir = $this->configVars->mappings->routes;

        /* Get the remote directory */
        $remoteDir = $this->root . "/" . $routesDir;

        /* Sync it! */
        $this->syncIt($routesDir, $remoteDir, "migrations");

    } // END routes() function

    /**
     * Sync the storage directory
     */
    public function storage()
    {
        /* Get the storage directory from the config file */
        $storageDir = $this->configVars->mappings->storage;

        /* Get the remote directory */
        $remoteDir = $this->root . "/" . $storageDir;

        /* Sync it! */
        $this->syncIt($storageDir, $remoteDir, "migrations");

    } // END storage() function

}