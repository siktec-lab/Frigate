<?php

namespace FrigateBin\App\Commands;


use JCli\IO\Interactor;
use JCli\Input\Command;

use FrigateBin\App\About as FrigateBinAbout;
use Frigate\About as FrigateAbout;



class InfoCommand extends Command
{
    public const COMMAND        = 'info';
    public const DESCRIPTION    = 'Information about current Frigate installation';
    public const ALIAS          = 'i';
    public const DEFAULT        = false;

    public function __construct()
    {
        parent::__construct(self::COMMAND, self::DESCRIPTION);

        $this->usage(
            // append details or explanation of given example with ` ## ` so they will be uniformly aligned when shown
            sprintf('<bold> %s</end><eol/>', self::COMMAND)
        );
    }

    // This method is auto called before `self::execute()`
    public function interact(Interactor $io) : void
    {

    }

    // When app->handle() locates `init` command it automatically calls `execute()`
    public function execute()
    {
        /** @var Interactor $io */
        $io = $this->app()->io();

        $frigate = [
            "Frigate"       => FrigateAbout::VERSION,
            "Frigate CLI"   => FrigateBinAbout::VERSION,
            "PHP Version"   => PHP_VERSION,
            "OS Version"    => PHP_OS,
        ];

        foreach ($frigate as $key => $value) {
            $io->cyan(sprintf("%-15s:", $key))->write($value, true);
        }
    }
}
