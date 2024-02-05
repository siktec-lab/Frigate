<?php

declare(strict_types=1);

namespace Frigate\Helpers;

use Frigate\Helpers\Files;

/**
 * A dictionary of MIME types and their associated file extensions.
 */
class MimeTypesFileBuilder
{

	protected $mapping;

	/**
	 * Create a new mapping builder.
	 */
	public function __construct(array $mimes = [], array $extensions = [])
    {
        $this->mapping = array(
            'extensions' => $extensions,
            'mimes'      => $mimes
        );
    }

    /**
     * Parse a mime.types file.
     * Compatible with the format used by Apache and Nginx.
     * ref: https://svn.apache.org/repos/asf/httpd/httpd/trunk/docs/conf/mime.types
     * ref: https://httpd.apache.org/docs/current/programs/httpd.html
    */
    protected function parseDefinition($definition) : array
    {
        $lines = explode("\n", $definition);
        $mimes = [];
        $extensions = [];
        foreach ($lines as $line) {
            $line = trim($line);
            if (empty($line) || $line[0] === '#') {
                continue;
            }
            $parts = preg_split('/\s+/', $line);
            $mime = array_shift($parts);
            foreach ($parts as $ext) {
                $ext = ltrim($ext, '.');
                $mimes[$ext][] = $mime;
                $extensions[$mime][] = $ext;
            }
        }
        return [$mimes, $extensions];
    }

    /**
     * Load a mime.types string definition.
     * Compatible with the format used by Apache and Nginx.
     * ref: https://svn.apache.org/repos/asf/httpd/httpd/trunk/docs/conf/mime.types
     * ref: https://httpd.apache.org/docs/current/programs/httpd.html
    */
    public function loadDefinition(string $definition) : void
    {
        [$mimes, $extensions] = $this->parseDefinition($definition);
        $this->mapping['mimes'] = $mimes;
        $this->mapping['extensions'] = $extensions;
    }

	/**
	 * @return array The mapping.
	 */
	public function getMapping() : array
    {
        return $this->mapping;
    }

	/**
	 * Compile the current mapping to PHP.
	 *
	 * @return string The compiled PHP code to save to a file.
	 */
	public function compile() : string
    {
        $mapping = $this->getMapping();
        $mapping_export = var_export($mapping, true);
        return "<?php return $mapping_export;";
    }

	/**
	 * Save the current mapping to a file.
	 */
	public function save(string $path) : bool
	{
		return Files::writeFile($path, $this->compile());
	}

	/**
	 * Create a new mapping builder that has no types defined.
	 *
	 * @return MimeMappingBuilder A mapping builder with no types defined.
	 */
	public static function blank()
	{
		return new self(array('mimes' => array(), 'extensions' => array()));
	}
}