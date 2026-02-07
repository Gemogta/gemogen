<?php

declare(strict_types=1);

namespace Gemogen;

use InvalidArgumentException;

/**
 * Lightweight dependency injection container.
 */
class Container {

	/** @var array<string, callable> */
	private array $factories = [];

	/** @var array<string, mixed> */
	private array $instances = [];

	/**
	 * Bind a factory to the container.
	 */
	public function set( string $id, callable $factory ): void {
		$this->factories[ $id ] = $factory;
		unset( $this->instances[ $id ] );
	}

	/**
	 * Resolve a service from the container (singleton).
	 */
	public function get( string $id ): mixed {
		if ( isset( $this->instances[ $id ] ) ) {
			return $this->instances[ $id ];
		}

		if ( ! isset( $this->factories[ $id ] ) ) {
			throw new InvalidArgumentException( "Service not found: {$id}" );
		}

		$this->instances[ $id ] = ( $this->factories[ $id ] )( $this );

		return $this->instances[ $id ];
	}

	/**
	 * Check if a service is registered.
	 */
	public function has( string $id ): bool {
		return isset( $this->factories[ $id ] ) || isset( $this->instances[ $id ] );
	}
}
