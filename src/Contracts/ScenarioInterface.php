<?php

declare(strict_types=1);

namespace Gemogen\Contracts;

interface ScenarioInterface {

	/**
	 * Unique identifier (e.g. 'core-content', 'woocommerce-store').
	 */
	public function getId(): string;

	/**
	 * Human-readable name.
	 */
	public function getName(): string;

	/**
	 * Description of what this scenario generates.
	 */
	public function getDescription(): string;

	/**
	 * JSON-Schema-like configuration definition.
	 *
	 * Used for validation, CLI help, and auto-generating admin UI forms.
	 *
	 * @return array{properties: array<string, array{type: string, default?: mixed, description?: string}>}
	 */
	public function getSchema(): array;

	/**
	 * Execute the scenario with the given configuration.
	 *
	 * @param array<string, mixed> $config Merged with schema defaults.
	 * @return array<string, int[]> Map of content type => created IDs (e.g. ['posts' => [1,2,3], 'users' => [4,5]]).
	 */
	public function execute( array $config = [] ): array;

	/**
	 * Rollback previously created content.
	 *
	 * @param array<string, int[]> $createdIds Same structure returned by execute().
	 */
	public function rollback( array $createdIds ): void;
}
