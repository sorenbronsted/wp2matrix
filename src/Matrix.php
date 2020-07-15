<?php namespace sbronsted;

use Exception;
use stdClass;

class Matrix {
	private $http;

	public function __construct(Http $http) {
		$this->http = $http;
	}

	public function post( string $url, string $accessToken, string $roomId, object $post ): void {
		$url .= '/_matrix/client/r0/rooms/' . urlencode( $roomId ) . '/send/m.room.message/' . $post->ID . '?access_token=' . urlencode( $accessToken );

		$message                 = new stdClass();
		$message->msgtype        = 'm.text';
		$message->body           = strip_tags( $post->post_title ) . strip_tags( $post->post_content );
		$message->format         = 'org.matrix.custom.html';
		$message->formatted_body = $post->post_title . $post->post_content;

		$code = $this->http->put( $url, $message );
		if ( $code != 200 ) {
			throw new W2MException( 'Posting id: ' . $post->ID . ' failed with: ' . $code, $code );
		}
	}


	public function login( string $url, string $user, string $password ): object {
		$url  .= '/_matrix/client/r0/login';
		$flow = 'm.login.password';
		$this->hasLoginFlow( $url, $flow );

		$body           = new stdClass();
		$body->type     = $flow;
		$body->user     = $user;
		$body->password = $password;

		$code = $this->http->post( $url, $body );
		if ( $code == 403 ) {
			throw new W2MException( 'Wrong credentials', $code );
		} else if ( $code != 200 ) {
			throw new W2MException( 'Login failed with code: ' . $code, $code );
		}

		return json_decode( $this->http->body() );
	}

	private function hasLoginFlow( string $url, string $flow ): void {
		$code = $this->http->get($url);
		if ( $code != 200 ) {
			throw new W2MException( 'Unexpected result code: ' . $code, $code );
		}
		$method = json_decode( $this->http->body() );
		foreach ( $method->flows as $item ) {
			if ( $item->type == $flow ) {
				return;
			}
		}
		throw new W2MException( 'Unsupported login flow' );
	}

	public function getRoomId( string $url, string $userId, string $roomAlias ): string {
		$parts = explode( ':', $userId );
		$url   .= '/_matrix/client/r0/directory/room/' . urlencode( '#' . $roomAlias . ':' . $parts[1] );
		$code  = $this->http->get($url);
		if ( $code == 404 ) {
			throw new W2MException( 'Room not found', $code );
		} else if ( $code != 200 ) {
			throw new W2MException( 'Unexpected result: ' . $code, $code );
		}
		$data = json_decode( $this->http->body() );

		return $data->room_id;
	}
}