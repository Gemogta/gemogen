<?php

declare(strict_types=1);

namespace Gemogen\Sources;

use Gemogen\Contracts\ContentSourceInterface;

/**
 * User-defined content templates stored in WordPress options.
 *
 * Users can define custom titles, paragraphs, and field values
 * through the admin UI or programmatically.
 *
 * Option format (stored as 'gemogen_user_templates'):
 * [
 *     'titles'  => ['My Custom Title', 'Another Title'],
 *     'content' => ['My custom paragraph.', 'Another paragraph.'],
 *     'fields'  => [
 *         'first_name' => ['John', 'Jane'],
 *         'category_name' => ['My Category'],
 *     ],
 * ]
 */
class UserTemplateSource implements ContentSourceInterface {

	private const OPTION_KEY = 'gemogen_user_templates';

	/** @var array<string, mixed>|null Cached templates. */
	private ?array $templates = null;

	public function getTitle( string $context = 'post' ): ?string {
		$templates = $this->getTemplates();

		// Check context-specific titles first, then generic.
		$contextKey = "titles_{$context}";
		$titles     = $templates[ $contextKey ] ?? $templates['titles'] ?? [];

		if ( empty( $titles ) ) {
			return null;
		}

		return $titles[ array_rand( $titles ) ];
	}

	public function getContent( string $context = 'post' ): ?string {
		$templates = $this->getTemplates();

		$contextKey = "content_{$context}";
		$paragraphs = $templates[ $contextKey ] ?? $templates['content'] ?? [];

		if ( empty( $paragraphs ) ) {
			return null;
		}

		// Pick 2-4 random paragraphs.
		$count  = min( rand( 2, 4 ), count( $paragraphs ) );
		$picked = [];

		for ( $i = 0; $i < $count; $i++ ) {
			$picked[] = '<!-- wp:paragraph --><p>' . $paragraphs[ array_rand( $paragraphs ) ] . '</p><!-- /wp:paragraph -->';
		}

		return implode( "\n\n", $picked );
	}

	public function getField( string $field ): ?string {
		$templates = $this->getTemplates();
		$fields    = $templates['fields'] ?? [];

		if ( ! isset( $fields[ $field ] ) || empty( $fields[ $field ] ) ) {
			return null;
		}

		$values = $fields[ $field ];
		return $values[ array_rand( $values ) ];
	}

	public function supports( string $field ): bool {
		$templates = $this->getTemplates();
		$fields    = $templates['fields'] ?? [];

		return isset( $fields[ $field ] ) && ! empty( $fields[ $field ] );
	}

	public function getPriority(): int {
		return 50; // Higher than built-in, lower than file import.
	}

	/**
	 * Save user templates.
	 *
	 * @param array<string, mixed> $templates
	 */
	public static function save( array $templates ): void {
		if ( function_exists( 'update_option' ) ) {
			update_option( self::OPTION_KEY, $templates );
		}
	}

	/**
	 * Get the stored templates (cached per-request).
	 *
	 * @return array<string, mixed>
	 */
	private function getTemplates(): array {
		if ( $this->templates === null ) {
			if ( function_exists( 'get_option' ) ) {
				$this->templates = get_option( self::OPTION_KEY, [] );
			} else {
				$this->templates = [];
			}

			if ( ! is_array( $this->templates ) ) {
				$this->templates = [];
			}
		}

		return $this->templates;
	}
}
