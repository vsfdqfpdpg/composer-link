<?php

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use \Symfony\Component\Console\Input\InputInterface;
use \Symfony\Component\Console\Output\OutputInterface;

class LinkCommand extends Command
{
    protected static $defaultName = 'link';
    protected $output;

    protected function configure()
    {
        $this->setDescription("Link local package to composer directory.")
            ->setHelp("<info>composer-link link</info>             You need administrator privilege to run this command and this will create a symlink to your composer folder.\n<info>composer-link package version</info>  This will install required linked package.\n");
        $this->addArgument("package", InputArgument::OPTIONAL, "The package name")
            ->addArgument("version", InputArgument::OPTIONAL, "The version of package.", "@dev");
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->output = $output;

        if (!$package = $input->getArgument('package')) {
            $this->link();
        } else {
            $this->linkToProject($package, $input->getArgument('version'));
        }
    }

    protected function linkToProject($package, $version)
    {
        $home = trim(shell_exec('composer config --global home')) . DIRECTORY_SEPARATOR . 'packages' . DIRECTORY_SEPARATOR . $package;

        $config = [
            "type" => "path",
            "url" => $home,
            "options" => [
                "symlink" => true
            ]
        ];

        $registerRepository = 'composer config repositories.' . $package . ' "' . str_replace('"', '\"', json_encode($config)) . '"';
        shell_exec($registerRepository);
        echo 'composer require "' . $package . '" "' . $version . '"';
        shell_exec('composer require "' . $package . '" "' . $version . '"');
    }

    protected function link()
    {
        $name = $this->checkComposerJson();
        $home = trim(shell_exec('composer config --global home'));

        $packageDirectory = $home . DIRECTORY_SEPARATOR . 'packages' . DIRECTORY_SEPARATOR . $name[0];
        !is_dir($packageDirectory) && mkdir($packageDirectory, 0755, true);
        $link = $packageDirectory . DIRECTORY_SEPARATOR . $name[1];

        set_error_handler(array($this, "warning_handler"), E_WARNING);
        if (symlink(getcwd(), $link)) {
            $this->output->writeln("<info>Project linked successfully.</info>");
        }
        restore_error_handler();
    }

    protected function checkComposerJson()
    {
        $jsonFile = getcwd() . DIRECTORY_SEPARATOR . 'composer.json';
        if (!is_readable($jsonFile) || !is_file($jsonFile)) {
            $this->exit('File "./composer.json" cannot be found in the current directory');
        }

        $name = trim(shell_exec('composer config name 2>&1'));
        $version = trim(shell_exec('composer config version 2>&1'));

        if (stripos($name, 'RuntimeException')) {
            $this->exit('Package name is not defined in your composer.json file.');
        }

        if (stripos($version, 'RuntimeException')) {
            $this->exit('Package version is not defined in your composer.json file.');
            exit();
        }

        return explode('/', $name);
    }

    protected function exit($message)
    {
        $this->output->writeln('<error>' . $message . '</error>');
        exit();
    }

    protected function warning_handler($errno, $errstr)
    {
        if (preg_match('/code\((\d+)\)/', $errstr, $match)) {
            switch ($match[1]) {
                case "183":
                    $errstr = 'Your project is already linked.';
                    break;
                case "1314":
                    $errstr = 'Your need run this command as administrator.';
                    break;
                case "123":
                    $errstr = 'Link directory is not valid path.';
                    break;
            }
        }
        $this->output->writeln('<error>' . $errstr . '</error>');
    }
}