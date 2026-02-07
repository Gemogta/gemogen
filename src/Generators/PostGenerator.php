<?php

declare(strict_types=1);

namespace Gemogen\Generators;

use Gemogen\Contracts\GeneratorInterface;

class PostGenerator implements GeneratorInterface {

	private const TITLES = [
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

	private const PARAGRAPHS = [
		'Lorem ipsum dolor sit amet, consectetur adipiscing elit. Sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris.',
		'Duis aute irure dolor in reprehenderit in voluptate velit esse cillum dolore eu fugiat nulla pariatur. Excepteur sint occaecat cupidatat non proident, sunt in culpa qui officia deserunt mollit.',
		'Curabitur pretium tincidunt lacus. Nulla gravida orci a odio. Nullam varius, turpis et commodo pharetra, est eros bibendum elit, nec luctus magna felis sollicitudin mauris.',
		'Praesent dapibus, neque id cursus faucibus, tortor neque egestas augue, eu vulputate magna eros eu erat. Aliquam erat volutpat. Nam dui mi, tincidunt quis, accumsan porttitor.',
		'Vestibulum ante ipsum primis in faucibus orci luctus et ultrices posuere cubilia curae; Donec velit neque, auctor sit amet aliquam vel, ullamcorper sit amet ligula.',
	];

	public function generate( array $params = [] ): int {
		$post_type = $params['post_type'] ?? 'post';
		$status    = $params['post_status'] ?? 'publish';
		$parent    = $params['post_parent'] ?? 0;

		$title   = $params['post_title'] ?? self::TITLES[ array_rand( self::TITLES ) ];
		$content = $params['post_content'] ?? $this->generateContent();

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

		return $post_id;
	}

	public function delete( int $id ): void {
		wp_delete_post( $id, true );
	}

	private function generateContent(): string {
		$count      = wp_rand( 2, 4 );
		$paragraphs = [];

		for ( $i = 0; $i < $count; $i++ ) {
			$paragraphs[] = '<!-- wp:paragraph --><p>' . self::PARAGRAPHS[ array_rand( self::PARAGRAPHS ) ] . '</p><!-- /wp:paragraph -->';
		}

		return implode( "\n\n", $paragraphs );
	}
}
