<?php

declare(strict_types=1);

namespace Gemogen\Sources;

use Gemogen\Contracts\ContentSourceInterface;

/**
 * Content source from CSV or JSON files.
 *
 * Reads content data from files. Supports two formats:
 *
 * JSON format:
 * {
 *     "titles": ["Title 1", "Title 2"],
 *     "content": ["Paragraph 1.", "Paragraph 2."],
 *     "fields": {
 *         "first_name": ["John", "Jane"],
 *         "category_name": ["Tech", "Science"]
 *     }
 * }
 *
 * CSV format:
 * title,content,first_name,category_name
 * "My Post","My paragraph.","John","Tech"
 * "Other Post","Another paragraph.","Jane","Science"
 */
class FileImportSource implements ContentSourceInterface {

	/** @var array<string, mixed> Parsed content data. */
	private array $data;

	/**
	 * @param string $filePath Path to a CSV or JSON file.
	 */
	public function __construct( string $filePath ) {
		$this->data = $this->parseFile( $filePath );
	}

	/**
	 * Create from a raw data array (for testing or programmatic use).
	 *
	 * @param array<string, mixed> $data
	 */
	public static function fromArray( array $data ): self {
		$instance       = new self( '' );
		$instance->data = $data;
		return $instance;
	}

	public function getTitle( string $context = 'post' ): ?string {
		$contextKey = "titles_{$context}";
		$titles     = $this->data[ $contextKey ] ?? $this->data['titles'] ?? [];

		if ( empty( $titles ) ) {
			return null;
		}

		return $titles[ array_rand( $titles ) ];
	}

	public function getContent( string $context = 'post' ): ?string {
		$contextKey = "content_{$context}";
		$paragraphs = $this->data[ $contextKey ] ?? $this->data['content'] ?? [];

		if ( empty( $paragraphs ) ) {
			return null;
		}

		$count  = min( rand( 2, 4 ), count( $paragraphs ) );
		$picked = [];

		for ( $i = 0; $i < $count; $i++ ) {
			$picked[] = '<!-- wp:paragraph --><p>' . $paragraphs[ array_rand( $paragraphs ) ] . '</p><!-- /wp:paragraph -->';
		}

		return implode( "\n\n", $picked );
	}

	public function getField( string $field ): ?string {
		$fields = $this->data['fields'] ?? [];

		if ( ! isset( $fields[ $field ] ) || empty( $fields[ $field ] ) ) {
			return null;
		}

		$values = $fields[ $field ];
		return $values[ array_rand( $values ) ];
	}

	public function supports( string $field ): bool {
		$fields = $this->data['fields'] ?? [];
		return isset( $fields[ $field ] ) && ! empty( $fields[ $field ] );
	}

	public function getPriority(): int {
		return 100; // Highest priority — file imports override everything.
	}

	/**
	 * Parse a CSV or JSON file into the standard data format.
	 *
	 * @return array<string, mixed>
	 */
	private function parseFile( string $filePath ): array {
		if ( empty( $filePath ) || ! file_exists( $filePath ) ) {
			return [];
		}

		$extension = strtolower( pathinfo( $filePath, PATHINFO_EXTENSION ) );

		return match ( $extension ) {
			'json' => $this->parseJson( $filePath ),
			'csv'  => $this->parseCsv( $filePath ),
			default => [],
		};
	}

	/**
	 * @return array<string, mixed>
	 */
	private function parseJson( string $filePath ): array {
		$content = file_get_contents( $filePath );

		if ( $content === false ) {
			return [];
		}

		$data = json_decode( $content, true );

		if ( ! is_array( $data ) ) {
			return [];
		}

		return $data;
	}

	/**
	 * Parse CSV into the standard format.
	 *
	 * Columns named 'title' or 'post_title' go into 'titles'.
	 * Columns named 'content' or 'post_content' go into 'content'.
	 * All other columns go into 'fields'.
	 *
	 * @return array<string, mixed>
	 */
	private function parseCsv( string $filePath ): array {
		$handle = fopen( $filePath, 'r' );

		if ( $handle === false ) {
			return [];
		}

		$headers = fgetcsv( $handle );

		if ( $headers === false ) {
			fclose( $handle );
			return [];
		}

		$headers = array_map( 'trim', $headers );

		$result = [
			'titles'  => [],
			'content' => [],
			'fields'  => [],
		];

		// Map column names to data buckets.
		$titleColumns   = [ 'title', 'post_title', 'name' ];
		$contentColumns = [ 'content', 'post_content', 'body', 'description' ];

		while ( ( $row = fgetcsv( $handle ) ) !== false ) {
			foreach ( $headers as $index => $header ) {
				$value = $row[ $index ] ?? '';

				if ( $value === '' ) {
					continue;
				}

				$headerLower = strtolower( $header );

				if ( in_array( $headerLower, $titleColumns, true ) ) {
					$result['titles'][] = $value;
				} elseif ( in_array( $headerLower, $contentColumns, true ) ) {
					$result['content'][] = $value;
				} else {
					$result['fields'][ $headerLower ][] = $value;
				}
			}
		}

		fclose( $handle );

		return $result;
	}
}
