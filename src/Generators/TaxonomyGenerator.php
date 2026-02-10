<?php

declare(strict_types=1);

namespace Gemogen\Generators;

use Gemogen\Contracts\GeneratorInterface;
use Gemogen\Core\ContentPool;

class TaxonomyGenerator implements GeneratorInterface {

	private ContentPool $pool;

	public function __construct( ContentPool $pool ) {
		$this->pool = $pool;
	}

	public function generate( array $params = [] ): int {
		$taxonomy = $params['taxonomy'] ?? 'category';
		$parent   = $params['parent'] ?? 0;

		$field = $taxonomy === 'post_tag' ? 'tag_name' : 'category_name';
		$name  = $params['name'] ?? $this->pool->getField( $field ) ?? 'Term';
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

		$term_id = (int) $result['term_id'];
		update_term_meta( $term_id, '_gemogen_generated', 1 );

		return $term_id;
	}

	public function delete( int $id ): void {
		$term = get_term( $id );

		if ( $term && ! is_wp_error( $term ) ) {
			wp_delete_term( $id, $term->taxonomy );
		}
	}
}
