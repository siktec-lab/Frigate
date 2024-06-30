<?php

declare(strict_types=1);

namespace FrigateBin\App\Logic;

use Frigate\Helpers\Files;
use Frigate\Helpers\Strings;
use PHPUnit\Framework\Constraint\Count;

/**
 * A dictionary of MIME types and their associated file extensions.
 * Importing from: https://svn.apache.org/repos/asf/httpd/httpd/trunk/docs/conf/mime.types
 * For checking: https://www.iana.org/assignments/media-types/media-types.xhtml
 */
class MimeTypesBuilder
{

    public const EXTRA_MIMES = [
        '1km' => 'application/vnd.1000minds.decision-model+xml',
        '3gpp' => 'video/3gpp',
        '3mf' => 'model/3mf',
        '7zip' => 'application/x-7z-compressed',
        'ac3' => 'audio/ac3',
        'adts' => 'audio/aac',
        'age' => 'application/vnd.age',
        'aml' => 'application/automationml-aml+xml',
        'amlx' => 'application/automationml-amlx+zip',
        'amr' => 'audio/amr',
        'apng' => 'image/apng',
        'appinstaller' => 'application/appinstaller',
        'appx' => 'application/appx',
        'appxbundle' => 'application/appxbundle',
        'arj' => 'application/x-arj',
        'atomdeleted' => 'application/atomdeleted+xml',
        'avci' => 'image/avci',
        'avcs' => 'image/avcs',
        'avif' => 'image/avif',
        'azv' => 'image/vnd.airzip.accelerator.azv',
        'b16' => 'image/vnd.pco.b16',
        'bdoc' => 'application/x-bdoc',
        'bmml' => 'application/vnd.balsamiq.bmml+xml',
        'bpmn' => 'application/octet-stream',
        'bsp' => 'model/vnd.valve.source.compiled-map',
        'btf' => 'image/prs.btif',
        'buffer' => 'application/octet-stream',
        'cco' => 'application/x-cocoa',
        'cdfx' => 'application/cdfx+xml',
        'cdr' => 'application/cdr',
        'cjs' => 'application/node',
        'cld' => 'model/vnd.cld',
        'coffee' => 'text/coffeescript',
        'cpl' => 'application/cpl+xml',
        'crx' => 'application/x-chrome-extension',
        'csl' => 'application/vnd.citationstyles.style+xml',
        'csr' => 'application/octet-stream',
        'cwl' => 'application/cwl',
        'dbf' => 'application/vnd.dbf',
        'ddf' => 'application/vnd.syncml.dmddf+xml',
        'dds' => 'image/vnd.ms-dds',
        'dib' => 'image/bmp',
        'disposition-notification' => 'message/disposition-notification',
        'dmn' => 'application/octet-stream',
        'dpx' => 'image/dpx',
        'drle' => 'image/dicom-rle',
        'dwd' => 'application/atsc-dwd+xml',
        'ear' => 'application/java-archive',
        'emotionml' => 'application/emotionml+xml',
        'exp' => 'application/express',
        'exr' => 'image/aces',
        'fdt' => 'application/fdt+xml',
        'fits' => 'image/fits',
        'fo' => 'application/vnd.software602.filler.form+xml',
        'gdoc' => 'application/vnd.google-apps.document',
        'ged' => 'text/vnd.familysearch.gedcom',
        'geojson' => 'application/geo+json',
        'glb' => 'model/gltf-binary',
        'gltf' => 'model/gltf+json',
        'gpg' => 'application/gpg-keys',
        'gsheet' => 'application/vnd.google-apps.spreadsheet',
        'gslides' => 'application/vnd.google-apps.presentation',
        'gz' => 'application/gzip',
        'gzip' => 'application/gzip',
        'hbs' => 'text/x-handlebars-template',
        'hdd' => 'application/x-virtualbox-hdd',
        'heic' => 'image/heic',
        'heics' => 'image/heic-sequence',
        'heif' => 'image/heif',
        'heifs' => 'image/heif-sequence',
        'hej2' => 'image/hej2k',
        'held' => 'application/atsc-held+xml',
        'hjson' => 'application/hjson',
        'hsj2' => 'image/hsj2',
        'htc' => 'text/x-component',
        'img' => 'application/octet-stream',
        'ini' => 'text/plain',
        'its' => 'application/its+xml',
        'jade' => 'text/jade',
        'jardiff' => 'application/x-java-archive-diff',
        'jhc' => 'image/jphc',
        'jls' => 'image/jls',
        'jng' => 'image/x-jng',
        'jp2' => 'image/jp2',
        'jpf' => 'image/jpx',
        'jpg2' => 'image/jp2',
        'jph' => 'image/jph',
        'jpx' => 'image/jpx',
        'json5' => 'application/json5',
        'jsonld' => 'application/ld+json',
        'jsx' => 'text/jsx',
        'jt' => 'model/jt',
        'jxr' => 'image/jxr',
        'jxra' => 'image/jxra',
        'jxrs' => 'image/jxrs',
        'jxs' => 'image/jxs',
        'jxsc' => 'image/jxsc',
        'jxsi' => 'image/jxsi',
        'jxss' => 'image/jxss',
        'kdb' => 'application/octet-stream',
        'kdbx' => 'application/x-keepass2',
        'key' => 'application/x-iwork-keynote-sffkey',
        'ktx2' => 'image/ktx2',
        'less' => 'text/less',
        'lgr' => 'application/lgr+xml',
        'litcoffee' => 'text/coffeescript',
        'lua' => 'text/x-lua',
        'luac' => 'application/x-lua-bytecode',
        'm4p' => 'application/mp4',
        'm4s' => 'video/iso.segment',
        'maei' => 'application/mmt-aei+xml',
        'manifest' => 'text/cache-manifest',
        'map' => 'application/json',
        'markdown' => 'text/markdown',
        'md' => 'text/markdown',
        'mdx' => 'text/mdx',
        'mkd' => 'text/x-markdown',
        'mml' => 'text/mathml',
        'mpd' => 'application/dash+xml',
        'mpf' => 'application/media-policy-dataset+xml',
        'msg' => 'application/vnd.ms-outlook',
        'msix' => 'application/msix',
        'msixbundle' => 'application/msixbundle',
        'msm' => 'application/octet-stream',
        'msp' => 'application/octet-stream',
        'mtl' => 'model/mtl',
        'musd' => 'application/mmt-usd+xml',
        'mvt' => 'application/vnd.mapbox-vector-tile',
        'mxmf' => 'audio/mobile-xmf',
        'nq' => 'application/n-quads',
        'nt' => 'application/n-triples',
        'numbers' => 'application/x-iwork-numbers-sffnumbers',
        'obgx' => 'application/vnd.openblox.game+xml',
        'ogex' => 'model/vnd.opengex',
        'osm' => 'application/vnd.openstreetmap.data+xml',
        'ova' => 'application/x-virtualbox-ova',
        'ovf' => 'application/x-virtualbox-ovf',
        'owl' => 'application/rdf+xml',
        'p7a' => 'application/x-pkcs7-signature',
        'pac' => 'application/x-ns-proxy-autoconfig',
        'pages' => 'application/x-iwork-pages-sffpages',
        'pde' => 'text/x-processing',
        'pem' => 'application/x-x509-user-cert',
        'phar' => 'application/octet-stream',
        'php' => 'application/x-httpd-php',
        'php3' => 'application/x-httpd-php',
        'php4' => 'application/x-httpd-php',
        'phps' => 'application/x-httpd-php-source',
        'phtml' => 'application/x-httpd-php',
        'pkpass' => 'application/vnd.apple.pkpass',
        'pl' => 'application/x-perl',
        'pm' => 'application/x-perl',
        'ppa' => 'application/vnd.ms-powerpoint',
        'provx' => 'application/provenance+xml',
        'pti' => 'image/prs.pti',
        'pyo' => 'model/vnd.pytha.pyox',
        'pyox' => 'model/vnd.pytha.pyox',
        'raml' => 'application/raml+yaml',
        'rapd' => 'application/route-apd+xml',
        'relo' => 'application/p2p-overlay+xml',
        'rng' => 'application/xml',
        'rpm' => 'audio/x-pn-realaudio-plugin',
        'rsa' => 'application/x-pkcs7',
        'rsat' => 'application/atsc-rsat+xml',
        'rsheet' => 'application/urc-ressheet+xml',
        'run' => 'application/x-makeself',
        'rusd' => 'application/route-usd+xml',
        'rv' => 'video/vnd.rn-realvideo',
        'sass' => 'text/x-sass',
        'scss' => 'text/x-scss',
        'sea' => 'application/octet-stream',
        'senmlx' => 'application/senml+xml',
        'sensmlx' => 'application/sensml+xml',
        'shex' => 'text/shex',
        'shtml' => 'text/html',
        'sieve' => 'application/sieve',
        'siv' => 'application/sieve',
        'slim' => 'text/slim',
        'slm' => 'text/slim',
        'sls' => 'application/route-s-tsid+xml',
        'spdx' => 'text/spdx',
        'sst' => 'application/octet-stream',
        'step' => 'application/STEP',
        'stp' => 'application/STEP',
        'stpx' => 'model/step+xml',
        'stpxz' => 'model/step-xml+zip',
        'stpz' => 'model/step+zip',
        'styl' => 'text/stylus',
        'stylus' => 'text/stylus',
        'swidtag' => 'application/swid+xml',
        't38' => 'image/t38',
        'tap' => 'image/vnd.tencent.tap',
        'td' => 'application/urc-targetdesc+xml',
        'tfx' => 'image/tiff-fx',
        'tgz' => 'application/x-tar',
        'tk' => 'application/x-tcl',
        'toml' => 'application/toml',
        'trig' => 'application/trig',
        'tsx'  => 'text/typescript',
        'ttml' => 'application/ttml+xml',
        'u3d' => 'model/u3d',
        'u8dsn' => 'message/global-delivery-status',
        'u8hdr' => 'message/global-headers',
        'u8mdn' => 'message/global-disposition-notification',
        'u8msg' => 'message/global',
        'ubj' => 'application/ubjson',
        'uo' => 'application/vnd.uoml+xml',
        'usda' => 'model/vnd.usda',
        'usdz' => 'model/vnd.usdz+zip',
        'vbox' => 'application/x-virtualbox-vbox',
        'vbox-extpack' => 'application/x-virtualbox-vbox-extpack',
        'vdi' => 'application/x-virtualbox-vdi',
        'vds' => 'model/vnd.sap.vds',
        'vhd' => 'application/x-virtualbox-vhd',
        'vlc' => 'application/videolan',
        'vmdk' => 'application/x-virtualbox-vmdk',
        'vtf' => 'image/vnd.valve.source.texture',
        'vtt' => 'text/vtt',
        'wadl' => 'application/vnd.sun.wadl+xml',
        'war' => 'application/java-archive',
        'webapp' => 'application/x-web-app-manifest+json',
        'webmanifest' => 'application/manifest+json',
        'wgsl' => 'text/wgsl',
        'wif' => 'application/watcherinfo+xml',
        'word' => 'application/msword',
        'wsc' => 'message/vnd.wfa.wsc',
        'x_b' => 'model/vnd.parasolid.transmit.binary',
        'x_t' => 'model/vnd.parasolid.transmit.text',
        'xav' => 'application/xcap-att+xml',
        'xca' => 'application/xcap-caps+xml',
        'xcs' => 'application/calendar+xml',
        'xel' => 'application/xcap-el+xml',
        'xhtm' => 'application/vnd.pwg-xhtml-print+xml',
        'xl' => 'application/excel',
        'xns' => 'application/xcap-ns+xml',
        'xsd' => 'application/xml',
        'xsf' => 'application/prs.xsf+xml',
        'yaml' => 'text/yaml',
        'yml' => 'text/yaml',
        'ymp' => 'text/x-suse-ymp',
        'z' => 'application/x-compress',
        'zsh' => 'text/x-scriptzsh',
    ];
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
        $lines = Strings::splitLines($definition);
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
        foreach (self::EXTRA_MIMES as $ext => $mime) {

            // Add the mime type to the mapping:
            if (!isset($this->mapping['mimes'][$ext])) {
                $this->mapping['mimes'][$ext] = [];
            }
            $this->mapping['mimes'][$ext][] = $mime;

            // Add the extension to the mapping:
            if (!isset($this->mapping['extensions'][$mime])) {
                $this->mapping['extensions'][$mime] = [];
            }
            $this->mapping['extensions'][$mime][] = $ext;
        }
        ksort($this->mapping['mimes']);
        ksort($this->mapping['extensions']);
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
	 * @return array The lines of the inner part of the PHP array.
     */
	public function compile(string $prefix = '', string $suffix = '') : array
    {
        $mapping = $this->getMapping();
        $lines = [];
        foreach ($mapping['mimes'] as $ext => $mime) {
            $mime = $mime[0] ?? '';
            if (empty($mime)) {
                continue;
            }
            $lines[] = "$prefix'$ext' => '$mime',$suffix";
        }
        return $lines;
    }

