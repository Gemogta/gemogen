<?php

declare(strict_types=1);

namespace Gemogen\Tests\Unit\Core;

use Gemogen\Core\ContentPool;
use Gemogen\Sources\BuiltInSource;
use Gemogen\Sources\FileImportSource;
use PHPUnit\Framework\TestCase;

class ContentPoolTest extends TestCase {

	public function test_returns_untitled_when_no_sources(): void {
		$pool = new ContentPool();
		$this->assertSame( 'Untitled', $pool->getTitle() );
	}

	public function test_returns_empty_content_when_no_sources(): void {
		$pool = new ContentPool();
		$this->assertSame( '', $pool->getContent() );
	}

	public function test_returns_null_field_when_no_sources(): void {
		$pool = new ContentPool();
		$this->assertNull( $pool->getField( 'first_name' ) );
	}

	public function test_supports_returns_false_when_no_sources(): void {
		$pool = new ContentPool();
		$this->assertFalse( $pool->supports( 'first_name' ) );
	}

	public function test_builtin_source_provides_title(): void {
		$pool = new ContentPool();
		$pool->addSource( new BuiltInSource() );

		$title = $pool->getTitle( 'post' );
		$this->assertNotEmpty( $title );
		$this->assertNotSame( 'Untitled', $title );
	}

	public function test_builtin_source_provides_content(): void {
		$pool = new ContentPool();
		$pool->addSource( new BuiltInSource() );

		$content = $pool->getContent( 'post' );
		$this->assertNotEmpty( $content );
		$this->assertStringContainsString( 'wp:paragraph', $content );
	}

	public function test_builtin_source_provides_fields(): void {
		$pool = new ContentPool();
		$pool->addSource( new BuiltInSource() );

		$this->assertTrue( $pool->supports( 'first_name' ) );
		$this->assertNotNull( $pool->getField( 'first_name' ) );
	}

	public function test_higher_priority_source_overrides_lower(): void {
		$pool = new ContentPool();
		$pool->addSource( new BuiltInSource() ); // Priority 0.

		$customData = FileImportSource::fromArray( [
			'titles'  => [ 'Custom Title Only' ],
			'content' => [],
			'fields'  => [],
		] ); // Priority 100.

		$pool->addSource( $customData );

		$title = $pool->getTitle();
		$this->assertSame( 'Custom Title Only', $title );
	}

	public function test_falls_back_to_lower_priority_when_higher_returns_null(): void {
		$pool = new ContentPool();
		$pool->addSource( new BuiltInSource() ); // Priority 0, has first_name.

		$emptyCustom = FileImportSource::fromArray( [
			'titles'  => [],
			'content' => [],
			'fields'  => [], // No first_name.
		] ); // Priority 100.

		$pool->addSource( $emptyCustom );

		// Should fall back to BuiltInSource for first_name.
		$this->assertNotNull( $pool->getField( 'first_name' ) );
	}

	public function test_get_sources_returns_sorted_list(): void {
		$pool    = new ContentPool();
		$builtin = new BuiltInSource();
		$custom  = FileImportSource::fromArray( [ 'titles' => [ 'Hi' ] ] );

		$pool->addSource( $builtin );
		$pool->addSource( $custom );

		$sources = $pool->getSources();
		$this->assertCount( 2, $sources );
		// Custom (priority 100) should come first.
		$this->assertSame( 100, $sources[0]->getPriority() );
		$this->assertSame( 0, $sources[1]->getPriority() );
	}
}
