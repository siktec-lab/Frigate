<?php 

declare(strict_types=1);

namespace Frigate\Api\Impl;

use SplFileObject;
use Frigate\Api\EndPoint;
use Frigate\Api\Traits\ContextHelpers;
use Frigate\Routing\Http\RequestInterface;
use Frigate\Routing\Http\ResponseInterface;
use Frigate\Helpers\Paths;
use Frigate\Helpers\Files;
use GuzzleHttp\Psr7\MimeType;

/**
 * StaticEndpoint
 * this endpoint class implementation is a pre-configured endpoint that serves static content
 */
class StaticEndpoint extends EndPoint { 

    use ContextHelpers;

    public function __construct(
        protected string $directory, 
        protected array $types = ["*/*"], 
        protected array $extensions = [],
        ?bool $debug = null
    ) {
        parent::__construct($debug);

        // Set the directory:
        $dir_path = realpath($directory);
        if (!$dir_path) {
            throw new \InvalidArgumentException("The directory does not exist: $directory"); //TODO: make this a proper exception
        }
        $this->directory = $dir_path;
    }

    /**
     * Initialize the mime types
     */
    protected function initMimeTypes() : void 
    {
        return;
    }

    protected function validatePath(string $path) : ?SplFileObject
    {   
        $path = Paths::join($this->directory, $path);
        $path = realpath(Paths::removePathRelative($path)) ?: "";
        if (strpos($path, realpath($this->directory)) !== 0) {
            return null;
        }
        return Files::file($path, 'rb', false);
    } 

    public function call(
        array $context, 
        RequestInterface $request, 
        ResponseInterface $response
    ) : ResponseInterface {

        //TODO:  Need to implement the following:
        // - handle range requests i.e. partial content headers e.g  "Content-Range: bytes 0-1023/1024"
        // - handle content disposition
        // - handle caching headers e.g. "Cache-Control: no-cache, no-store, must-revalidate"

        $file = $this->validatePath(
            $this->getFromContext("serve", $context) ?? ""
        );
        
        // Error if the path is empty:
        if (is_null($file)) {
            $response->setStatus(404);
            return $response;
        }

        //The file meta:
        $filename = $file->getFilename();
        $fileExtention = $file->getExtension();
        $mime = $fileExtention 
            ? (MimeType::fromExtension($fileExtention) ?? "application/octet-stream")
            : (MimeType::fromFilename($file->getRealPath()) ?? "application/octet-stream");
        $size = $file->getSize();

        $headers = [
            "Content-Length"                => $size,
            "Content-Type"                  => $mime,
            "Content-Disposition"           => 'inline'.($filename ? '; filename="'.$filename.'"' : '')
        ];

        
        return $response->setHeaders($headers)
                        ->setBody(fopen($file->getRealPath(), "rb"));
        // $range = $request->getHeader("Content-Range") ?? false;

        // //Set headers:
        // $header = [
        //     "Content-Length"                => $size,
        //     "Content-Type"                  => $mime,
        //     "Content-Disposition"           => 'inline'.($filename ? '; filename="'.$filename.'"' : ''),
        //     "Expires"                       => "-1",
        //     "Cache-Control"                 => "no-cache",
        //     "Cache-Control"                 => "public, must-revalidate, post-check=0, pre-check=0"
        // ];

        // if ($range) {
        //     $header["Content-Range"] = $request->getHeader("Content-Range");
        // }

        // return new Http\Response(
        //     status    : $range ?: 200,
        //     headers : $header,
        //     body    : fopen($file, "rb")
        // );


    }
}