<?php

declare(strict_types=1);

namespace FrigateBin\App\Commands;

use JCli\IO\Interactor;
use Frigate\Cli\Commands\Command;
use FrigateBin\App\Logic\MimeTypesBuilder;
use Frigate\Helpers\MimeType;

class UpdateMimesCommand extends Command
{

    public const COMMAND        = 'update-mimes';
    public const DESCRIPTION    = 'Run Frigate tests';
    public const ALIAS          = 'upm';
    public const DEFAULT        = false;

    private const MIME_TYPES_URL = "https://svn.apache.org/repos/asf/httpd/httpd/trunk/docs/conf/mime.types";

    /**
     * UpdateMimesCommand constructor
     */
    public function __construct(
        string $cwd      = null,
        bool   $as_json  = false
    ) {

        parent::__construct(self::class, $cwd, $as_json);

        $this->usage(
            sprintf('<bold> %s</end> <eol/>', self::COMMAND)
        );
    }

    /**
     * Before execute validate the command
     */
    public function interact(Interactor $io) : void
    {
        parent::interact($io);
    }

    /**
     * Execute the command
     */
    public function execute() : int
    {

        // Download a file from the svn apache server
        $this->jsonIo()?->info("Downloading file from the internet", true);
        $mime_types = file_get_contents(self::MIME_TYPES_URL);

        // Error on remote file download failure:
        if (!$mime_types) {
            return $this->responseError(
                message: "Failed to download mime.types file from apache svn",
                args: [],
                data: []
            );
        }

        // Process the mime types file:
        $builder = new MimeTypesBuilder();
        $builder->loadDefinition($mime_types);
        $source = MimeType::UPDATE_FILE;
        // $source = __DIR__ . DIRECTORY_SEPARATOR . "MimeType.php";
        $saved = $builder->save($source);

        // If we failed return an error:
        if ($saved !== true) {
            return $this->responseError(
                message: $saved,
                args: [],
                data: []
            );
        }

        // Finalize the response:
        $parsed = strlen($mime_types);
        $total_mimes = count($builder->getMapping()['mimes']);
        $template = <<<EOT
        <greenBold>Finished updating mime types database:</end>
            - <cyan>Generated:</end> <bold>%s</end>
            - <cyan>Total Parsed:</end> <bold>%s</end>
            - <cyan>Mime Types:</end> <bold>%d</end>
        EOT;

        // Return the response:
        return $this->responseColorized(
            message: $template,
            args: [ $source, $parsed, $total_mimes ],
            data: [
                "file"         => $source,
                "total_parsed" => $parsed,
                "mime_types"   => $total_mimes
            ],
            status: "done"
        );
    }
}
