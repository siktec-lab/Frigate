<?php 

namespace Frigate\Routing\Files;

use Frigate\Tools\Hashing;
use Frigate\Tools\Arrays\ArrayHelpers;
use Frigate\Tools\FileSystem\DirectoryHelper;
use Frigate\Tools\FileSystem\FilesHelper;

class Upload {

    private string $id;
    private $file = null;
    private array $chunks = [];
    private array $variants = [];
    private array $metadata = [];
    
    public function __construct(?string $id = null) {
        $this->id = $id ?? Hashing\UUID::v4();
    }

    public function get_id() : ?string {
        return $this->id;
    }

    public function get_metadata() : array {
        return $this->metadata;
    }

    public function get_chunks() : array {
        return $this->chunks;
    }

    public function get_files($mutator = null) {
        if ($this->file === null) 
            return null;
        $files = array_merge(isset($this->file) ? [$this->file] : [], $this->variants);
        return $mutator === null ? $files : call_user_func($mutator, $files, $this->metadata);
    }
        
    /**
     * restore
     * restore a transfer from a serialized state
     * 
     * @param  mixed $file
     * @param  array $variants
     * @param  array $chunks
     * @param  array $metadata
     * @return void
     */
    public function restore($file, array $variants = [], array $chunks = [], array $metadata = []) : void {
        $this->file     = $file;
        $this->variants = $variants;
        $this->chunks   = $chunks;
        $this->metadata = $metadata;
    }
    
    /**
     * populate
     *
     * @param  string $entry -> the files array key name (e.g. 'files')
     * @return void
     */
    public function populate(string $entry) : void {

        $files = isset($_FILES[$entry]) ? $this->to_array_of_files($_FILES[$entry]) : null;
        $metadata = isset($_POST[$entry]) ? ArrayHelpers::to_array($_POST[$entry]) : [];

        // parse metadata
        if (count($metadata)) {
            $this->metadata = @json_decode($metadata[0], true);
        }

        // no files
        if ($files === null) 
            return;

        // files should always be available, first file is always the main file
        $this->file = $files[0];
        
        // if variants submitted, set to variants array
        $this->variants = array_slice($files, 1);
    }

    private function to_array_of_files($value) : array {
        if (is_array($value['tmp_name'])) {
            $results = [];
            foreach($value['tmp_name'] as $index => $tmpName ) {
                $file = [
                    'tmp_name' => $value['tmp_name'][$index],
                    'name' => $value['name'][$index],
                    'size' => $value['size'][$index],
                    'error' => $value['error'][$index],
                    'type' => $value['type'][$index]
                ];
                array_push( $results, $file );
            }
            return $results;
        }
        return ArrayHelpers::to_array($value);
    }
    
    /**
     * store_in_temp
     *
     * @param  string $temp_folder
     * @return array [boolean, message]
     */
    public function store_in_temp(string $temp_folder) : array {
        $file_temp_folder = $temp_folder . '/' . $this->id;

        //Create temp folder:
        $create_folder = DirectoryHelper::create_secure_directory($file_temp_folder, 0755, true);
        if (!$create_folder) {
            return [false, "Could not create temp folder"];
        }

        //Write meta data file to temp folder:
        $meta_data_file = $file_temp_folder . '/meta.json';
        $meta_data = json_encode($this->get_metadata());
        FilesHelper::writeFile($meta_data_file, $meta_data);
        
        $files = $this->get_files();
        //If its a chunked upload, stop here:
        if ($files === null) {
            return [true, "Ready for chunks upload"];
        }

        //Write main file to temp folder:
        $file = $files[0];
        self::move_file($file, $file_temp_folder);

        // store variants
        if (count($files) > 1) {
            $files = array_slice($files, 1);
            $variants_folder = $file_temp_folder . DIRECTORY_SEPARATOR . "variants";
            $create_folder = DirectoryHelper::create_secure_directory($variants_folder, 0755, true);
            foreach($files as $file) {
                self::move_file($file, $variants_folder);
            }
        }
        return [true, "Uploaded to temp folder"];
    }

