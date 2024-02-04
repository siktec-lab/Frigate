<?php

declare(strict_types=1);

namespace Frigate\Tools\MimeTypes;

/**
 * A dictionary of MIME types and their associated file extensions.
 */
class MimeHelper
{

    private const BUILT_IN_FILE = __DIR__ . '/mime.types.php';

	/** The cached built-in mapping array. */
	private static array $built_in = [];

	/** The mapping array. */
	protected array $mapping;

	/**
	 * Create a new mime types instance with the given mappings.
	 *
	 * If no mappings are defined, they will default to the ones included with this package.
	 *
	 * @param array $mapping An associative array containing two entries.
	 * Entry "mimes" being an associative array of extension to array of MIME types.
	 * Entry "extensions" being an associative array of MIME type to array of extensions.
	 * Example:
	 * <code>
	 * array(
	 *   'extensions' => array(
	 *     'application/json' => array('json'),
	 *     'image/jpeg'       => array('jpg', 'jpeg'),
	 *     ...
	 *   ),
	 *   'mimes' => array(
	 *     'json' => array('application/json'),
	 *     'jpeg' => array('image/jpeg'),
	 *     ...
	 *   )
	 * )
	 * </code>
	 */
	public function __construct(array $mimes = null, array $extensions = null)
    {
        if ($mimes === null && $extensions === null) {
            $this->mapping = self::getBuiltIn();
        } else {
			$this->mapping = array(
				'extensions' => $extensions ?? [],
				'mimes'      => $mimes ?? []
			);
		}
    }
        

	/**
	 * Get the MIME type of the given file extension.
	 */
	public function getMimeTypeOf(string $extension, bool $all = false) : string|array|null
	{
		$extension = self::normalize($extension);
		if (!empty($this->mapping['mimes'][$extension])) {
			return $all ? $this->mapping['mimes'][$extension] : $this->mapping['mimes'][$extension][0];
		}
		return null;
	}

	/**
	 * Get the file extension of the given MIME type.
	 */
	public function getExtensionOf(string $mime_type, bool $all = false) : string|array|null
    {
        $mime_type = self::normalize($mime_type);
        if (!empty($this->mapping['extensions'][$mime_type])) {
            return $all ? $this->mapping['extensions'][$mime_type] : $this->mapping['extensions'][$mime_type][0];
        }
        return null;
    }

	/**
	 * Add a conversion.
	 */
	public function add(
        string $mime, 
        string $extension, 
        bool   $default_extension = true,
        bool   $default_mime = true
    ) : self {
		
		$mime = self::normalize($mime);
		$extension = self::normalize($extension);
		
		// Set the container if it doesn't exist:
		if (!array_key_exists($mime, $this->mapping['extensions'][$mime])) {
            $this->mapping['extensions'][$mime] = [];
        }
		if (!array_key_exists($extension, $this->mapping['mimes'][$extension])) {
            $this->mapping['mimes'][$extension] = [];
        }
		// Append or prepend the new value:
		if ($default_extension) {
			array_unshift($this->mapping['extensions'][$mime], $extension);
		} else {
			$this->mapping['extensions'][$mime][] = $extension;
		}
		if ($default_mime) {
			array_unshift($this->mapping['mimes'][$extension], $mime);
		} else {
			$this->mapping['mimes'][$extension][] = $mime;
		}
		return $this;
	}

	/**
	 * Get the built-in mapping.
	 */
	protected static function getBuiltIn() : array
	{
		if (empty(self::$built_in)) {
			self::$built_in = require(self::BUILT_IN_FILE);
		}
		return self::$built_in;
	}

	/**
	 * Normalize the input string using lowercase/trim and remove leading dots.
	 */
	private static function normalize($input) : string
	{
		return ltrim(strtolower(trim($input)), '.');
	}
}