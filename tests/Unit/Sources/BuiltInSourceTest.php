<?php

declare(strict_types=1);

namespace Gemogen\Tests\Unit\Sources;

use Gemogen\Contracts\ContentSourceInterface;
use Gemogen\Sources\BuiltInSource;
use PHPUnit\Framework\TestCase;

class BuiltInSourceTest extends TestCase {

	private BuiltInSource $source;

	protected function setUp(): void {
		$this->source = new BuiltInSource();
	}

	public function test_implements_content_source_interface(): void {
		$this->assertInstanceOf( ContentSourceInterface::class, $this->source );
	}

	public function test_priority_is_zero(): void {
		$this->assertSame( 0, $this->source->getPriority() );
	}

	public function test_get_title_returns_string_for_post_context(): void {
		$title = $this->source->getTitle( 'post' );

		$this->assertIsString( $title );
		$this->assertNotEmpty( $title );
	}

	public function test_get_title_returns_string_for_page_context(): void {
		$title = $this->source->getTitle( 'page' );

		$this->assertIsString( $title );
		$this->assertNotEmpty( $title );
	}

	public function test_get_title_falls_back_to_post_for_unknown_context(): void {
		$title = $this->source->getTitle( 'unknown_type' );

		$this->assertIsString( $title );
		$this->assertNotEmpty( $title );
	}

	public function test_get_content_returns_block_markup(): void {
		$content = $this->source->getContent();

		$this->assertIsString( $content );
		$this->assertStringContainsString( 'wp:paragraph', $content );
	}

	public function test_get_content_contains_paragraphs(): void {
		$content = $this->source->getContent();

		$this->assertStringContainsString( '<p>', $content );
		$this->assertStringContainsString( '</p>', $content );
	}

	public function test_supports_first_name(): void {
		$this->assertTrue( $this->source->supports( 'first_name' ) );
	}

	public function test_supports_last_name(): void {
		$this->assertTrue( $this->source->supports( 'last_name' ) );
	}

	public function test_supports_category_name(): void {
		$this->assertTrue( $this->source->supports( 'category_name' ) );
	}

	public function test_supports_tag_name(): void {
		$this->assertTrue( $this->source->supports( 'tag_name' ) );
	}

	public function test_supports_comment_text(): void {
		$this->assertTrue( $this->source->supports( 'comment_text' ) );
	}

	public function test_does_not_support_unknown_field(): void {
		$this->assertFalse( $this->source->supports( 'unknown_field' ) );
	}

	public function test_get_field_returns_string_for_first_name(): void {
		$value = $this->source->getField( 'first_name' );

		$this->assertIsString( $value );
		$this->assertNotEmpty( $value );
	}

	public function test_get_field_returns_string_for_last_name(): void {
		$value = $this->source->getField( 'last_name' );

		$this->assertIsString( $value );
		$this->assertNotEmpty( $value );
	}

	public function test_get_field_returns_null_for_unknown(): void {
		$this->assertNull( $this->source->getField( 'nonexistent_field' ) );
	}

	public function test_get_field_returns_known_first_names(): void {
		$knownNames = [
			'Alice', 'Bob', 'Charlie', 'Diana', 'Edward',
			'Fiona', 'George', 'Helen', 'Ivan', 'Julia',
			'Kevin', 'Laura', 'Mike', 'Nina', 'Oscar',
		];

		$name = $this->source->getField( 'first_name' );
		$this->assertContains( $name, $knownNames );
	}

	public function test_get_title_returns_known_post_titles(): void {
		$knownTitles = [
			'Getting Started with WordPress',
			'How to Build a Website',
			'Understanding Custom Post Types',
			'A Guide to Plugin Development',
			'Best Practices for Theme Design',
			'Optimizing Your WordPress Site',
			'Working with REST API',
			'Introduction to Block Editor',
			'Managing Media in WordPress',
			'Security Tips for WordPress',
			'Understanding WordPress Hooks',
			'Creating Custom Taxonomies',
			'WordPress Performance Tuning',
			'Building Custom Widgets',
			'Mastering WordPress Templates',
		];

		$title = $this->source->getTitle( 'post' );
		$this->assertContains( $title, $knownTitles );
	}

	public function test_get_title_returns_known_page_titles(): void {
		$knownTitles = [
			'About Us', 'Contact', 'Services', 'Our Team', 'FAQ',
			'Privacy Policy', 'Terms of Service', 'Portfolio', 'Blog', 'Careers',
		];

		$title = $this->source->getTitle( 'page' );
		$this->assertContains( $title, $knownTitles );
	}
}
