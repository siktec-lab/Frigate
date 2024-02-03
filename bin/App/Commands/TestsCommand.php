<?php

namespace FrigateBin\App\Commands;

use JCli\IO\Interactor;
use JCli\Input\Command;
use Frigate\Tools\FileSystem\FilesHelper as Files;
use Frigate\Tools\Paths\PathHelpers as Path;

class TestsCommand extends Command
{

    use \Frigate\Cli\Commands\CommandResponseTrait;

    public const COMMAND        = 'tests';
    public const DESCRIPTION    = 'Run Frigate tests';
    public const ALIAS          = 'test';
    public const DEFAULT        = false;

    public function __construct() {

        parent::__construct(self::COMMAND, self::DESCRIPTION);

        $this->argument(
            '[testsOf]', 
            sprintf('Which level of tests to run empty for interactive mode')
        );
        $this->option('-a, --all', 'Run all tests', 'boolval', false);
        $this->option('-t, --test', 'Which test to run', 'strval', "__none__");

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

        // Download a file from the internet: https://svn.apache.org/repos/asf/httpd/httpd/trunk/docs/conf/mime.types
        $io->info("Downloading file from the internet", true);

        // Do it with php to avoid dependencies

        // $mime_types = file_get_contents("https://svn.apache.org/repos/asf/httpd/httpd/trunk/docs/conf/mime.types");

        // If we failed return an error:
        // if ($mime_types === false) {
        //     $this->response_error(
        //         io: $io,
        //         message: "Failed to download mime.types file from apache svn",
        //         args: [],
        //         data: [],
        //         new_line: true,
        //         die: true
        //     );
        //     return;
        // }

        $temp_file = Path::path(CWD, "mime.types");
        if (!Files::appendFile($temp_file, "Hello World" . PHP_EOL)) {
            $this->response_error(
                io: $io,
                message: "Failed to write mime.types file to disk",
                args: [],
                data: [],
                new_line: true,
                die: true
            );
            return;
        }
        
        // $file = fopen($temp_file, "w");
        // flock($file, LOCK_EX);
        // fwrite($file, "Hello World" . PHP_EOL);
        // flock($file, LOCK_UN);
        // fclose($file);
        // var_dump(filesize($temp_file));
        // $file = new \SplFileObject($temp_file, "w");
        // $file->flock(LOCK_EX);
        // $file->fwrite("Hello World" . PHP_EOL);
        // $file->fflush();
        // $file->flock(LOCK_UN);
        // unset($file);

        // Reset statcache
        // clearstatcache(true, $temp_file);
        // var_dump(filesize($temp_file));
        
        // $file = fopen($temp_file, "r");
        // flock($file, LOCK_SH);
        // $content = fread($file, 1000);
        // flock($file, LOCK_UN);
        // fclose($file);

        
        $content = Files::readFileContents($temp_file, true);
        // $content = file_get_contents($temp_file);
        
        echo PHP_EOL . $content;

        $this->response_success(
            io: $io,
            message: "Finished updating mime types database",
            args: [],
            data: [],
            new_line: true,
            die: true
        );
    }
}
