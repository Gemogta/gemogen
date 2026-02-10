<?php

declare(strict_types=1);

namespace Gemogen\Generators;

use Gemogen\Contracts\GeneratorInterface;
use Gemogen\Core\ContentPool;

class CommentGenerator implements GeneratorInterface {

	private ContentPool $pool;

	public function __construct( ContentPool $pool ) {
		$this->pool = $pool;
	}

	public function generate( array $params = [] ): int {
		$post_id = $params['comment_post_ID'] ?? 0;

		if ( $post_id === 0 ) {
			throw new \RuntimeException( 'comment_post_ID is required for CommentGenerator.' );
		}

		$author_name = $this->pool->getField( 'first_name' ) ?? 'Gemogen User';
		$uid         = wp_rand( 1, 999 );

		$comment_id = wp_insert_comment(
			[
				'comment_post_ID'      => $post_id,
				'comment_content'      => $this->pool->getField( 'comment_text' ) ?? 'Great post!',
				'comment_author'       => $author_name . ' ' . $uid,
				'comment_author_email' => strtolower( $author_name ) . $uid . '@gemogen.test',
				'comment_approved'     => 1,
			]
		);

		if ( ! $comment_id ) {
			throw new \RuntimeException( 'Failed to create comment.' );
		}

		update_comment_meta( (int) $comment_id, '_gemogen_generated', 1 );

		return (int) $comment_id;
	}

	public function delete( int $id ): void {
		wp_delete_comment( $id, true );
	}
}
