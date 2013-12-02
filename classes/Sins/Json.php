<?php

namespace Sins;

/**
 * Decode from json with options and error handling.
**/
class Json
{
    /**
     * Decodes json hashes to associative arrays (rather than objects).
    **/
    public $decodeAssoc = true;

    /**
     * Array of default options for json_decode - these have PHP constant names
     * as keys, although only JSON_BIGINT_AS_STRING is implemented in 2013.
    **/
    protected $decodeDefaults = array(
        'JSON_BIGINT_AS_STRING' => true,
    );

    /**
     * Depth parameter for json_decode - this must be set to a value because
     * of the ordering of arguments in json_decode().
    **/
    public $decodeDepth = 512;

    /**
     * Array of options for json_decode - see $defaults.
    **/
    public $decodeOptions = array();

    /**
     * Array of default options for json_encode - these have PHP constant names
     * as keys - some examples shown below (these two are useful for displaying
     * JSON as HTML).
    **/
    protected $encodeDefaults = array(
        // 'JSON_PRETTY_PRINT' => true,
        // 'JSON_HEX_TAG'      => true,
    );

    /**
     * Depth parameter for json_encode.
    **/
    public $encodeDepth = null;

    /**
     * Array of options for json_encode - see $defaults.
    **/
    public $encodeOptions = array();

    /**
     * If true, options that don't exist throw an exception, otherwise they are ignored.
    **/
    public $strict = false;

    /**
     * Decode from json with options and error handling.
    **/
    public function decode($body)
    {
        $bitmask = 0;
        $settings = array_merge($this->decodeDefaults, $this->decodeOptions);
        foreach ($settings as $option => $value) {
            if ($value && defined($option)) {
                $bitmask = $bitmask | constant($option);
            } elseif ($value && $this->strict) {
                throw new \Exception(strtr(
                    'Constant :const not available in PHP :ver',
                     array(':const' => $option, ':ver' => phpversion())
                ));
            }
        }
        $decoded = json_decode($body, $this->decodeAssoc, $this->decodeDepth, $bitmask);
        if (json_last_error === JSON_ERROR_NONE) {
            return $decoded;
        }
        throw new Exception(strtr(
            'Json decoding error. :msg',
            array(':msg' => json_last_error())
        ));
    }

    /**
     * Encode into json with options and error handling.
    **/
    public function encode($body)
    {
        $bitmask = 0;
        $settings = array_merge($this->encodeDefaults, $this->encodeOptions);

        foreach ($settings as $option => $value) {
            if ($value && defined($option)) {
                $bitmask = $bitmask | constant($option);
            } elseif ($value && $this->strict) {
                throw new \Exception(strtr(
                    'Constant :const not available in PHP :ver',
                    array(':const' => $option, ':ver' => phpversion())
                ));
            }
        }

        // get the encoded string
        if ($this->encodeDepth === null) {
            $encoded = json_encode($body, $bitmask);
        } else {
            $encoded = json_encode($body, $bitmask, $this->encodeDepth);
        }

        // if there was no error return the encoded string
        if (json_last_error() === JSON_ERROR_NONE) {
            return $encoded;
        }
        
        // fail if there was an error
        throw new \Exception(strtr(
            'Json encoding error. :msg',
            array(':msg' => json_last_error())
        ));
    }
}