	/**
	 * Save the current mapping to a file.
	 */
	public function save(string $path) : bool|string
	{
        // Compile the mapping:
        $compiled = $this->compile();
        if (empty($compiled)) {
            return 'Failed to compile the mime types mapping.';
        }

        // Read the source file:
        $content = Files::readFileContent($path);
        if (empty($content)) {
            return 'Failed to read the destination file.';
        }

        // Split the file into lines:
        $lines = Strings::splitLines($content);

        // Detect the line to replace to start the replacement:
        $start = -1;
        $end   = -1;
        $indent = '';
        foreach ($lines as $i => $line) {
            if ($start === -1 && strpos($line, 'protected static $EXT_MIME = [') !== false) {
                $start = $i;
                $indent = str_replace(ltrim($line), '', $line);
                continue;
            }
            if ($start !== -1 && strpos($line, '];') !== false) {
                $end = $i;
                break;
            }
        }
        if ($start === -1 || $end === -1) {
            return 'Failed to detect the start and end of the mime types mapping.';
        }

        //Apply indentation:
        $indent = str_repeat($indent, 2);
        $compiled = array_map(fn($line) => $indent . $line, $compiled);

        // Delete all lines between start and end:
        array_splice($lines, $start + 1, $end - $start - 1, $compiled);

        // Write the file:
		return Files::writeFile(
            $path, 
            implode("\n", $lines)
        ) ?: "Failed to write the destination file.";
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