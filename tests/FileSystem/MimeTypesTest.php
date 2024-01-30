<?php

declare(strict_types=1);

namespace Frigate\Tests\FileSystem;

use PHPUnit\Framework\TestCase;
use Frigate\Tools\FileSystem\MimeTypes;

class MimeTypesTest extends TestCase
{

    protected MimeTypes $mime;

    protected function setUp() : void
    {
        $this->mime = new MimeTypes(
            mimes : [
                'json'  => ['application/json'],
                'jpeg'  => ['image/jpeg'],
                'jpg'   => ['image/jpeg'],
                'bar'   => ['foo', 'qux'],
                'baz'   => ['foo']
            ],
            extensions : [
                'application/json'  => ['json'],
                'image/jpeg'        => ['jpeg', 'jpg'],
                'foo'               => ['bar', 'baz'],
                'qux'               => ['bar']
            ]
        );
    }

    public static function checkMimeTypes() : array
    {
        return [
            ['application/json', 'json'],
            ['image/jpeg', 'jpeg'],
            ['image/jpeg', 'jpg'],
            ['foo', 'bar'],
            ['foo', 'baz']
        ];
    }

    public static function checkAllMimeTypes() : array
    {
        return [
            [['application/json'], 'json'],
            [['image/jpeg'], 'jpeg'],
            [['image/jpeg'], 'jpg'],
            [['foo', 'qux'], 'bar'],
            [['foo'], 'baz']
        ];
    }

    public static function checkExtensions() : array
    {
        return [
            ['json', 'application/json'],
            ['jpeg', 'image/jpeg'],
            ['bar', 'foo'],
            ['bar', 'qux']
        ];
    }

    public static function checkAllExtensions() : array
    {
        return [
            [['json'], 'application/json'],
            [['jpeg', 'jpg'], 'image/jpeg'],
            [['bar', 'baz'], 'foo'],
            [['bar'], 'qux']
        ];
    }

    /**
     * @dataProvider checkMimeTypes
     */
    public function testGetMimeType($expectedMimeType, $extension) : void
    {
        $this->assertEquals($expectedMimeType, $this->mime->getExtMimeType($extension));
    }

    /**
     * @dataProvider checkExtensions
     */
    public function testGetExtension($expectedExtension, $mimeType) : void
    {
        $this->assertEquals($expectedExtension, $this->mime->getMimeTypeExt($mimeType));
    }

    /**
     * @dataProvider checkAllMimeTypes
     */
    public function testGetAllMimeTypes($expectedMimeTypes, $extension) : void
    {
        $this->assertEquals($expectedMimeTypes, $this->mime->getExtMimeType($extension, true));
    }

    /**
     * @dataProvider checkAllExtensions
     */
    public function testGetAllExtensions($expectedExtensions, $mimeType) : void
    {
        $this->assertEquals($expectedExtensions, $this->mime->getMimeTypeExt($mimeType, true));
    }

    public function testGetMimeTypeUndefined() : void
    {
        $this->assertNull($this->mime->getExtMimeType('undefined'));
    }

    public function testGetExtensionUndefined() : void
    {
        $this->assertNull($this->mime->getMimeTypeExt('undefined'));
    }

    public function testGetAllMimeTypesUndefined() : void
    {
        $this->assertNull($this->mime->getExtMimeType('undefined', true));
    }

    public function testGetAllExtensionsUndefined() : void
    {
        $this->assertNull($this->mime->getMimeTypeExt('undefined', true));
    }

    public function testBuiltInMapping()
    {
        $mime = new MimeTypes();
        $this->assertEquals('json', $mime->getMimeTypeExt('application/json'));
        $this->assertEquals('application/json', $mime->getExtMimeType('json'));

        // Check with dots:
        $this->assertEquals('application/json', $mime->getExtMimeType('.json'));
    }
}