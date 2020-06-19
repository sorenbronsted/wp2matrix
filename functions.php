<?php namespace sbronsted;

use Exception;
use stdClass;

function post(string $host, string $accessToken, string $roomId, object $post) {
	$url = 'http://'.$host.'/_matrix/client/r0/rooms/'.urlencode($roomId).'/send/m.room.message/'.$post->ID.'?access_token='.urlencode($accessToken);

	$message = new stdClass();
	$message->msgtype = 'm.text';
	$message->body = strip_tags($post->post_title).strip_tags($post->post_content);
	$message->format = 'org.matrix.custom.html';
	$message->formatted_body = $post->post_title.$post->post_content;

	$args = array(
		'method'      => 'PUT',
		'headers'     => [
			'Content-Type' => 'application/json',
		],
		'body'     => json_encode($message),
		'timeout'     => '5',
		'redirection' => '5',
		'httpversion' => '1.0',
		'blocking'    => true,
	);
	$response = wp_remote_request($url, $args);
	$code = wp_remote_retrieve_response_code($response);
	if ($code != 200) {
		throw new Exception('Posting id: '.$post->ID.' failed with: '.$code);
	}
}


function login(string $host, string $user, string $password) : object {
	$url = 'http://'.$host.'/_matrix/client/r0/login';
	$flow = 'm.login.password';
	hasLoginFlow($url, $flow);

	$body = new stdClass();
	$body->type = $flow;
	$body->user = $user;
	$body->password = $password;

	$args = array(
		'body'        => json_encode($body),
		'timeout'     => '5',
		'redirection' => '5',
		'httpversion' => '1.0',
		'blocking'    => true,
	);
	$response = wp_remote_post($url, $args);
	$code = wp_remote_retrieve_response_code($response);
	if ($code == 403) {
		throw new Exception('Wrong credentials');
	}
	else if ($code != 200) {
		throw new Exception('Login failed with code: '.$code);
	}
	return json_decode(wp_remote_retrieve_body($response));
}

function hasLoginFlow(string $url, string $flow) {
	$response = wp_remote_get($url);
	$code = wp_remote_retrieve_response_code($response);
	if ($code != 200) {
		throw new Exception('Unexpected result: '.$code);
	}
	$method = json_decode(wp_remote_retrieve_body($response));
	foreach ($method->flows as $item) {
		if ($item->type == $flow) {
			return;
		}
	}
	throw new Exception('Unsupported login flow');
}

function getRoomId(string $host, string $userId, string $roomAlias) : string {
	$parts = explode(':', $userId);
	$url = 'http://'.$host.'/_matrix/client/r0/directory/room/'.urlencode('#'.$roomAlias.':'.$parts[1]);
	$response = wp_remote_get($url);
	$code = wp_remote_retrieve_response_code($response);
	if ($code == 404) {
		throw new Exception('Room not found');
	}
	else if ($code != 200) {
		throw new Exception('Unexpected result: '.$code);
	}
	$data = json_decode(wp_remote_retrieve_body($response));
	return $data->room_id;
}