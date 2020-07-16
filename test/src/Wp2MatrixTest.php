<?php namespace sbronsted;

use Mockery;
use PHPUnit\Framework\TestCase;
use stdClass;
use function Brain\Monkey\Functions\when;
use function Brain\Monkey\setUp;
use function Brain\Monkey\tearDown;

class Wp2MatrixTest extends TestCase {
	 protected function setUp(): void {
			parent::setUp();
			setUp();
	 }

	 protected function tearDown(): void {
			tearDown();
			parent::tearDown();
	 }

	 private function setUpHttpMock() {
			when( 'wp_remote_retrieve_response_code' )->alias( function ( $response ) {
				 return $response->code;
			} );
			when( 'wp_remote_retrieve_body' )->alias( function ( $response ) {
				 return $response->body;
			} );
			when( 'wp_remote_get' )->alias( function ( $url ) {
				 $result = new stdClass();
				 if ( preg_match( '/login/', $url ) ) {
						$result->flows          = [];
						$result->flows[]        = new stdClass();
						$result->flows[0]->type = 'm.login.password';
				 } else if ( preg_match( '/room/', $url ) ) {
						$result->room_id = '1';
				 }

				 return (object) [
					 'code' => 200,
					 'body' => json_encode( $result ),
				 ];
			} );
			when( 'wp_remote_request' )->alias( function ( $url, $data ) {
				 $result = new stdClass();
				 if ( preg_match( '/login/', $url ) ) {
						$result->user_id      = '@foo:somewhere.net';
						$result->access_token = 'ok';
				 }

				 return (object) [
					 'code' => 200,
					 'body' => json_encode( $result ),
				 ];
			} );
	 }

	 public function testOnPreUpdate() {
			self::setUpHttpMock();
			$name   = Wp2Matrix::settings;
			$w2m    = Wp2Matrix::instance();
			$values = [
				Wp2Matrix::url       => 'http://somewhere.net',
				Wp2Matrix::user      => 'foo',
				Wp2Matrix::password  => 'bar',
				Wp2Matrix::roomAlias => 'aRoom',
			];
			$origin = [];
			when( 'add_settings_error' )->echoArg( 3 );
			ob_start();
			$result = $w2m->onPreUpdate( $values, $name, $origin );
			$output = ob_get_clean();
			$this->assertEquals( 0, strlen( $output ) );
			$this->assertEquals( '@foo:somewhere.net', $result[ Wp2Matrix::userId ] );
			$this->assertEquals( '1', $result[ Wp2Matrix::roomId ] );
			$this->assertEquals( 'ok', $result[ Wp2Matrix::accessToken ] );
	 }

	 public function testOnPreUpdateShouldChange() {
			self::setUpHttpMock();
			$name   = Wp2Matrix::settings;
			$w2m    = Wp2Matrix::instance();
			$values = [
				Wp2Matrix::url       => 'http://somewhere.net',
				Wp2Matrix::user      => 'foo',
				Wp2Matrix::password  => 'bar',
				Wp2Matrix::roomAlias => 'aRoom',
			];
			$origin = [
				Wp2Matrix::userId      => 'foo',
				Wp2Matrix::accessToken => 'bar',
				Wp2Matrix::roomId      => '2',
			];
			when( 'add_settings_error' )->echoArg( 3 );
			ob_start();
			$result = $w2m->onPreUpdate( $values, $name, $origin );
			$output = ob_get_clean();
			$this->assertEquals( 0, strlen( $output ) );
			$this->assertEquals( '@foo:somewhere.net', $result[ Wp2Matrix::userId ] );
			$this->assertEquals( '1', $result[ Wp2Matrix::roomId ] );
			$this->assertEquals( 'ok', $result[ Wp2Matrix::accessToken ] );
	 }

	 public function testOnPreUpdateWrongName() {
			$name = 'foo';
			$w2m  = Wp2Matrix::instance();
			when( 'add_settings_error' )->echoArg( 3 );
			ob_start();
			$result = $w2m->onPreUpdate( [], $name, [] );
			$output = ob_get_clean();
			$this->assertEquals( 0, strlen( $output ) );
			$this->assertTrue( empty( $result ) );
	 }

	 public function testOnPreUpdateException() {
			$error = Mockery::Mock( 'WP_Error' );
			$error->shouldReceive( 'get_error_message' )->andReturn( 'Not found' );
			$error->shouldReceive( 'get_error_code' )->andReturn( 404 );
			when( 'wp_remote_get' )->justReturn( $error );
			$name = Wp2Matrix::settings;
			$w2m  = Wp2Matrix::instance();
			when( 'add_settings_error' )->echoArg( 3 );
			ob_start();
			$result = $w2m->onPreUpdate( [], $name, [] );
			$output = ob_get_clean();
			$this->assertTrue( strlen( $output ) > 0 );
			$this->assertTrue( empty( $result ) );
	 }

	 public function testOnPostPublish() {
			self::setUpHttpMock();
			$post               = new stdClass();
			$post->ID           = 1;
			$post->post_title   = 'test';
			$post->post_content = 'test';

			$fixtures = [
				Wp2Matrix::url         => 'http://somewhere.net',
				Wp2Matrix::accessToken => 'ok',
				Wp2Matrix::roomId      => '1',
			];
			when( 'get_option' )->justReturn( $fixtures );
			$w2m = Wp2Matrix::instance();
			$this->expectNotToPerformAssertions();
			$w2m->onPostPublish( $post->ID, $post );
	 }

	 public function testDisplay() {
			$w2m      = Wp2Matrix::instance();
			$fixtures = [
				Wp2Matrix::url       => 'http://somewhere.net',
				Wp2Matrix::user      => 'foo',
				Wp2Matrix::password  => 'bar',
				Wp2Matrix::roomAlias => 'aRoom',
			];

			when( '__' )->returnArg( 1 );
			when( 'esc_attr' )->returnArg( 1 );
			when( 'esc_html_e' )->returnArg( 1 );
			when( 'current_user_can' )->justReturn( true );
			when( 'esc_html' )->returnArg( 1 );
			when( 'get_admin_page_title' )->justReturn();
			when( 'settings_fields' )->justReturn();
			when( 'register_setting' )->justReturn();
			when( 'submit_button' )->justReturn();
			when( 'do_settings_sections' )->alias( function () use ( $w2m ) {
				 $w2m->onInit();
			} );
			when( 'add_settings_field' )->alias( function ( $id, $title, $callback, $page, $section, $args ) {
				 $object = $callback[0];
				 $method = $callback[1];
				 $object->$method( $args );
			} );
			when( 'get_option' )->justReturn( $fixtures );
			when( 'add_settings_section' )->alias( function ( $id, $title, $callback, $page ) {
				 $object = $callback[0];
				 $method = $callback[1];
				 $object->$method( [ 'id' => $id ] );
			} );
			ob_start();
			$w2m->display();
			$output = ob_get_clean();
			$this->assertTrue( strlen( $output ) > 0 );
			$this->assertStringContainsString( 'following', $output );
			foreach ( $fixtures as $name => $value ) {
				 $this->assertStringContainsString( $name, $output, $name );
				 $this->assertStringContainsString( $value, $output, $value );
			}
	 }
}
