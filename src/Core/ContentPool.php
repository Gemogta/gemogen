<?php

declare(strict_types=1);

namespace Gemogen\Core;

use Gemogen\Contracts\ContentSourceInterface;

/**
 * Aggregates content sources and serves content to generators.
 *
 * Sources are queried by priority (highest first). Falls back through
 * the chain until a source provides a value.
 */
class ContentPool {

	/** @var ContentSourceInterface[] Sorted by priority (desc). */
	private array $sources = [];

	private bool $sorted = false;

	/**
	 * Add a content source.
	 */
	public function addSource( ContentSourceInterface $source ): void {
		$this->sources[] = $source;
		$this->sorted    = false;
	}

	/**
	 * Get a title from the highest-priority source that provides one.
	 */
	public function getTitle( string $context = 'post' ): string {
		foreach ( $this->getSortedSources() as $source ) {
			$title = $source->getTitle( $context );
			if ( $title !== null ) {
				return $title;
			}
		}

		return 'Untitled';
	}

	/**
	 * Get body content from the highest-priority source that provides one.
	 */
	public function getContent( string $context = 'post' ): string {
		foreach ( $this->getSortedSources() as $source ) {
			$content = $source->getContent( $context );
			if ( $content !== null ) {
				return $content;
			}
		}

		return '';
	}

	/**
	 * Get a field value from the highest-priority source that supports it.
	 */
	public function getField( string $field ): ?string {
		foreach ( $this->getSortedSources() as $source ) {
			if ( $source->supports( $field ) ) {
				$value = $source->getField( $field );
				if ( $value !== null ) {
					return $value;
				}
			}
		}

		return null;
	}

	/**
	 * Check if any source supports a given field.
	 */
	public function supports( string $field ): bool {
		foreach ( $this->getSortedSources() as $source ) {
			if ( $source->supports( $field ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Get all registered sources (sorted by priority).
	 *
	 * @return ContentSourceInterface[]
	 */
	public function getSources(): array {
		return $this->getSortedSources();
	}

	/**
	 * @return ContentSourceInterface[]
	 */
	private function getSortedSources(): array {
		if ( ! $this->sorted ) {
			usort(
				$this->sources,
				fn( ContentSourceInterface $a, ContentSourceInterface $b ) => $b->getPriority() <=> $a->getPriority()
			);
			$this->sorted = true;
		}

		return $this->sources;
	}
}
