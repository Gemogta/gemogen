<?php

declare(strict_types=1);

namespace Gemogen\Generators;

use Gemogen\Contracts\GeneratorInterface;
use Gemogen\Core\ContentPool;

class PostGenerator implements GeneratorInterface {

	private ContentPool $pool;

	public function __construct( ContentPool $pool ) {
		$this->pool = $pool;
	}

	public function generate( array $params = [] ): int {
		$post_type = $params['post_type'] ?? 'post';
		$status    = $params['post_status'] ?? 'publish';
		$parent    = $params['post_parent'] ?? 0;

		$title   = $params['post_title'] ?? $this->pool->getTitle( $post_type );
		$content = $params['post_content'] ?? $this->pool->getContent( $post_type );

		$post_id = wp_insert_post(
			[
				'post_title'   => $title . ' #' . wp_rand( 100, 9999 ),
				'post_content' => $content,
				'post_status'  => $status,
				'post_type'    => $post_type,
				'post_parent'  => $parent,
				'post_author'  => get_current_user_id() ?: 1,
			],
			true
		);

		if ( is_wp_error( $post_id ) ) {
			throw new \RuntimeException( 'Failed to create post: ' . $post_id->get_error_message() );
		}

		update_post_meta( $post_id, '_gemogen_generated', 1 );

		return $post_id;
	}

	public function delete( int $id ): void {
		wp_delete_post( $id, true );
	}
}
