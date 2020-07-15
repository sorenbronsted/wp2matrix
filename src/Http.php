<?php namespace sbronsted;

class Http {
	private $response;

	public function get($url) {
		if (!parse_url($url)) {
			throw new W2MException('Invalid url');
		}
		$this->response = wp_remote_get( $url );
		$this->isError();
		return wp_remote_retrieve_response_code( $this->response );
	}

	public function post( $url, $data ) {
		if (!parse_url($url)) {
			throw new W2MException('Invalid url');
		}
		$args           = array(
				'method'      => 'POST',
				'headers'     => [
						'Content-Type' => 'application/json',
				],
				'body'        => json_encode( $data ),
				'timeout'     => '5',
				'redirection' => '5',
				'httpversion' => '1.0',
				'blocking'    => true,
		);
		$this->response = wp_remote_request( $url, $args );
		$this->isError();
		return wp_remote_retrieve_response_code( $this->response );
	}

	public function put( $url, $data ) {
		if (!parse_url($url)) {
			throw new W2MException('Invalid url');
		}
		$args           = array(
				'method'      => 'PUT',
				'headers'     => [
						'Content-Type' => 'application/json',
				],
				'body'        => json_encode( $data ),
				'timeout'     => '5',
				'redirection' => '5',
				'httpversion' => '1.0',
				'blocking'    => true,
		);
		$this->response = wp_remote_request( $url, $args );
		$this->isError();
		return wp_remote_retrieve_response_code( $this->response );
	}

	public function body() {
		if ( $this->response == null ) {
			throw new W2MException( 'No response available. You need to do a http method call first' );
		}
		return wp_remote_retrieve_body( $this->response );
	}

	private function isError(): void {
		if ( is_wp_error( $this->response ) ) {
			$error = $this->response;
			$this->response = null;
			W2MException::throw( $error );
		}
	}
}