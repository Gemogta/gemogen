<?php

declare(strict_types=1);

namespace Gemogen\Contracts;

interface GeneratorInterface {

	/**
	 * Generate a single piece of content.
	 *
	 * @param array<string, mixed> $params Generation parameters.
	 * @return int The created object ID.
	 */
	public function generate( array $params = [] ): int;

	/**
	 * Delete a previously generated object.
	 *
	 * @param int $id The object ID to delete.
	 */
	public function delete( int $id ): void;
}
