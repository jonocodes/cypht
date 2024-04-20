<?php

/**
 * Output content
 * @package framework
 * @subpackage output
 */

/**
 * Base class that controls how data is output
 * @abstract
 */
abstract class Hm_Output {

    /**
     * Extended classes must override this method to output content
     * @param mixed $content data to output
     * @param array $headers headers to send
     * @return void
     */
    abstract protected function output_content($content, $headers);

    /**
     * Wrapper around extended class output_content() calls
     * @param mixed $response data to output
     * @param array $input raw module data
     * @return void
     */
    public function send_response($response, $input=array()) {
        if (array_key_exists('http_headers', $input)) {
            $this->output_content($response, $input['http_headers']);
        }
        else {
            $this->output_content($response, array());
        }
    }
}

/**
 * Output request responses using HTTP
 */
class Hm_Output_HTTP extends Hm_Output {

    /**
     * Send HTTP headers
     * @param array $headers headers to send
     * @return void
     */
    protected function output_headers($headers) {
        foreach ($headers as $name => $value) {
            Hm_Functions::header($name.': '.$value);
        }
    }

    /**
     * Send response content to the browser
     * @param mixed $content data to send
     * @param array $headers HTTP headers to set
     * @return void
     */
    protected function output_content($content, $headers=array()) {
        $this->output_headers($headers);
        ob_end_clean();
        echo $content;
    }
}

/**
 * Message list struct used for user notices and system debug
 */
trait Hm_List {

    /* message list */
    private static $msgs = array();

    /**
     * Add a message
     * @param string $string message to add
     * @return void
     */
    public static function add($string) {
        self::$msgs[] = self::str($string, false);
    }

    /**
     * Return all messages
     * @return array all messages
     */
    public static function get() {
        return self::$msgs;
    }

    /**
     * Flush all messages
     * @return null
     */
    public static function flush() {
        self::$msgs = array();
    }

    /**
     * Stringify a value
     * @param mixed $mixed value to stringify
     * @return string
     */
    public static function str($mixed, $return_type=true) {
        $type = gettype($mixed);
        if (in_array($type, array('array', 'object'), true)) {
            $str = print_r($mixed, true);
        }
        elseif ($return_type) {
            $str = sprintf("%s: %s", $type, $mixed);
        }
        else {
            $str = (string) $mixed;
        }
        return $str;
    }

    /**
     * Log all messages
     * @return bool
     */
    public static function show() {
        return Hm_Functions::error_log(print_r(self::$msgs, true));
    }
}

/**
 * Notices the user sees
 */
class Hm_Msgs { use Hm_List; }

/**
 * System debug notices
 */
class Hm_Debug {

    use Hm_List;

    /**
     * Add page execution stats to the Hm_Debug list
     * @return void
     */
    public static function load_page_stats() {
        self::add(sprintf("PHP version %s", phpversion()));
        self::add(sprintf("Zend version %s", zend_version()));
        self::add(sprintf("Peak Memory: %d", (memory_get_peak_usage(true)/1024)));
        self::add(sprintf("PID: %d", getmypid()));
        self::add(sprintf("Included files: %d", count(get_included_files())));
    }
}

/**
 * Easy to use error logging
 * @param mixed $mixed vaule to send to the log
 * @return boolean|null
 */
function elog($mixed) {
    if (DEBUG_MODE) {
        $bt = debug_backtrace();
        $caller = array_shift($bt);
        Hm_Debug::add(sprintf('ELOG called in %s at line %d', $caller['file'], $caller['line']));
        return Hm_Functions::error_log(Hm_Debug::str($mixed));
    }
}
