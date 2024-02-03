<?php

declare(strict_types=1);

namespace Frigate\Cli;

use RecursiveIteratorIterator;
use RecursiveDirectoryIterator;
use JCli\Application;
use JCli\Input\Command as CliCommand;

class CliApp {

    public ?Application $cli = null;

    public static string $LOGO = <<<EOT

        ______       _                __      
       / ____/_____ (_)____ _ ____ _ / /_ ___ 
      / /_   / ___// // __ `// __ `// __// _ \
     / __/  / /   / // /_/ // /_/ // /_ /  __/
    /_/    /_/   /_/ \__, / \__,_/ \__/ \___/ 
                    /____/                    

    EOT;

    public function __construct(
        public ?string $cwd = null,
        public string  $name = "CLI App",
        public string  $version = "0.0.1",
        ?string $logo = null
    ) {

        $this->cli = new Application($name, $version);
        
        $this->cwd = ($cwd ?? getcwd()) ?: __DIR__;

        // register all default commands:
        //TODO: maybe its a good idea to load all commands from a folder
        //TODO: maybe its a good idea to prefix all commands with a namespace indicating the source of the command
        
        //TODO: add response traits to the commands
        //TODO: add json support to the commands

        // Set logo
        $this->cli->logo($logo ?? self::$LOGO);
    }

    public function addCommand(CliCommand $command, string $alias = "", bool $default = false) : void
    {
        $this->cli->add($command, $alias, $default);
    }

    public function autoLoadCommands($namespace = "", string $folder) : array
    {
        // Make sure the folder exists:
        if (!is_dir($folder)) {
            throw new \Exception("Folder $folder does not exist"); // TODO: add proper exception
        }
        // Iterate over all files in the folder using RecursiveDirectoryIterator and SplFileInfo:
        $commands = [];
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($folder, RecursiveDirectoryIterator::SKIP_DOTS), 
            RecursiveIteratorIterator::SELF_FIRST
        );
        foreach ($iterator as $file) {
            // Only take php files into account:
            /** @var \SplFileInfo $file */
            if ($file->getExtension() !== 'php') {
                continue;
            }
            // Only name:
            $name = $file->getBasename(".php");
            
            // If does not end with Command, skip:
            if (!str_ends_with($name, 'Command')) {
                continue;
            }

            // load the command:
            $class = $namespace . "\\" . $name;

            // if its an instance of Command, add it to the list of commands:
            if (!is_subclass_of($class, CliCommand::class)) {
                continue;
            }
            $commands[] = $class;

            //TODO: make an interface for commands so we know the have the ALIAS and DEFAULT properties
            // Add the command:
            $this->addCommand(new $class(), $class::ALIAS, $class::DEFAULT);
        }
        return $commands;
    }

    public function handle(?array $argv = null) : void
    {
        $this->cli->handle($argv ?? $_SERVER['argv']);
    }
}

