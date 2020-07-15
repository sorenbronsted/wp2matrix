<?php namespace sbronsted;

use Mockery;
use PHPUnit\Framework\TestCase;
use function Brain\Monkey\Functions\when;
use function Brain\Monkey\setUp;
use function Brain\Monkey\tearDown;

class HttpTest extends TestCase {

	protected function setUp(): void {
		parent::setUp();
		setUp();
	}

	protected function tearDown(): void {
		tearDown();
		parent::tearDown();
	}

	public function testGet() {
		when('wp_remote_get')->justReturn(200);
		when('wp_remote_retrieve_response_code')->returnArg(1);
		when('wp_remote_retrieve_body')->justReturn(json_encode((object)['foo' => 'bar']));

		$http = new Http();
		$this->assertEquals(200, $http->get('http://somewhere.net'));
		$this->assertEquals('bar', json_decode($http->body())->foo);
	}

	public function testGetFail() {
		when('wp_remote_get')->justReturn(500);
		when('wp_remote_retrieve_response_code')->returnArg(1);

		$http = new Http();
		$this->assertEquals(500, $http->get('http://somewhere.net'));
	}

	public function testGetWithWpError() {
		$error = Mockery::Mock('WP_Error');
		$error->shouldReceive('get_error_message')->andReturn('Not found');
		$error->shouldReceive('get_error_code')->andReturn(404);

		when('wp_remote_get')->justReturn($error);

		$http = new Http();
		$this->expectException(W2MException::class);
		$http->get('http://somewhere.net');
	}

	public function testPost() {
		when('wp_remote_request')->justReturn(200);
		when('wp_remote_retrieve_response_code')->returnArg(1);

		$http = new Http();
		$this->assertEquals(200, $http->post('http://somewhere.net', ['foo' => 'bar']));
	}


	public function testPostFail() {
		when('wp_remote_request')->justReturn(500);
		when('wp_remote_retrieve_response_code')->returnArg(1);

		$http = new Http();
		$this->assertEquals(500, $http->post('http://somewhere.net', ['foo' => 'bar']));
	}

	public function testPostWithWpError() {
		$error = Mockery::Mock('WP_Error');
		$error->shouldReceive('get_error_message')->andReturn('Not found');
		$error->shouldReceive('get_error_code')->andReturn(404);

		when('wp_remote_request')->justReturn($error);

		$http = new Http();
		$this->expectException(W2MException::class);
		$http->post('http://somewhere.net', ['foo' => 'bar']);
	}

	public function testPut() {
		when('wp_remote_request')->justReturn(200);
		when('wp_remote_retrieve_response_code')->returnArg(1);

		$http = new Http();
		$this->assertEquals(200, $http->put('http://somewhere.net', ['foo' => 'bar']));
	}


	public function testPutFail() {
		when('wp_remote_request')->justReturn(500);
		when('wp_remote_retrieve_response_code')->returnArg(1);

		$http = new Http();
		$this->assertEquals(500, $http->put('http://somewhere.net', ['foo' => 'bar']));
	}

	public function testPutWithWpError() {
		$error = Mockery::Mock('WP_Error');
		$error->shouldReceive('get_error_message')->andReturn('Not found');
		$error->shouldReceive('get_error_code')->andReturn(404);

		when('wp_remote_request')->justReturn($error);

		$http = new Http();
		$this->expectException(W2MException::class);
		$http->put('http://somewhere.net', ['foo' => 'bar']);
	}

	public function testBodyFail() {
		$this->expectExceptionMessage('No response available. You need to do a http method call first');
		$http = new Http();
		$http->body();
	}
}