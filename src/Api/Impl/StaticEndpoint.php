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
use Frigate\Helpers\MimeType;

/**
 * StaticEndpoint
 * this endpoint class implementation is a pre-configured endpoint that serves static content
 */
class StaticEndpoint extends EndPoint { 

    use ContextHelpers;

    protected array $types = ["*/*" => "inline"];

    public const DISPOSE_INLINE     = "inline";    
    public const DISPOSE_ATTACHMENT = "attachment";

    public function __construct(
        protected string $directory, 
        array $types = ["*/*" => "inline"],
        ?bool $debug = null
    ) {
        parent::__construct($debug);

        // Initialize the mime types:
        $this->initMimeTypes($types);

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
    protected function initMimeTypes(array $types) : void 
    {
        if (empty($types)) {
            return;
        }
        foreach ($types as $type => $disposition) {
            $mime = is_string($type) ? $type : $disposition;
            $disp = is_string($type) ? $disposition : "inline";
            
            // If the mime type is an extension:
            if ($mime == "*/*" || MimeType::isExtension($mime)) {
                $mime = MimeType::fromExtension($mime);
            } elseif (!MimeType::isMimeType($mime)) {
                continue;
            }

            //Validate the disposition:
            if ($disp !== self::DISPOSE_INLINE && $disp !== self::DISPOSE_ATTACHMENT) {
                continue;
            }

            // Save it:
            $this->types[$mime] = $disp;
        }
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

        // Is supported?
        $disposition = $this->types[$mime] ?? "inline";

        // Check if the file
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