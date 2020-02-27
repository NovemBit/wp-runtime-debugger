<?php


namespace NovemBit\wp\plugins\RuntimeDebugger;


class SystemStateTracker
{
    /**
     * @var SystemStateTracker
     */
    protected static $instance = null;
    public static function get_instance()
    {
        if (null === static::$instance ) {
            static::$instance = new static();
        }
        return static::$instance;
    }
    protected function __construct()
    {
    }
    protected function __clone()
    {
    }
    protected function __wakeup()
    {
    }

    protected $time_stack = array();

    /**
     * Starts tracking, stores the current state
     *
     * @return void
     */
    public function start( $key )
    {
        $this->time_stack[ $key ] = microtime(true);
    }

    /**
     * Returns the time elapsed since the start
     *
     * @see start
     *
     * @return double
     */
    public function get_time_elapsed( $key )
    {
        return microtime(true) - $this->time_stack[ $key ];
    }
}