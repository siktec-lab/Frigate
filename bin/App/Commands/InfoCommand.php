<?php

declare(strict_types=1);

namespace FrigateBin\App\Commands;

use Frigate\Cli\Commands\Command;
use FrigateBin\App\About as FrigateBinAbout;
use Frigate\About as FrigateAbout;

class InfoCommand extends Command
{
    public const COMMAND        = 'info';
    public const DESCRIPTION    = 'Information about current Frigate installation';
    public const ALIAS          = 'i';
    public const DEFAULT        = false;

    /**
     * InfoCommand constructor
     */
    public function __construct(
        string $cwd      = null,
        bool   $as_json  = false, // this will force the output as json
    ) {
        parent::__construct($this, $cwd, $as_json);

        $this->usage(
            sprintf('<bold> %s</end><eol/>', self::COMMAND)
        );
    }

    /**
     * Execute the command
     */
    public function execute() : int
    {
        $frigate = [
            "Frigate"       => FrigateAbout::VERSION,
            "Frigate CLI"   => FrigateBinAbout::VERSION,
            "PHP Version"   => PHP_VERSION,
            "OS Version"    => PHP_OS,
        ];

        foreach ($frigate as $key => $value) {
            $this->io()->cyan(sprintf("%-15s:", $key))->write($value, true);
        }

        return self::EXIT_CODES['done'];
    }
}
