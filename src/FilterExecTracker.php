<?php


namespace NovemBit\wp\plugins\RuntimeDebugger;


use Closure;
use ReflectionClass;
use ReflectionException;
use ReflectionFunction;

class FilterExecTracker
{
    /**
     * @var FilterExecTracker
     */
    protected static $instance = null;
    public static function get_instance()
    {
        if (null === static::$instance ) {
            static::$instance = new static();
        }

        return static::$instance;
    }
    protected $file;

    /**
     * Get file to write to.
     */
    protected function __construct()
    {
        if (! isset($_REQUEST['track_hooks']) ) {
            return;
        }
        if (! defined('FILE_TO_LOG') ) {
            die;
        }
        $this->file = FILE_TO_LOG;
        $this->write_columns_row();

    }
    protected function __clone()
    {
    }
    protected function __wakeup()
    {
    }

    /**
     * Write initial row to file.
     */
    protected function write_columns_row()
    {
        fputcsv(
            $this->file,
            array(
                'ID',
                't',
                'diff',
                'h',
                'c',
                'comp',
                'E',
                'T',
            )
        );
    }

    protected $disabled = false;

    public $request_id = -1;

    public function track_callback_start( $type, $filter_name, $callback )
    {
        if (true === $this->disabled || ! isset($_REQUEST['track_hooks']) ) {
            return;
        }
        $this->request_id += 1;
        $this->disabled    = true;
        $state_tracker     = SystemStateTracker::get_instance();
        $state_tracker->start($this->request_id);
        $callback_info = $this->get_callback_info($callback);
        $component     = $this->get_component($callback_info['file']);
        fputcsv(
            $this->file,
            array(
                'ID'   => $this->request_id,
                't'    => number_format(microtime(true), 6, '.', ''),
                'diff' => '',
                'h'    => $filter_name,
                'c'    => $callback_info['name'],
                'comp' => $component,
                'E'    => 'S',
                'T'    => $type,
            )
        );
        $this->disabled = false;
    }

    public function track_callback_end( $id, $type, $filter_name, $callback )
    {
        if (true === $this->disabled || ! isset($_REQUEST['track_hooks']) ) {
            return;
        }
        $this->disabled = true;

        $state_tracker = SystemStateTracker::get_instance();
        $time          = $state_tracker->get_time_elapsed($id);
        $callback_info = $this->get_callback_info($callback);
        $component     = $this->get_component($callback_info['file']);
        fputcsv(
            $this->file,
            array(
                'ID'   => $id,
                't'    => number_format(microtime(true), 6, '.', ''),
                'diff' => number_format($time * 1000, 6, '.', ''),
                'h'    => $filter_name,
                'c'    => $callback_info['name'],
                'comp' => $component,
                'E'    => 'F',
                'T'    => $type,
            )
        );
        $this->disabled = false;
    }

    public function get_callback_info( $callback )
    {
        $result = array();
        if (is_string($callback) ) {
            $callback_str   = trim($callback);
            $result['file'] = '';
            if (strlen($callback_str) > 0 ) {
                if (false !== strpos($callback_str, '::') ) {
                    $frame = explode('::', $callback_str);
                    if (! class_exists($frame[0], false) && ! method_exists($frame[0], $frame[1]) ) {
                        $ref = null;
                    } else {
                        try {
                            $callback = $frame[0];
                            $ref      = new ReflectionClass($callback);
                        } catch ( ReflectionException $e ) {
                            $callback = $frame[1];
                            try {

                                $ref = new ReflectionFunction($callback);
                            } catch ( ReflectionException $e ) {
                                $ref = null;
                            }
                        }
                    }
                } else {
                    try {
                        if(class_exists($callback_str, false)) {
                            $ref = new ReflectionClass(trim($callback_str));

                        } else if(function_exists($callback_str)) {
                            $ref = new ReflectionFunction($callback_str);
                        } else {
                            $ref = null;
                        }
                    } catch ( ReflectionException $e ) {
                        $ref = null;
                    }
                }
                $result['file'] = $ref === null ? null : $ref->getFileName();
            }
            $result['name'] = $callback;
            return $result;
        } elseif (is_array($callback) ) {
            if (is_object($callback[0]) ) {
                $result['name'] = sprintf('%s::%s', get_class($callback[0]), trim($callback[1]));
                try {
                    $ref = new ReflectionClass($callback[0]);
                } catch ( ReflectionException $e ) {
                    $ref = null;
                }

                $result['file'] = $ref->getFileName();
                return $result;
            } else {
                $result['name'] = sprintf('%s::%s', trim($callback[0]), trim($callback[1]));
                try {
                    $ref = new ReflectionClass(trim($callback[0]));
                } catch ( ReflectionException $e ) {
                    $ref = null;
                }

                $result['file'] = $ref->getFileName();
                return $result;
            }
        } elseif ($callback instanceof Closure ) {
            $result['name'] = 'closure';
            try {
                $ref = new ReflectionFunction($callback);

            } catch ( ReflectionException $e ) {
                $ref = null;
            }

            $result['file'] = $ref->getFileName();
            return $result;
        } else {
            $result['name'] = 'unknown';
            $result['file'] = '';
            return $result;
        }
    }
    public function get_component( $path )
    {
        if (strlen($path) === 0 ) {
            return $path;
        }
        if (false !== strpos($path, 'wp-includes') ) {
            return 'core';
        }
        if (false !== strpos($path, WP_PLUGIN_DIR) ) {

            $plugin_dir = substr($path, strlen(WP_PLUGIN_DIR), strlen($path) - 1);

            if ($plugin_dir[0] === '/' ) {
                $plugin_dir = substr($plugin_dir, 1, strlen($plugin_dir) - 1);
            }

            return substr($plugin_dir, 0, strpos($plugin_dir, '/'));
        }
    }
}