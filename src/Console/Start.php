<?php

namespace BMM\CMSMove\Console;

use InvalidArgumentException;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;

class Start extends Command
{
    /**
     * Configure the command.
     *
     * @return void
     */
    protected function configure()
    {
        $this->setName('config')
            ->setDescription('Create a new project configuration file')
            ->addArgument('framework', InputArgument::REQUIRED, 'The framework to create a configuration file for')
            ->addArgument('overwrite', InputArgument::OPTIONAL, 'Whether or not to overwrite an existing config file');
    }

    /**
     * Execute the command.
     *
     * @param  InputInterface $input
     * @param  OutputInterface $output
     * @return void
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $framework = $input->getArgument('framework');
        $configPath = __DIR__ . '/../ConfigGenerators/' . $framework;
        $configClass = $configPath . '/config.php';

        if (!file_exists($configClass)) {
            throw new InvalidArgumentException("Could not find configuration class for `$framework`");
        }

        $configFile = $configPath . '/config.json';

        if (!file_exists($configFile)) {
            throw new InvalidArgumentException("No starter config file found for `$framework`. Please ensure there is a config.json file with configuration defaults to continue.");
        }

        if (file_exists(getcwd() . '/config.json')) {

            $helper = $this->getHelper('question');
            $question = new ConfirmationQuestion('Whoa there! It looks like you already have a config file. Are you sure you want to overwrite it? ', false);

            if (!$helper->ask($input, $output, $question)) {
                return;
            }

        }

        copy($configFile, getcwd() . '/config.json');

        $output->writeln('<comment>Config file created for ' . $framework . '!</comment>');
        $output->writeln('<comment>Please modify file with your application defaults and environment variables.</comment>');
    }
}