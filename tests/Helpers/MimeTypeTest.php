<?php

declare(strict_types=1);

namespace Frigate\Tests\Helpers;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes as Attr;
use Frigate\Helpers\MimeType;
use Frigate\Helpers\MimeTypes;

#[
    Attr\Group('helpers'), 
    Attr\Group('helpers-mime')
]
class MimeTypeTest extends TestCase
{

    protected function setUp() : void
    {
        MimeType::setMimeTypes([
            "ts"        => "text/typescript",
            "tsx"       => "text/typescript",
            "o"         => "application/octet-stream",
            "custom"    => "frigate/custom",
            "frigate"   => "frigate/native",
        ]);
        return;
    }

    public function tearDown() : void
    {
        return;
    }

    public static function getSampleMimeTypes() : array
    {
        return ["mixed ext -> mimes set" => [[
            "json"      => "application/json",
            "jpeg"      => "image/jpeg",
            "jpg"       => "image/jpeg",
            "ico"       => "image/x-icon",
            "bmp"       => "image/bmp",
            "gif"       => "image/gif",
            "webp"      => "image/webp",
            "png"       => "image/png",
            "svg"       => "image/svg+xml",
            "xml"       => "application/xml",
            "txt"       => "text/plain",
            "html"      => "text/html",
            "css"       => "text/css",
            "js"        => "text/javascript",
            "ts"        => "text/typescript",
            "wasm"      => "application/wasm",
            "scss"      => "text/x-scss",
            "md"        => "text/markdown",
            "mp3"       => "audio/mpeg",
            "mp4"       => "video/mp4",
            "o"         => "application/octet-stream",
            "exe"       => "application/x-msdownload",
            "unknown"   => "text/plain"
        ]]];
    }

    // #[Attr\Group('current')]
    #[Attr\DataProvider('getSampleMimeTypes')]
    public function testGetMimeType(array $data) : void
    {
        foreach ($data as $ext => $mime) {
            $this->assertEquals($mime, MimeType::fromExtension($ext, "text/plain"));
        }
    }

    // #[Attr\Group('current')]
    public function testExpandWildCard() : void
    {   
        // All the text types:
        $text_group = [];
        foreach (MimeType::getExtensionDictionary() as $mime) {
            if (str_starts_with($mime, "text/")) {
                $text_group[$mime] = null;
            }
        }
        $text_group = array_keys($text_group);
        $get_text = MimeType::expandWildCard("text/*");
        $this->assertEquals(count($text_group), count($get_text));
        $this->assertEquals($text_group, $get_text);
        
        // Check all mimes:
        $all = array_keys(array_flip(MimeType::getExtensionDictionary()));
        $all_mimes = MimeType::expandWildCard("*/*");
        $this->assertEquals(count($all), count($all_mimes));
        $this->assertEquals($all, $all_mimes);

        //Not a group:
        $this->assertEquals(["text/plain"], MimeType::expandWildCard("text/plain"));
        
        //Not found:
        $this->assertEquals([], MimeType::expandWildCard("text/unknown"));
        
        //Not found:
        $this->assertEquals([], MimeType::expandWildCard("unknown/*"));

        //Not found with default:
        $this->assertEquals(["text/plain"], MimeType::expandWildCard("unknown/*", ["text/plain"]));
        $this->assertEquals(["text/plain"], MimeType::expandWildCard("unknown/*", "text/plain"));
        
        //Not found with default:
        $this->assertEquals(["text/plain"], MimeType::expandWildCard("text/unknown", ["text/plain"]));
        $this->assertEquals(["text/plain"], MimeType::expandWildCard("text/unknown", "text/plain"));
    }
    
    // #[Attr\Group('current')]
    public function testGetMimeFromFile() : void
    {
        $this->assertEquals("application/x-httpd-php", MimeType::fromFilename(__FILE__));
        $this->assertEquals(MimeType::fromExtension("php"), MimeType::fromFilename(__FILE__));
        $this->assertEquals(MimeType::fromExtension("jpg"), MimeType::fromFilename("/folder/root/imaginary.jpg"));
        $this->assertNull(MimeType::fromFilename("unknown"));
        $this->assertEquals("text/plain", MimeType::fromFilename("unknown", "text/plain"));
    }
    
    // #[Attr\Group('current')]
    public function testGetMimeTypeExtensions() : void
    {
        // Json:
        $ext_json = MimeType::mimeTypeExtensions("application/json");
        $this->assertEquals(["json", "map"], $ext_json);
        
        // Video:
        $video_group = [];
        foreach (MimeType::getExtensionDictionary() as $ext => $mime) {
            if (str_starts_with($mime, "video/")) {
                $video_group[] = $ext;
            }
        }
        $video_group = array_flip(array_flip($video_group));
        $ext_video = MimeType::mimeTypeExtensions("video/*");
        $this->assertEquals(count($video_group), count($ext_video));
        
        //Unknown:
        $this->assertEquals([], MimeType::mimeTypeExtensions("unknown"));
    }

    // #[Attr\Group('current')]
    public function testMimeTypesFromQuery() : void
    {
        $q = implode(',', [
            "text/html",
            "application/xhtml+xml",
            "frigate/*;q=0.9", // Wildcard + added by us in the test setup
            "application/xml;q=0.8",
            "image/avif",
            "image/webp",
            "image/apng",
            "*/*;q=0.7", // Wildcard will never be expanded
            "application/signed-exchange;v=b3;q=0.6" // Not a native mime
        ]);

        $expected_mimes = [
            "text/html",
            "application/xhtml+xml",
            "image/avif",
            "image/webp",
            "image/apng",
            "frigate/*",
            "application/xml",
            "*/*"
        ];

        $expected_mimes_expanded = [
            "text/html",
            "application/xhtml+xml",
            "image/avif",
            "image/webp",
            "image/apng",
            "frigate/custom",
            "frigate/native",
            "application/xml",
            "*/*"
        ];

        $get_mimes = MimeType::fromQuery($q);
        $get_mimes_expanded = MimeType::fromQuery($q, true);
        $this->assertEqualsCanonicalizing($expected_mimes, $get_mimes);
        $this->assertEqualsCanonicalizing($expected_mimes_expanded, $get_mimes_expanded);
    }

    public static function setUpBeforeClass() : void
    {
        return;
    }

    public static function tearDownAfterClass() : void
    {
        return;
    }
}