    public static function head_temp_file(string $temp_folder, null|string $key) {
        
        $file_temp_folder = $temp_folder . '/' . $key;

        if (!Hashing\UUID::is_valid($key ?? "")) {
            return [false, "Invalid key"];
        }

        $patches = glob($file_temp_folder . '.patch.*');
        $offsets = array();
        $size = '';
        $last_offset = 0;
        foreach ($patches as $patch) {
            // get size of chunk
            $size = filesize($patch);
            // get offset of chunk
            $offset = explode('.patch.', $patch, 2)[1];
            // offsets
            array_push($offsets, intval($offset));
        }
        sort($offsets);
        foreach ($offsets as $offset) {
            // test if is missing previous chunk
            // don't test first chunk (previous chunk is non existent)
            if ($offset > 0 && !in_array($offset - $size, $offsets)) {
                $last_offset = $offset - $size;
                break;
            }
            // last offset is at least next offset
            $last_offset = $offset + $size;
        }

        return [true, $last_offset];

    }
    public static function patch_temp_file(
        string $temp_folder,
        null|string|int $offset, 
        null|string|int $length, 
        null|string $name,
        null|string $key
    ) : array {

        // should be numeric values, else exit and required name + key
        $name = self::sanitize_filename($name);
        $file_temp_folder = $temp_folder . '/' . $key;

        if (
            !is_numeric($offset) || 
            !is_numeric($length) || 
            empty($name) || 
            !Hashing\UUID::is_valid($key ?? "")
        ) {
            return [false, "missing or invalid headers - offset: $offset, length: $length, name: $name, key: $key"];
        }

        // check if directory still exists:
        if ( !is_dir($file_temp_folder) ) {
            return [false, "Temp folder does not exist - temp: $file_temp_folder"];
        }

        // write patch file for this request
        file_put_contents( $file_temp_folder.'/.patch.'. $offset, fopen('php://input', 'r'));
        
        // calculate total size of patches
        $current_size = 0;
        $patches = glob($file_temp_folder . '/.patch.*');
        foreach ($patches as $patch) {
            $current_size += filesize($patch);
        }

        // if total size equals length of file we have gathered all patch files
        if ($current_size == $length) {

            // create output file
            $final_file = fopen($file_temp_folder.'/'.$name, 'w');

            // write patches to file
            foreach ($patches as $patch) {

                // get offset from filename
                $offset = explode('.patch.', $patch, 2)[1];

                // read patch and close
                $patch_handle = fopen($patch, 'r');
                $patch_contents = fread($patch_handle, filesize($patch));
                fclose($patch_handle); 
                
                // apply patch
                fseek($final_file, $offset);
                fwrite($final_file, $patch_contents);
            }

            // remove patches
            foreach ($patches as $patch) {
                unlink($patch);
            }

            // done with file
            fclose($final_file);
        }
        return [true, "done"];
    }



    public static function delete_temp_file(string $temp_folder, string $key) : array {

        if (!Hashing\UUID::is_valid($key)) {
            return [false, "invalid key"];
        }

        $file_temp_folder = $temp_folder . '/' . $key;
        DirectoryHelper::remove_directory($file_temp_folder.'/variants');
        DirectoryHelper::remove_directory($file_temp_folder);
        return [ true, "temp file deleted" ];
    }


    public static function save_file_key(string $key, string $name, string $temp_folder, string $storage_folder) : array {
        $key = trim($key);
        $name = self::sanitize_filename($name);
        $temp_file_folder = $temp_folder . '/' . $key;
        $temp_file        = $temp_file_folder.'/'.$name;

        $return  = [
            "success"   => false,
            "message"   => "Could not save file",
            "filename"  => $name,
            "file_path" => null,
            "mime_type" => null,
            "file_size" => null
        ];

        //check if key is valid:
        if (!Hashing\UUID::is_valid($key)) {
            $return["message"] = "Invalid key";
            return $return;
        }

        //check if temp folder exists:
        if (!is_dir($temp_file_folder) || !is_file($temp_file) || !is_dir($storage_folder) ) {
            $return["message"] = "Temp file or folder does not exist";
            return $return;
        }

        //set data: 
        $return["mime_type"] = mime_content_type($temp_file) ?: "application/octet-stream";
        $return["file_size"] = filesize($temp_file);

        //rename:
        $info = pathinfo($temp_file);
        $key_name = $key.'.'.$info['extension'];

        //Move file to storage folder:
        $storage_file = $storage_folder.'/'.$key_name;
        $return["file_path"] = $storage_file;
        if (!rename($temp_file, $storage_file)) {
            return $return;
        }
        
        $return["success"] = true;
        $return["message"] = "File saved";

        //remove temp folder:
        self::delete_temp_file($temp_folder, $key);

        return $return;
    }

    public static function move_temp_file($file, $path) {
        move_uploaded_file($file['tmp_name'], $path . DIRECTORY_SEPARATOR . self::sanitize_filename($file['name']));
    }
    
    public static function move_file($file, $path) {
        if (is_uploaded_file($file['tmp_name'])) {
            return self::move_temp_file($file, $path);
        }
        return rename($file['tmp_name'], $path . DIRECTORY_SEPARATOR . self::sanitize_filename($file['name']));
    }

    public static function sanitize_filename($filename) {
        $info = pathinfo($filename);
        $name = self::sanitize_filename_part($info['filename']);
        $extension = self::sanitize_filename_part($info['extension']);
        return (strlen($name) > 0 ? $name : '_') . '.' . $extension;
    }
    
    public static function sanitize_filename_part($str) {
        return preg_replace("/[^a-zA-Z0-9\_\s]/", "", $str);
    }

}