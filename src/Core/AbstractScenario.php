<?php

declare(strict_types=1);

namespace Gemogen\Core;

use Gemogen\Contracts\ScenarioInterface;

abstract class AbstractScenario implements ScenarioInterface {

	protected Logger $logger;

	public function __construct( Logger $logger ) {
		$this->logger = $logger;
	}

	/**
	 * Merge user config with schema defaults.
	 *
	 * @param array<string, mixed> $config User-provided config.
	 * @return array<string, mixed> Config with defaults applied.
	 */
	protected function mergeDefaults( array $config ): array {
		$schema = $this->getSchema();

		foreach ( $schema['properties'] ?? [] as $key => $definition ) {
			if ( ! array_key_exists( $key, $config ) && array_key_exists( 'default', $definition ) ) {
				$config[ $key ] = $definition['default'];
			}
		}

		return $config;
	}

	/**
	 * Validate config against the schema.
	 *
	 * @param array<string, mixed> $config
	 * @return string[] List of error messages (empty = valid).
	 */
	public function validate( array $config ): array {
		$errors = [];
		$schema = $this->getSchema();

		foreach ( $schema['properties'] ?? [] as $key => $definition ) {
			if ( ! array_key_exists( $key, $config ) ) {
				continue;
			}

			$value = $config[ $key ];
			$type  = $definition['type'] ?? 'string';

			$valid = match ( $type ) {
				'integer' => is_int( $value ),
				'string'  => is_string( $value ),
				'boolean' => is_bool( $value ),
				'array'   => is_array( $value ),
				default   => true,
			};

			if ( ! $valid ) {
				$errors[] = sprintf( "Property '%s' must be of type '%s'.", $key, $type );
			}

			if ( $type === 'integer' && is_int( $value ) && $value < 0 ) {
				$errors[] = sprintf( "Property '%s' must be a non-negative integer.", $key );
			}
		}

		return $errors;
	}
}
