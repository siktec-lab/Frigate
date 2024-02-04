<?php

declare(strict_types=1);

namespace Frigate\Cli;

use RecursiveIteratorIterator;
use RecursiveDirectoryIterator;
use JCli\Application;
use JCli\Input\Command as CliCommand;
use Frigate\Tools\Paths\PathHelpers as Path;

class CliApp {

    const COMMAND_CLASS_SUFFIX = "Command";

    public ?Application $cli = null;

    public static string $LOGO = <<<EOT

        ______       _                __      
       / ____/_____ (_)____ _ ____ _ / /_ ___ 
      / /_   / ___// // __ `// __ `// __// _ \
     / __/  / /   / // /_/ // /_/ // /_ /  __/
    /_/    /_/   /_/ \__, / \__,_/ \__/ \___/ 
                    /____/                    

    EOT;

    /**
     * CliApp constructor
     */
    public function __construct(
        public ?string $cwd     = null,
        public string  $name    = "CLI App",
        public string  $version = "0.0.1",
        ?string $logo           = null
    ) {
        // Create the cli application
        $this->cli = new Application($name, $version);
        //Global cwd
        $this->cwd = ($cwd ?? getcwd()) ?: __DIR__;
        // Set logo
        $this->cli->logo($logo ?? self::$LOGO);
    }

    /**
     * Add a command to the cli application
     */
    public function addCommand(CliCommand $command, string $alias = "", bool $default = false) : void
    {
        $this->cli->add($command, $alias, $default);
    }

    /**
     * Auto load commands from a folder
     */
    public function autoLoadCommands(string $namespace = "", string $folder) : array
    {
        // Make sure the folder exists:
        if (!is_dir($folder)) {
            throw new \Exception("Folder {$folder} does not exist"); // TODO: add proper exception
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
            if (!str_ends_with($name, self::COMMAND_CLASS_SUFFIX)) {
                continue;
            }

            // load the command:
            $class = $namespace . "\\" . $name;

            // if its an instance of Command, add it to the list of commands:
            if (!is_subclass_of($class, CliCommand::class)) {
                continue;
            }
            $commands[] = $class;

            // Can throw an exception if the class is not compatible.
            // Probably because constants are not defined.
            $instance = new $class($this->cwd);

            // Add the command:
            $this->addCommand($instance, $instance::ALIAS, $instance::DEFAULT);
        }
        return $commands;
    }

    /**
     * Handle the cli application
     */
    public function handle(?array $argv = null) : void
    {
        $this->cli->handle($argv ?? $_SERVER['argv']);
    }
}

