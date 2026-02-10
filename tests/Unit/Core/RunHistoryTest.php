<?php

declare(strict_types=1);

namespace Gemogen\Tests\Unit\Core;

use Gemogen\Core\RunHistory;
use PHPUnit\Framework\TestCase;

class RunHistoryTest extends TestCase {

	private RunHistory $history;

	protected function setUp(): void {
		$this->history = new RunHistory();
	}

	public function test_get_all_returns_empty_array_without_wp(): void {
		$this->assertSame( [], $this->history->getAll() );
	}

	public function test_get_last_returns_null_without_wp(): void {
		$this->assertNull( $this->history->getLast() );
	}

	public function test_record_does_not_throw_without_wp(): void {
		// record() should gracefully handle missing WP functions.
		$this->history->record( 'core-content', [ 'posts' => 5 ], [ 'posts' => [ 1, 2, 3, 4, 5 ] ] );

		// Still returns empty because get_option is not available.
		$this->assertSame( [], $this->history->getAll() );
	}

	public function test_remove_last_does_not_throw_without_wp(): void {
		$this->history->removeLast();

		$this->assertSame( [], $this->history->getAll() );
	}

	public function test_clear_does_not_throw_without_wp(): void {
		$this->history->clear();

		$this->assertSame( [], $this->history->getAll() );
	}

	public function test_can_be_instantiated(): void {
		$this->assertInstanceOf( RunHistory::class, $this->history );
	}
}
