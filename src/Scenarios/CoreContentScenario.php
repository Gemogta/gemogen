<?php

declare(strict_types=1);

namespace Gemogen\Scenarios;

use Gemogen\Core\AbstractScenario;
use Gemogen\Core\Logger;
use Gemogen\Generators\CommentGenerator;
use Gemogen\Generators\MediaGenerator;
use Gemogen\Generators\PostGenerator;
use Gemogen\Generators\TaxonomyGenerator;
use Gemogen\Generators\UserGenerator;

class CoreContentScenario extends AbstractScenario {

	private PostGenerator $posts;
	private UserGenerator $users;
	private TaxonomyGenerator $taxonomies;
	private CommentGenerator $comments;
	private MediaGenerator $media;

	public function __construct(
		Logger $logger,
		PostGenerator $posts,
		UserGenerator $users,
		TaxonomyGenerator $taxonomies,
		CommentGenerator $comments,
		MediaGenerator $media,
	) {
		parent::__construct( $logger );
		$this->posts      = $posts;
		$this->users      = $users;
		$this->taxonomies = $taxonomies;
		$this->comments   = $comments;
		$this->media      = $media;
	}

	public function getId(): string {
		return 'core-content';
	}

	public function getName(): string {
		return 'WordPress Core Content';
	}

	public function getDescription(): string {
		return 'Generates posts, pages, categories, tags, users, comments, and media attachments.';
	}

	public function getSchema(): array {
		return [
			'properties' => [
				'posts'      => [ 'type' => 'integer', 'default' => 10, 'description' => 'Number of posts to create' ],
				'pages'      => [ 'type' => 'integer', 'default' => 5, 'description' => 'Number of pages to create' ],
				'users'      => [ 'type' => 'integer', 'default' => 3, 'description' => 'Number of users to create' ],
				'categories' => [ 'type' => 'integer', 'default' => 5, 'description' => 'Number of categories to create' ],
				'tags'       => [ 'type' => 'integer', 'default' => 10, 'description' => 'Number of tags to create' ],
				'comments'   => [ 'type' => 'integer', 'default' => 20, 'description' => 'Number of comments to create' ],
				'media'      => [ 'type' => 'integer', 'default' => 5, 'description' => 'Number of media items to create' ],
			],
		];
	}

	public function execute( array $config = [] ): array {
		$config = $this->mergeDefaults( $config );

		$created = [
			'categories' => [],
			'tags'       => [],
			'users'      => [],
			'media'      => [],
			'posts'      => [],
			'pages'      => [],
			'comments'   => [],
		];

		try {
			// 1. Categories.
			$this->logger->info( "Creating {$config['categories']} categories..." );
			for ( $i = 0; $i < $config['categories']; $i++ ) {
				$created['categories'][] = $this->taxonomies->generate( [ 'taxonomy' => 'category' ] );
			}

			// 2. Tags.
			$this->logger->info( "Creating {$config['tags']} tags..." );
			for ( $i = 0; $i < $config['tags']; $i++ ) {
				$created['tags'][] = $this->taxonomies->generate( [ 'taxonomy' => 'post_tag' ] );
			}

			// 3. Users.
			$this->logger->info( "Creating {$config['users']} users..." );
			$roles = [ 'editor', 'author', 'subscriber' ];
			for ( $i = 0; $i < $config['users']; $i++ ) {
				$created['users'][] = $this->users->generate( [ 'role' => $roles[ $i % count( $roles ) ] ] );
			}

			// 4. Media.
			$this->logger->info( "Creating {$config['media']} media items..." );
			for ( $i = 0; $i < $config['media']; $i++ ) {
				$created['media'][] = $this->media->generate();
			}

			// 5. Posts (assign random categories/tags and featured images).
			$this->logger->info( "Creating {$config['posts']} posts..." );
			for ( $i = 0; $i < $config['posts']; $i++ ) {
				$post_id = $this->posts->generate( [ 'post_type' => 'post' ] );
				$created['posts'][] = $post_id;

				// Assign a random category if available.
				if ( ! empty( $created['categories'] ) ) {
					wp_set_post_terms( $post_id, [ $created['categories'][ array_rand( $created['categories'] ) ] ], 'category' );
				}

				// Assign a random tag if available.
				if ( ! empty( $created['tags'] ) ) {
					wp_set_post_terms( $post_id, [ $created['tags'][ array_rand( $created['tags'] ) ] ], 'post_tag' );
				}

				// Set featured image if available.
				if ( ! empty( $created['media'] ) ) {
					set_post_thumbnail( $post_id, $created['media'][ array_rand( $created['media'] ) ] );
				}
			}

			// 6. Pages.
			$this->logger->info( "Creating {$config['pages']} pages..." );
			for ( $i = 0; $i < $config['pages']; $i++ ) {
				$created['pages'][] = $this->posts->generate( [ 'post_type' => 'page' ] );
			}

			// 7. Comments (distribute across posts).
			$this->logger->info( "Creating {$config['comments']} comments..." );
			for ( $i = 0; $i < $config['comments']; $i++ ) {
				$target_post = ! empty( $created['posts'] )
					? $created['posts'][ $i % count( $created['posts'] ) ]
					: 0;

				if ( $target_post > 0 ) {
					$created['comments'][] = $this->comments->generate( [ 'comment_post_ID' => $target_post ] );
				}
			}
		} catch ( \Throwable $e ) {
			$this->logger->error( 'Error during execution: ' . $e->getMessage() );
			$this->logger->warning( 'Rolling back partial content...' );
			$this->rollback( $created );
			throw $e;
		}

		return $created;
	}

	public function rollback( array $createdIds ): void {
		// Rollback in reverse order of creation.
		foreach ( array_reverse( $createdIds ) as $type => $ids ) {
			foreach ( $ids as $id ) {
				match ( $type ) {
					'posts', 'pages', 'media' => $this->posts->delete( $id ),
					'users'                   => $this->users->delete( $id ),
					'categories', 'tags'      => $this->taxonomies->delete( $id ),
					'comments'                => $this->comments->delete( $id ),
					default                   => null,
				};
			}
		}

		$this->logger->info( 'Rollback complete for core-content.' );
	}
}
