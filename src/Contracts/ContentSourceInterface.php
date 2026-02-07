<?php

declare(strict_types=1);

namespace Gemogen\Contracts;

/**
 * Content source provides text/data for generators.
 *
 * Multiple sources can be stacked in a ContentPool with priority.
 * Generators ask the pool for content; it queries sources in order.
 */
interface ContentSourceInterface {

	/**
	 * Get a random title.
	 *
	 * @param string $context Content type context (e.g. 'post', 'page', 'product').
	 */
	public function getTitle( string $context = 'post' ): ?string;

	/**
	 * Get random body content (paragraphs).
	 *
	 * @param string $context Content type context.
	 */
	public function getContent( string $context = 'post' ): ?string;

	/**
	 * Get a random value for a specific field.
	 *
	 * @param string $field Field name (e.g. 'first_name', 'comment_text', 'category_name').
	 */
	public function getField( string $field ): ?string;

	/**
	 * Check if this source can provide data for a given field.
	 */
	public function supports( string $field ): bool;

	/**
	 * Source priority (higher = checked first).
	 */
	public function getPriority(): int;
}
