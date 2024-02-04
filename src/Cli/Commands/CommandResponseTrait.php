<?php

declare(strict_types=1);

namespace Frigate\Cli\Commands;

use JCli\IO\Interactor;

// respose trait
trait CommandResponseTrait
{
    const JSON_STATUS_DONE      = 'done';
    const JSON_STATUS_ERROR     = 'error';
    const JSON_STATUS_WARNING   = 'warning';
    const JSON_STATUS_ABORT     = 'abort';
    const JSON_STATUS_INFO      = 'info';

    /** available built-in exit codes */
    const EXIT_CODES = [
        "done"      =>  0,
        "info"      =>  0,
        "abort"     =>  0,
        "error"     =>  1,
        "warning"   =>  2,
    ];

    /** default exit code */
    const DEFAULT_EXIT_CODE = 3;

    /** force json response */
    public bool $force_json = false;

    /**
     * get the response code for the status
     */
    private function getResponseCode(string $status) : int 
    {
        return self::EXIT_CODES[$status] ?? self::DEFAULT_EXIT_CODE;
    }

    /**
     * finalize the response and exit if needed
     */
    private function finalizeResponse(bool $exit = false, string $type = "done", int $exit_code = -1) : int 
    {
        $exit_code = $exit_code == -1 ? self::EXIT_CODES[$type] : $exit_code;
        return !$exit ? $exit_code : exit($exit_code);
    }

    /**
     * prepare the response message
     *
     * @param  mixed $io the interactor
     * @param  mixed $message the message to display can contain placeholders
     * @param  mixed $args the arguments to pass to the message placeholders
     * @param  mixed $data the data to add to the response (json only)
     * @param  mixed $status the status of the response (json only) - done, error, warning, abort, info
     */
    public function prepareResponse(
        string  $message, 
        array   $args = [], 
        array   $data = [], 
        string  $status = "done"
    ) : string {
        $message = sprintf($message, ...$args);
        if ($this->force_json) {
            return json_encode([
                'status'    => $status,
                'message'   => $this->removeColorCodes($message),
                'data'      => $data
            ]);
        }
        return $message;
    }
    
    /**
     * response error message
     *
     * @param  Interactor $io the interactor
     * @param  string $message the message to display can contain placeholders
     * @param  array $args the arguments to pass to the message placeholders
     * @param  array $data the data to add to the response (json only)
     * @param  bool $new_line - add a new line after the message
     * @param  bool $exit - exit the script
     * @param  int $exit_code - the exit code to use default is -1 which is automatic
     */
    public function responseError(
        Interactor $io, 
        string $message, 
        array $args = [],
        array $data = [], 
        bool $new_line = true, 
        bool $exit = false,
        int $exit_code = -1 
    ) : int {

        $respose = $this->prepareResponse($message, $args, $data, self::JSON_STATUS_ERROR);

        $io->error($respose, $new_line);

        return $this->finalizeResponse($exit, "error", $exit_code);
    }

    /**
     * response success message
     *
     * @param  Interactor $io the interactor
     * @param  string $message the message to display can contain placeholders
     * @param  array $args the arguments to pass to the message placeholders
     * @param  array $data the data to add to the response (json only)
     * @param  bool $new_line - add a new line after the message
     * @param  bool $exit - exit the script
     * @param  int $exit_code - the exit code to use default is -1 which is automatic
     */
    public function responseSuccess(
        Interactor $io, 
        string $message, 
        array $args = [],
        array $data = [], 
        bool $new_line = true, 
        bool $exit = false,
        int $exit_code = -1 
    ) : int {

        $respose = $this->prepareResponse($message, $args, $data, self::JSON_STATUS_DONE);

        $io->ok($respose, $new_line);

        return $this->finalizeResponse($exit, "done", $exit_code);
    }

    /**
     * response warning message
     *
     * @param  Interactor $io the interactor
     * @param  string $message the message to display can contain placeholders
     * @param  array $args the arguments to pass to the message placeholders
     * @param  array $data the data to add to the response (json only)
     * @param  bool $new_line - add a new line after the message
     * @param  bool $exit - exit the script
     * @param  int $exit_code - the exit code to use default is -1 which is automatic
     */
    public function responseWarning(
        Interactor $io, 
        string $message, 
        array $args = [],
        array $data = [], 
        bool $new_line = true, 
        bool $exit = false,
        int $exit_code = -1 
    ) : int {

        // Prepare response:
        $respose = $this->prepareResponse($message, $args, $data, self::JSON_STATUS_WARNING);

        // Print response:
        $io->warn($respose, $new_line);
        
        // Finalize response:
        return $this->finalizeResponse($exit, "warning", $exit_code);
    }

    /**
     * response warning message
     *
     * @param  Interactor $io the interactor
     * @param  string $message the message to display can contain placeholders
     * @param  array $args the arguments to pass to the message placeholders
     * @param  array $data the data to add to the response (json only)
     * @param  bool $new_line - add a new line after the message
     * @param  bool $exit - exit the script
     * @param  int $exit_code - the exit code to use default is -1 which is automatic
     */
    public function responseInfo(
        Interactor $io, 
        string $message, 
        array $args = [],
        array $data = [], 
        bool $new_line = true, 
        bool $exit = false,
        int $exit_code = -1 
    ) : int {

        $respose = $this->prepareResponse($message, $args, $data, self::JSON_STATUS_INFO);

        $io->info($respose, $new_line);

        return $this->finalizeResponse($exit, "info", $exit_code);
    }

    /**
     * response warning message
     *
     * @param  Interactor $io the interactor
     * @param  string $message the message to display can contain placeholders
     * @param  array $args the arguments to pass to the message placeholders
     * @param  array $data the data to add to the response (json only)
     * @param  bool $new_line - add a new line after the message
     * @param  bool $exit - exit the script
     * @param  int $exit_code - the exit code to use default is -1 which is automatic
     */
    public function responseAbort(
        Interactor $io, 
        string $message, 
        array $args = [],
        array $data = [], 
        bool $new_line = true, 
        bool $exit = false,
        int $exit_code = -1 
    ) : int {

        $respose = $this->prepareResponse($message, $args, $data, self::JSON_STATUS_ABORT);

        $io->warn($respose, $new_line);

        return $this->finalizeResponse($exit, "abort", $exit_code);
    }

    /**
     * response colorized message
     *
     * @param  Interactor $io the interactor
     * @param  string $message the message to display can contain placeholders and color tags
     * @param  array $args the arguments to pass to the message placeholders
     * @param  array $data the data to add to the response (json only)
     * @param  string $status the status of the response (json only) - done, error, warning, abort, info
     * @param  bool $new_line - add a new line after the message
     * @param  bool $exit - exit the script
     * @param  int $exit_code - the exit code to use default is -1 which is automatic
     */
    public function responseColorized(
        Interactor $io, 
        string $message, 
        array $args = [],
        array $data = [], 
        string $status = "done",
        bool $new_line = true, 
        bool $exit = false,
        int $exit_code = -1
    ) : int {

        $respose = $this->prepareResponse($message, $args, $data, $status);

        $io->colors($respose, $new_line);

        return $this->finalizeResponse($exit, $status, $exit_code);
    }

    /**
     * remove color codes from a string
     */
    private function removeColorCodes(string $message) : string
    {
        return preg_replace("/\x1b\[[0-9;]*m/", "", $message);
    }
}