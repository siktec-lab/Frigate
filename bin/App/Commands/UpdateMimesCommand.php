<?php

declare(strict_types=1);

namespace FrigateBin\App\Commands;

use JCli\IO\Interactor;
use Frigate\Cli\Commands\Command;
use Frigate\Tools\Paths\PathHelpers as Path;
use Frigate\Tools\MimeTypes\MimeTypesFileBuilder as MimeBuilder;

class UpdateMimesCommand extends Command
{

    public const COMMAND        = 'update-mimes';
    public const DESCRIPTION    = 'Run Frigate tests';
    public const ALIAS          = 'upm';
    public const DEFAULT        = false;

    private const MIME_TYPES_URL = "https://svn.apache.org/repos/asf/httpd/httpd/trunk/docs/conf/mime.types";

    public function __construct(
        string $cwd      = null,
        bool   $as_json  = false
    ) {

        parent::__construct(self::class, $cwd, $as_json);

        $this->usage(
            sprintf('<bold> %s</end> <eol/>', self::COMMAND)
        );
    }

    // This method is auto called before `self::execute()`
    public function interact(Interactor $io) : void
    {
        parent::interact($io);
    }

    // When app->handle() locates `init` command it automatically calls `execute()`
    public function execute() : int
    {
        /** @var Interactor $io */
        $io = $this->app()->io();

        // Download a file from the svn apache server
        $io->info("Downloading file from the internet", true);
        $mime_types = file_get_contents(self::MIME_TYPES_URL);

        // Error on remote file download failure:
        if ($mime_types === false) {
            return $this->responseError(
                io: $io,
                message: "Failed to download mime.types file from apache svn",
                args: [],
                data: []
            );
        }

        // Process the mime types file:
        $builder = new MimeBuilder();
        $builder->loadDefinition($mime_types);
        $output = Path::join(__DIR__, "mime.types.php"); //TODO: should point to the frigate data directory
        $saved = $builder->save($output);

        // If we failed return an error:
        if (!$saved) {
            return $this->responseError(
                io: $io,
                message: "Failed to save mime.types.php file to disk",
                args: [],
                data: []
            );
        }

        // Finalize the response:
        $parsed = strlen($mime_types);
        $total_mimes = count($builder->getMapping()['mimes']);
        $template = <<<EOT
        Finished updating mime types database:
            - Generated: %s
            - Total Parsed: %s
            - Mime Types: %d
        EOT;

        // Return the response:
        return $this->responseColorized(
            io: $io,
            message: $template,
            args: [ $output, $parsed, $total_mimes ],
            data: [
                "file"         => $output,
                "total_parsed" => $parsed,
                "mime_types"   => $total_mimes
            ],
            status: "done"
        );
    }
}
