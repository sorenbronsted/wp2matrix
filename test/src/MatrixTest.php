<?php namespace sbronsted;

use Mockery;
use PHPUnit\Framework\TestCase;
use function Brain\Monkey\Functions\when;
use function Brain\Monkey\setUp;
use function Brain\Monkey\tearDown;

class MatrixTest extends TestCase {

	protected function setUp(): void {
		parent::setUp();
		setUp();

		when('wp_remote_retrieve_response_code')->alias(function($response) {
			return $response->code;
		});
		when('wp_remote_retrieve_body')->alias(function($response) {
			return $response->body;
		});
	}

	protected function tearDown(): void {
		tearDown();
		Mockery::close();
		parent::tearDown();
	}

	public function testLogin() {
		when('wp_remote_get')->alias(function($url) {
			return (object)[
					'code' => 200,
					'body' => json_encode((object)['flows' => [(object)['type' => 'm.login.password']]]),
			];
		});

		when('wp_remote_request')->alias(function($url, $data) {
			return (object)[
					'code' => 200,
					'body' => $data['body'],
			];
		});
		$matrix = new Matrix(new Http());
		$this->assertEquals('bar', $matrix->login('http://somewhere.net', 'foo', 'bar')->password);
	}

	public function testUnsupportedLoginMethode() {
		when('wp_remote_get')->alias(function($url) {
			return (object)[
					'code' => 200,
					'body' => json_encode((object)['flows' => [(object)['type' => 'm.oauth']]]),
			];
		});

		$this->expectExceptionMessage('Unsupported login flow');
		$matrix = new Matrix(new Http());
		$matrix->login('http://somewhere.net', 'foo', 'bar');
	}

	public function testWrongCredentials() {
		when('wp_remote_get')->alias(function($url) {
			return (object)[
					'code' => 200,
					'body' => json_encode((object)['flows' => [(object)['type' => 'm.login.password']]]),
			];
		});
		when('wp_remote_request')->alias(function($url, $data) {
			return (object)[
					'code' => 403,
					'body' => $data['body'],
			];
		});
		$this->expectExceptionMessage('Wrong credentials');
		$matrix = new Matrix(new Http());
		$matrix->login('http://somewhere.net', 'foo', 'bar');
	}

	public function testOtherLoginFailures() {
		when('wp_remote_get')->alias(function($url) {
			return (object)[
					'code' => 200,
					'body' => json_encode((object)['flows' => [(object)['type' => 'm.login.password']]]),
			];
		});
		when('wp_remote_request')->alias(function($url, $data) {
			return (object)[
					'code' => 500,
					'body' => $data['body'],
			];
		});

		$this->expectExceptionMessage('Login failed with code: 500');
		$matrix = new Matrix(new Http());
		$matrix->login('http://somewhere.net', 'foo', 'bar');
	}

	public function testUnexpectedLoginMethod() {
		$error = Mockery::Mock('WP_Error');
		$error->shouldReceive('get_error_message')->andReturn('foo');
		$error->shouldReceive('get_error_code')->andReturn(1);

		when('wp_remote_get')->justReturn($error);

		$this->expectException(W2MException::class);
		$this->expectExceptionMessage('foo');
		$matrix = new Matrix(new Http());
		$matrix->login('http://somewhere.net', 'foo', 'bar');
	}

	public function testGetRoomId() {
		when('wp_remote_get')->justReturn(
			(object)[
					'code' => 200,
					'body' => json_encode((object)['room_id' => 'bar']),
			]
		);
		$matrix = new Matrix(new Http());
		$result = $matrix->getRoomId('http://somewhere.net', '@foo:somewhere.net', 'bar');
		$this->assertEquals('bar', $result);
	}

	public function testRoomNotFound() {
		when('wp_remote_get')->justReturn((object)['code' => 404]);
		$this->expectExceptionMessage('Room not found');
		$matrix = new Matrix(new Http());
		$matrix->getRoomId('http://somewhere.net', '@foo:somewhere.net', 'bar');
	}

	public function testRoomUnexpectedResult() {
		when('wp_remote_get')->justReturn((object)['code' => 500]);
		$this->expectExceptionMessage('Unexpected result: 500');
		$matrix = new Matrix(new Http());
		$matrix->getRoomId('http://somewhere.net', '@foo:somewhere.net', 'bar');
	}

	public function testPost() {
		when('wp_remote_request')->justReturn((object)['code' => 200]);
		$this->expectNotToPerformAssertions();
		$matrix = new Matrix(new Http());
		$matrix->post('http://somewhere.net', '!znshdy&kdj', 'bar',
				(object)['ID' => 1, 'post_title' => 'The post', 'post_content' => 'The content']);
	}

	public function testPostError() {
		when('wp_remote_request')->justReturn((object)['code' => 500]);
		$this->expectExceptionMessage('Posting id: 1 failed with: 500');
		$matrix = new Matrix(new Http());
		$matrix->post('http://somewhere.net', '!znshdy&kdj', 'bar',
				(object)['ID' => 1, 'post_title' => 'The post', 'post_content' => 'The content']);
	}
}
