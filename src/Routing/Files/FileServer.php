<?php 

namespace Frigate\Routing\Files;

use Frigate\FrigateApp;
use Frigate\Api\EndPoint;
use Frigate\Api\EndPointContext;
use Frigate\Routing\Http;
use Frigate\Routing\Files;

class FileServer extends EndPoint { 

    use EndPointContext;

    protected string $temp_folder;
    protected string $storage_folder;
    public array $allowed_files = [

    ];
    public function __construct(
        bool $debug = false, 
        bool $auth = false, 
        string $auth_method = "session"
    ){
        parent::__construct($debug, $auth, $auth_method);

        
    }

    public function set_folders(string $temp, string $storage, bool $create = false) {
        //create folder if not exists:
        if (!file_exists($temp) ) {
            if ($create && !@mkdir($temp, 0775, true)) {
                throw new \Exception("Could not create FileServer temp folder: ".$temp);
            } elseif (!file_exists($temp)) {
                throw new \Exception("FileServer temp folder does not exist: ".$temp);
            }
        }

        if (!file_exists($storage)) {
            if ($create && !@mkdir($storage, 0775, true)) {
                throw new \Exception("Could not create fileserver storage folder: ".$storage);
            } elseif (!file_exists($storage)) {
                throw new \Exception("FileServer storage folder does not exist: ".$storage);
            }
        }
        $this->temp_folder = $temp;
        $this->storage_folder = $storage;
    }

    public function serve_file(string $key) : ?array {
        return [$key, null];
    }

    public function upload_validate(?array $files) : array {
        $message = "";
        if (!empty($this->allowed_files) && !empty($files)) {

            $not_allowed = array_filter($files, function($file) { 

                $wild_group = (explode("/", $file["type"])[0] ?? "")."/*";
                
                return  !in_array($file['type'], $this->allowed_files) 
                        && 
                        !in_array($wild_group, $this->allowed_files);
            });

            foreach ($not_allowed as $file) {
                $message .= "File type not allowed: ".$file['type']." [". $file["name"] ."]";
            }
        }
        return [empty($message), $message];
    }

