<?php

require_once __DIR__ . '/../vendor/autoload.php';

class visualCaptcha_TestCase extends PHPUnit_Framework_TestCase {
    public function setup() {
        $this->session = new DummySession();
    }
}

class DummySession extends \visualCaptcha\Session {
    private $session = Array();

    public function clear() {
        $this->session = Array();
    }

    public function get( $key ) {
        return $this->session[ $key ];
    }

    public function set( $key, $value ) {
        $this->session[ $key ] = $value;
    }
}

?>