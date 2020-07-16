<?php namespace sbronsted;

use Exception;
use Throwable;

class W2MException extends Exception {

	public function __construct( $message = "", $code = 0, Throwable $previous = null ) {
		parent::__construct( $message, $code, $previous );
	}

	public static function throw($error) {
		throw new self($error->get_error_message(), is_numeric($error->get_error_code()) ?? 0);
	}
}