    public function call(array $context, Http\RequestInterface $request) : Http\Response {

        /** @var RouteRequest $request */ // we use this to force type hinting
        
        FrigateApp::debug($this, "Execute endpoint - CreateProject\n".$request);
        
        // 4 types of requests:
        // 1. GET:    Get a file
        // 2. POST:   Upload a file or start a chunk upload
        // 3. PATCH:  Upload a file chunk
        // 4. DELETE: Delete a temp file
        // 5. HEAD:   Get upload status of chunked upload
        $method = $request->getMethod();

        switch ($method) {

            //Serve a file:
            case Http\Methods::GET: {

                $file = $this->get_context("file", $context, "");
                [$file, $filename] = $this->serve_file($file);

                //Validate file: exists, readable, not a directory
                if (empty($file) || !file_exists($file)) {
                    return new Http\Response(
                        status: 404,
                        headers: [],
                        body: "File not found"
                    );
                }

                //The file meta:
                $mime = mime_content_type($file) ?: 'application/octet-stream';
                $size = filesize($file);
                $range = $request->getHeader("Content-Range") ?? false;

                //Set headers:
                $header = [
                    "Access-Control-Allow-Origin"   => "*",
                    "Access-Control-Allow-Headers"  => "*",
                    "Access-Control-Expose-Headers" => "*",
                    "Accept-Ranges"                 => "bytes",
                    "Content-Length"                => $size,
                    "Content-Type"                  => $mime,
                    "Content-Disposition"           => 'inline'.($filename ? '; filename="'.$filename.'"' : ''),
                    "Pragma"                        => "public",
                    "Expires"                       => "-1",
                    "Cache-Control"                 => "no-cache",
                    "Cache-Control"                 => "public, must-revalidate, post-check=0, pre-check=0"
                ];

                if ($range) {
                    $header["Content-Range"] = $request->getHeader("Content-Range");
                }

                return new Http\Response(
                    status    : $range ?: 200,
                    headers : $header,
                    body    : fopen($file, "rb")
                );

            } break;

            //Upload a file:
            case Http\Methods::POST: {
                $user = $request->requireAuthorization($this->authorize_method, throw : true);
                
                $file = $this->get_context("file", $context, "upload");
                if ($file === "upload") {
                    $upload = new Files\Upload();
                    $upload->populate("files"); // TODO: make this configurable
                    $files = $upload->get_files(); // null when we are starting a chunk upload
                    if ($files != null && empty($files)) {
                        return new Http\Response(
                            status: 400, // Bad Request
                            headers: [ "Content-Type" => "text/plain" ],
                            body: "No files to upload"
                        );
                    }

                    // test if server had trouble copying files
                    if ($files != null) {
                        $file_errors = array_filter($files, function($file) { return $file['error'] !== 0; });
                        if (count($file_errors)) {
                            $errors = "";
                            foreach ($file_errors as $file) {
                                $errors .= sprintf("Uploading file \"%s\" failed with code \"" . $file['error'] . "\".", $file['name']);
                            }
                            return new Http\Response(
                                status: 500,
                                headers: [ "Content-Type" => "text/plain" ],
                                body: $errors
                            );
                        }
                    }

                    // test if files are of invalid format
                    [$validate, $message] = $this->upload_validate($files);
                    if (!$validate) {
                        return new Http\Response(
                            status: 415, // Unsupported Media Type
                            headers: [ "Content-Type" => "text/plain" ],
                            body: $message
                        );
                    }

                    // test if files are too large
                    [$result, $message] = $upload->store_in_temp($this->temp_folder);
                    if ($result) {
                        return new Http\Response(
                            status: 201, // 201 Created
                            headers: [ "Content-Type" => "text/plain" ],
                            body: $upload->get_id()
                        );
                    } else {
                        return new Http\Response(
                            status: 500,
                            headers: [ "Content-Type" => "text/plain" ],
                            body: $message
                        );
                    }
                } else {
                    return new Http\Response(
                        status: 400, // Bad Request
                        headers: [ "Content-Type" => "text/plain" ],
                        body: "No files to upload"
                    );
                }
            } break;
            case Http\Methods::PATCH: {
                $user = $request->requireAuthorization($this->authorize_method, throw : true);
                $file = $this->get_context("file", $context, "upload");
                if ($file === "upload") {

                    //Get expected headers:
                    $offset = trim($request->getHeader("Upload-Offset"));
                    $length = trim($request->getHeader("Upload-Length"));
                    $name   = trim($request->getHeader("Upload-Name") ?? "");
                    $key    = trim($request->getQueryParameters()["patch"] ?? "");

                    [$result, $message] = Files\Upload::patch_temp_file($this->temp_folder, $offset, $length, $name, $key);

                    if ($result) {
                        return new Http\Response(
                            status: 204, // 204 No Content
                            headers: [ "Content-Type" => "text/plain" ],
                            body: $message
                        );
                    } else {
                        return new Http\Response(
                            status: 400, // Bad Request
                            headers: [ "Content-Type" => "text/plain" ],
                            body: $message
                        );
                    }
                } else {
                    return new Http\Response(
                        status: 400, // Bad Request
                        headers: [ "Content-Type" => "text/plain" ],
                        body: "Bad endpoint request format"
                    );
                }
            } break;
            case Http\Methods::HEAD: {
                $user = $request->requireAuthorization($this->authorize_method, throw : true);
                $key    = trim($request->getQueryParameters()["patch"] ?? "");

                [$result, $message] = Files\Upload::head_temp_file($this->temp_folder, $key);

                if ($result) {
                    return new Http\Response(
                        status: 200, // 204 No Content
                        headers: [ 
                            "Content-Type" => "text/plain",
                            "Upload-Offset" => $message
                        ],
                        body: ""
                    );
                } else {
                    return new Http\Response(
                        status: 400, // Bad Request
                        headers: [ "Content-Type" => "text/plain" ],
                        body: $message
                    );
                }

            } break;
            case Http\Methods::DELETE: {
                $user = $request->requireAuthorization($this->authorize_method, throw : true);
                $file = $this->get_context("file", $context, "upload");
                if ($file === "upload") {
                    $file_temp_key = trim($request->getBodyAsString());
                    [$result, $message] = Files\Upload::delete_temp_file($this->temp_folder, $file_temp_key);
                    if ($result) {
                        return new Http\Response(
                            status: 204, // 204 No Content
                            headers: [ "Content-Type" => "text/plain" ],
                            body: ""
                        );
                    } else {
                        return new Http\Response(
                            status: 400,
                            headers: [ "Content-Type" => "text/plain" ],
                            body: $message
                        );
                    }
                }
                return new Http\Response(
                    status: 400, // Bad Request
                    headers: [ "Content-Type" => "text/plain" ],
                    body: "Bad endpoint request format"
                );
            } break;
        }
    }

}