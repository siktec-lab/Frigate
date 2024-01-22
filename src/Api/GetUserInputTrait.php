<?php

namespace Frigate\Api;

use Frigate\Routing\Http;

trait GetUserInputTrait
{
        
    /**
     * get_json_or_post
     * Helper function to get the json body or post data from the request with
     * default values
     * 
     * @param  RequestInterface $request The request object
     * @param  array $defaults The default values
     * @param  bool $only_default_keys If true only the keys in $defaults will be returned
     * @return array
     */
    public function get_json_or_post(Http\RequestInterface $request, array $defaults = [], bool $only_default_keys = false) : array {

        //Get user data - combination of post or json body:
        $data = $request->getPostData();
        $body = $request->getBodyAsString();
        $json = $request->getHeader("content-type") === "application/json" 
                ? @json_decode($body, true) ?? []
                : [];

        if (!$only_default_keys) {
            return array_merge_recursive($defaults, $data, $json ?: []);
        } else {
            $data = array_merge_recursive($data, $json ?: []);
            $result = [];
            foreach ($defaults as $key => $value) {
                $result[$key] = $data[$key] ?? $value;
            }
            return $result;
        }
    }

}