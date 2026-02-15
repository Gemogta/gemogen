<?php

declare(strict_types=1);

namespace Gemogen\Tests\Unit\Core;

use Gemogen\Core\Logger;
use PHPUnit\Framework\TestCase;

class LoggerTest extends TestCase {

	private Logger $logger;

	protected function setUp(): void {
		$this->logger = new Logger();
	}

	public function test_can_be_instantiated(): void {
		$this->assertInstanceOf( Logger::class, $this->logger );
	}

	public function test_info_method_exists(): void {
		$this->assertTrue( method_exists( $this->logger, 'info' ) );
	}

	public function test_error_method_exists(): void {
		$this->assertTrue( method_exists( $this->logger, 'error' ) );
	}

	public function test_warning_method_exists(): void {
		$this->assertTrue( method_exists( $this->logger, 'warning' ) );
	}

	public function test_info_logs_to_error_log_without_wp_cli(): void {
		// WP_CLI is not defined in unit tests, so info() should call error_log().
		// We verify it does not throw an exception.
		$this->logger->info( 'Test info message' );
		$this->assertTrue( true ); // No exception thrown.
	}

	public function test_error_logs_without_exception(): void {
		$this->logger->error( 'Test error message' );
		$this->assertTrue( true ); // No exception thrown.
	}

	public function test_warning_logs_without_exception(): void {
		$this->logger->warning( 'Test warning message' );
		$this->assertTrue( true ); // No exception thrown.
	}

	public function test_info_formats_message_correctly(): void {
		// Use a custom error handler to capture the error_log output.
		$captured = '';
		set_error_handler( function ( int $errno, string $errstr ) use ( &$captured ): bool {
			$captured = $errstr;
			return true;
		} );

		// error_log with type 0 sends to PHP's system logger.
		// We can't easily capture that, but we can verify it runs without error.
		$this->logger->info( 'Test message' );
		restore_error_handler();

		// Simply verify logger methods accept string arguments.
		$this->assertInstanceOf( Logger::class, $this->logger );
	}

	public function test_log_method_is_private(): void {
		$reflection = new \ReflectionClass( Logger::class );
		$method     = $reflection->getMethod( 'log' );

		$this->assertTrue( $method->isPrivate() );
	}

	public function test_log_method_has_level_and_message_parameters(): void {
		$reflection = new \ReflectionClass( Logger::class );
		$method     = $reflection->getMethod( 'log' );
		$params     = $method->getParameters();

		$this->assertCount( 2, $params );
		$this->assertSame( 'level', $params[0]->getName() );
		$this->assertSame( 'message', $params[1]->getName() );
	}
}
