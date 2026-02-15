<?php

declare(strict_types=1);

namespace Gemogen\Tests\Integration\Generators;

use Gemogen\Core\ContentPool;
use Gemogen\Generators\CommentGenerator;
use Gemogen\Generators\MediaGenerator;
use Gemogen\Generators\PostGenerator;
use Gemogen\Generators\TaxonomyGenerator;
use Gemogen\Generators\UserGenerator;
use Gemogen\Sources\BuiltInSource;
use WP_UnitTestCase;

/**
 * Integration tests for content tagging (_gemogen_generated meta).
 *
 * Requires wp-env (Docker). Verifies that all generators properly tag
 * their created content with the `_gemogen_generated` meta key.
 */
class ContentTaggingTest extends WP_UnitTestCase {

	private ContentPool $pool;

	protected function setUp(): void {
		parent::setUp();

		$this->pool = new ContentPool();
		$this->pool->addSource( new BuiltInSource() );
	}

	// -- PostGenerator tagging --

	public function test_post_generator_adds_gemogen_meta(): void {
		$generator = new PostGenerator( $this->pool );
		$post_id   = $generator->generate( [ 'post_type' => 'post' ] );

		$meta = get_post_meta( $post_id, '_gemogen_generated', true );

		$this->assertEquals( 1, $meta );
	}

	public function test_page_generator_adds_gemogen_meta(): void {
		$generator = new PostGenerator( $this->pool );
		$page_id   = $generator->generate( [ 'post_type' => 'page' ] );

		$meta = get_post_meta( $page_id, '_gemogen_generated', true );

		$this->assertEquals( 1, $meta );
	}

	public function test_post_generator_creates_published_post(): void {
		$generator = new PostGenerator( $this->pool );
		$post_id   = $generator->generate( [ 'post_type' => 'post' ] );

		$post = get_post( $post_id );

		$this->assertNotNull( $post );
		$this->assertSame( 'publish', $post->post_status );
		$this->assertSame( 'post', $post->post_type );
	}

	public function test_post_generator_delete_removes_post(): void {
		$generator = new PostGenerator( $this->pool );
		$post_id   = $generator->generate( [ 'post_type' => 'post' ] );

		$generator->delete( $post_id );

		$this->assertNull( get_post( $post_id ) );
	}

	// -- UserGenerator tagging --

	public function test_user_generator_adds_gemogen_meta(): void {
		$generator = new UserGenerator( $this->pool );
		$user_id   = $generator->generate( [ 'role' => 'subscriber' ] );

		$meta = get_user_meta( $user_id, '_gemogen_generated', true );

		$this->assertEquals( 1, $meta );
	}

	public function test_user_generator_creates_user_with_role(): void {
		$generator = new UserGenerator( $this->pool );
		$user_id   = $generator->generate( [ 'role' => 'editor' ] );

		$user = get_user_by( 'id', $user_id );

		$this->assertNotFalse( $user );
		$this->assertContains( 'editor', $user->roles );
	}

	public function test_user_generator_creates_test_email(): void {
		$generator = new UserGenerator( $this->pool );
		$user_id   = $generator->generate();

		$user = get_user_by( 'id', $user_id );

		$this->assertStringContainsString( '@gemogen.test', $user->user_email );
	}

	public function test_user_generator_delete_removes_user(): void {
		$generator = new UserGenerator( $this->pool );
		$user_id   = $generator->generate();

		$generator->delete( $user_id );

		$this->assertFalse( get_user_by( 'id', $user_id ) );
	}

	// -- TaxonomyGenerator tagging --

	public function test_taxonomy_generator_adds_gemogen_meta_to_category(): void {
		$generator = new TaxonomyGenerator( $this->pool );
		$term_id   = $generator->generate( [ 'taxonomy' => 'category' ] );

		$meta = get_term_meta( $term_id, '_gemogen_generated', true );

		$this->assertEquals( 1, $meta );
	}

	public function test_taxonomy_generator_adds_gemogen_meta_to_tag(): void {
		$generator = new TaxonomyGenerator( $this->pool );
		$term_id   = $generator->generate( [ 'taxonomy' => 'post_tag' ] );

		$meta = get_term_meta( $term_id, '_gemogen_generated', true );

		$this->assertEquals( 1, $meta );
	}

