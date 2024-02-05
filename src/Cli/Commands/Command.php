<?php

declare(strict_types=1);

namespace Frigate\Cli\Commands;

use JCli\IO\Interactor;
use JCli\Input\Command as BaseCommand;
use Frigate\Exceptions\FrigateCliException;

abstract class Command extends BaseCommand
{

    use CommandResponseTrait;

    public const COMMAND        = '!undefined!';
    public const DESCRIPTION    = '';
    public const ALIAS          = '';
    public const DEFAULT        = false;

    public string $cwd = __DIR__;

    /** force json response */
    public bool $force_json = false;

    public function __construct(
        string|object $of = '',
        string $cwd      = null,
        bool   $as_json  = false, // this will force the output as json
    ) {

        // Get the command and description from the class if not defined
        $of = is_object($of) ? get_class($of) : $of;
        $command = is_a($of, self::class, true) && defined("$of::COMMAND") ? $of::COMMAND : null;
        $description = is_a($of, self::class, true) && defined("$of::DESCRIPTION") ? $of::DESCRIPTION : null;

        //Make sure the command is defined
        if ($command === '!undefined!' || $command === null) {
            throw new FrigateCliException(FrigateCliException::CODE_FRIGATE_CLI_COM_CONSTANT, [$of]);
        }

        // Call the native constructor:
        parent::__construct($command, $description);

        // Specify the current working directory this is important in case the command is called from another directory
        // Maybe invoked directly from the our basecode.
        $this->cwd = ($cwd ?? getcwd()) ?: __DIR__;
        // Add a common option to force the output as json
        $this->option('-j, --json', 'Force output as json', 'boolval', $as_json);
    }

    /**
     * Helper method to get the IO interactor - It will return null if the IO is not set,
     * Or if we are in JSON mode i.e --json flag is set.
    */
    protected function jsonIo() : ?Interactor
    {
        return $this->force_json ? null : $this->app()->io();
    }
    protected function io() : Interactor
    {
        return $this->app()->io();
    }
    /** 
     * This method is auto called before `self::execute()`
     * Primarily used to interact with the user before the command is executed.
     * If its overridden the parent method should be called. or handle the json flag yourself.
     */
    public function interact(Interactor $io) : void 
    {
        $this->interactJson();
    }

    /** 
     * A flag is a special property that is usually set to FALSE by default.
     * The property flag ALWAYS overrides the option flag meaning if the property flag is set to TRUE
     * the option flag will be ignored.
     * If the property flag is not set or is FALSE the option flag will be used.
     * A Flag ALWAYS has a different name from the option.
    */
    protected function interactBooleanFlag(string $property, string $option) : ?bool 
    {
        if (!property_exists($this, $property)) {
            return null;
        }
        if (!property_exists($this, $option) && $this->__get($option) === null) {
            return (bool)$this->$property;
        }
        $this->$property = (bool)$this->$property || (bool)$this->$option;
        return $this->$property;
    }

    /** 
     * Helper method to get the special flag to force the output as json.
     */
    protected function interactJson() : ?bool {
        return $this->interactBooleanFlag('force_json', 'json');
    }

    abstract public function execute() : int;
}
