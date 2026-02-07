<?php

declare(strict_types=1);

namespace Gemogen\Tests\Unit\Sources;

use Gemogen\Sources\FileImportSource;
use PHPUnit\Framework\TestCase;

class FileImportSourceTest extends TestCase {

	public function test_from_array_provides_titles(): void {
		$source = FileImportSource::fromArray( [
			'titles' => [ 'Hello World', 'Test Post' ],
		] );

		$title = $source->getTitle();
		$this->assertContains( $title, [ 'Hello World', 'Test Post' ] );
	}

	public function test_from_array_provides_content(): void {
		$source = FileImportSource::fromArray( [
			'content' => [ 'My paragraph.', 'Another one.' ],
		] );

		$content = $source->getContent();
		$this->assertNotNull( $content );
		$this->assertStringContainsString( 'wp:paragraph', $content );
	}

	public function test_from_array_provides_fields(): void {
		$source = FileImportSource::fromArray( [
			'fields' => [
				'first_name' => [ 'John', 'Jane' ],
			],
		] );

		$this->assertTrue( $source->supports( 'first_name' ) );
		$this->assertContains( $source->getField( 'first_name' ), [ 'John', 'Jane' ] );
	}

	public function test_returns_null_for_missing_data(): void {
		$source = FileImportSource::fromArray( [] );

		$this->assertNull( $source->getTitle() );
		$this->assertNull( $source->getContent() );
		$this->assertNull( $source->getField( 'anything' ) );
	}

	public function test_supports_returns_false_for_unknown_field(): void {
		$source = FileImportSource::fromArray( [
			'fields' => [ 'known' => [ 'value' ] ],
		] );

		$this->assertTrue( $source->supports( 'known' ) );
		$this->assertFalse( $source->supports( 'unknown' ) );
	}

	public function test_priority_is_100(): void {
		$source = FileImportSource::fromArray( [] );
		$this->assertSame( 100, $source->getPriority() );
	}

	public function test_parses_json_file(): void {
		$tmpFile = tempnam( sys_get_temp_dir(), 'gemogen_test_' ) . '.json';
		file_put_contents( $tmpFile, json_encode( [
			'titles'  => [ 'JSON Title' ],
			'content' => [ 'JSON Content.' ],
			'fields'  => [
				'category_name' => [ 'From JSON' ],
			],
		] ) );

		$source = new FileImportSource( $tmpFile );

		$this->assertSame( 'JSON Title', $source->getTitle() );
		$this->assertTrue( $source->supports( 'category_name' ) );
		$this->assertSame( 'From JSON', $source->getField( 'category_name' ) );

		unlink( $tmpFile );
	}

	public function test_parses_csv_file(): void {
		$tmpFile = tempnam( sys_get_temp_dir(), 'gemogen_test_' ) . '.csv';
		file_put_contents( $tmpFile, "title,content,first_name\n\"CSV Post\",\"CSV paragraph.\",\"CSVUser\"\n\"Second Post\",\"Second paragraph.\",\"AnotherUser\"\n" );

		$source = new FileImportSource( $tmpFile );

		$this->assertContains( $source->getTitle(), [ 'CSV Post', 'Second Post' ] );
		$this->assertTrue( $source->supports( 'first_name' ) );
		$this->assertContains( $source->getField( 'first_name' ), [ 'CSVUser', 'AnotherUser' ] );

		unlink( $tmpFile );
	}

	public function test_handles_nonexistent_file(): void {
		$source = new FileImportSource( '/nonexistent/file.json' );

		$this->assertNull( $source->getTitle() );
		$this->assertNull( $source->getField( 'anything' ) );
	}
}
