<?php

declare(strict_types=1);

namespace Gemogen\Generators;

use Gemogen\Contracts\GeneratorInterface;

class UserGenerator implements GeneratorInterface {

	private const FIRST_NAMES = [
		'Alice', 'Bob', 'Charlie', 'Diana', 'Edward',
		'Fiona', 'George', 'Helen', 'Ivan', 'Julia',
		'Kevin', 'Laura', 'Mike', 'Nina', 'Oscar',
	];

	private const LAST_NAMES = [
		'Smith', 'Johnson', 'Williams', 'Brown', 'Jones',
		'Garcia', 'Miller', 'Davis', 'Rodriguez', 'Martinez',
		'Anderson', 'Taylor', 'Thomas', 'Moore', 'Jackson',
	];

	public function generate( array $params = [] ): int {
		$role  = $params['role'] ?? 'subscriber';
		$first = self::FIRST_NAMES[ array_rand( self::FIRST_NAMES ) ];
		$last  = self::LAST_NAMES[ array_rand( self::LAST_NAMES ) ];
		$uid   = wp_rand( 1000, 99999 );

		$username = strtolower( $first . '.' . $last . $uid );

		$user_id = wp_insert_user(
			[
				'user_login'   => $username,
				'user_pass'    => wp_generate_password( 24 ),
				'user_email'   => $username . '@gemogen.test',
				'first_name'   => $first,
				'last_name'    => $last,
				'display_name' => $first . ' ' . $last,
				'role'         => $role,
			]
		);

		if ( is_wp_error( $user_id ) ) {
			throw new \RuntimeException( 'Failed to create user: ' . $user_id->get_error_message() );
		}

		return $user_id;
	}

	public function delete( int $id ): void {
		require_once ABSPATH . 'wp-admin/includes/user.php';
		wp_delete_user( $id );
	}
}
