<?php

declare(strict_types=1);

namespace Gemogen\Generators;

use Gemogen\Contracts\GeneratorInterface;

class CommentGenerator implements GeneratorInterface {

	private const COMMENTS = [
		'Great article! Very helpful for beginners.',
		'Thanks for sharing this. I learned a lot.',
		'This is exactly what I was looking for.',
		'Well written and easy to follow.',
		'Could you expand on this topic in a future post?',
		'I have been using this approach for a while and it works great.',
		'Excellent tips! I will definitely try these out.',
		'This saved me hours of debugging. Thank you!',
		'Clear and concise explanation. Bookmarked!',
		'Would love to see more content like this.',
	];

	public function generate( array $params = [] ): int {
		$post_id = $params['comment_post_ID'] ?? 0;

		if ( $post_id === 0 ) {
			throw new \RuntimeException( 'comment_post_ID is required for CommentGenerator.' );
		}

		$comment_id = wp_insert_comment(
			[
				'comment_post_ID' => $post_id,
				'comment_content' => self::COMMENTS[ array_rand( self::COMMENTS ) ],
				'comment_author'  => 'Gemogen User ' . wp_rand( 1, 999 ),
				'comment_author_email' => 'user' . wp_rand( 1, 999 ) . '@gemogen.test',
				'comment_approved' => 1,
			]
		);

		if ( ! $comment_id ) {
			throw new \RuntimeException( 'Failed to create comment.' );
		}

		return (int) $comment_id;
	}

	public function delete( int $id ): void {
		wp_delete_comment( $id, true );
	}
}
