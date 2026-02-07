<?php

declare(strict_types=1);

namespace Gemogen\Generators;

use Gemogen\Contracts\GeneratorInterface;

class TaxonomyGenerator implements GeneratorInterface {

	private const CATEGORY_NAMES = [
		'Technology', 'Business', 'Health', 'Science', 'Sports',
		'Education', 'Entertainment', 'Travel', 'Food', 'Lifestyle',
		'Finance', 'Design', 'Marketing', 'Development', 'Culture',
	];

	private const TAG_NAMES = [
		'wordpress', 'php', 'javascript', 'react', 'tutorial',
		'guide', 'tips', 'best-practices', 'performance', 'security',
		'development', 'design', 'ux', 'api', 'testing',
		'automation', 'workflow', 'tools', 'plugins', 'themes',
	];

	public function generate( array $params = [] ): int {
		$taxonomy = $params['taxonomy'] ?? 'category';
		$parent   = $params['parent'] ?? 0;

		$names = $taxonomy === 'post_tag' ? self::TAG_NAMES : self::CATEGORY_NAMES;
		$name  = $params['name'] ?? $names[ array_rand( $names ) ];
		$name  = $name . ' ' . wp_rand( 100, 9999 );

		$result = wp_insert_term(
			$name,
			$taxonomy,
			[
				'parent'      => $parent,
				'description' => "Generated term for {$taxonomy}.",
			]
		);

		if ( is_wp_error( $result ) ) {
			throw new \RuntimeException( 'Failed to create term: ' . $result->get_error_message() );
		}

		return (int) $result['term_id'];
	}

	public function delete( int $id ): void {
		$term = get_term( $id );

		if ( $term && ! is_wp_error( $term ) ) {
			wp_delete_term( $id, $term->taxonomy );
		}
	}
}