	public function test_taxonomy_generator_creates_valid_term(): void {
		$generator = new TaxonomyGenerator( $this->pool );
		$term_id   = $generator->generate( [ 'taxonomy' => 'category' ] );

		$term = get_term( $term_id, 'category' );

		$this->assertNotNull( $term );
		$this->assertNotInstanceOf( \WP_Error::class, $term );
	}

	public function test_taxonomy_generator_delete_removes_term(): void {
		$generator = new TaxonomyGenerator( $this->pool );
		$term_id   = $generator->generate( [ 'taxonomy' => 'category' ] );

		$generator->delete( $term_id );

		$term = get_term( $term_id, 'category' );
		$this->assertTrue( $term === null || is_wp_error( $term ) );
	}

	// -- CommentGenerator tagging --

	public function test_comment_generator_adds_gemogen_meta(): void {
		// Create a post first.
		$post_generator = new PostGenerator( $this->pool );
		$post_id        = $post_generator->generate( [ 'post_type' => 'post' ] );

		$generator  = new CommentGenerator( $this->pool );
		$comment_id = $generator->generate( [ 'comment_post_ID' => $post_id ] );

		$meta = get_comment_meta( $comment_id, '_gemogen_generated', true );

		$this->assertEquals( 1, $meta );
	}

	public function test_comment_generator_requires_post_id(): void {
		$generator = new CommentGenerator( $this->pool );

		$this->expectException( \RuntimeException::class );
		$this->expectExceptionMessage( 'comment_post_ID is required' );

		$generator->generate( [ 'comment_post_ID' => 0 ] );
	}

	public function test_comment_generator_creates_approved_comment(): void {
		$post_generator = new PostGenerator( $this->pool );
		$post_id        = $post_generator->generate( [ 'post_type' => 'post' ] );

		$generator  = new CommentGenerator( $this->pool );
		$comment_id = $generator->generate( [ 'comment_post_ID' => $post_id ] );

		$comment = get_comment( $comment_id );

		$this->assertNotNull( $comment );
		$this->assertEquals( 1, $comment->comment_approved );
	}

	public function test_comment_generator_delete_removes_comment(): void {
		$post_generator = new PostGenerator( $this->pool );
		$post_id        = $post_generator->generate( [ 'post_type' => 'post' ] );

		$generator  = new CommentGenerator( $this->pool );
		$comment_id = $generator->generate( [ 'comment_post_ID' => $post_id ] );

		$generator->delete( $comment_id );

		$this->assertNull( get_comment( $comment_id ) );
	}

	// -- MediaGenerator tagging --

	public function test_media_generator_adds_gemogen_meta(): void {
		$generator     = new MediaGenerator();
		$attachment_id = $generator->generate();

		$meta = get_post_meta( $attachment_id, '_gemogen_generated', true );

		$this->assertEquals( 1, $meta );
	}

	public function test_media_generator_creates_attachment(): void {
		$generator     = new MediaGenerator();
		$attachment_id = $generator->generate();

		$post = get_post( $attachment_id );

		$this->assertNotNull( $post );
		$this->assertSame( 'attachment', $post->post_type );
		$this->assertSame( 'image/png', $post->post_mime_type );
	}

	public function test_media_generator_delete_removes_attachment(): void {
		$generator     = new MediaGenerator();
		$attachment_id = $generator->generate();

		$generator->delete( $attachment_id );

		$this->assertNull( get_post( $attachment_id ) );
	}

	// -- Cross-cutting: meta query --

	public function test_can_query_all_gemogen_posts_by_meta(): void {
		$generator = new PostGenerator( $this->pool );
		$id1       = $generator->generate( [ 'post_type' => 'post' ] );
		$id2       = $generator->generate( [ 'post_type' => 'post' ] );

		$query = new \WP_Query( [
			'meta_key'   => '_gemogen_generated',
			'meta_value' => '1',
			'post_type'  => 'any',
			'fields'     => 'ids',
		] );

		$this->assertContains( $id1, $query->posts );
		$this->assertContains( $id2, $query->posts );
	}
}
