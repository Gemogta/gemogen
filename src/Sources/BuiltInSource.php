<?php

declare(strict_types=1);

namespace Gemogen\Sources;

use Gemogen\Contracts\ContentSourceInterface;

/**
 * Built-in content source — hardcoded lorem ipsum and sample data.
 *
 * This is the lowest-priority fallback that always provides a value.
 */
class BuiltInSource implements ContentSourceInterface {

	private const TITLES = [
		'post' => [
			'Getting Started with WordPress',
			'How to Build a Website',
			'Understanding Custom Post Types',
			'A Guide to Plugin Development',
			'Best Practices for Theme Design',
			'Optimizing Your WordPress Site',
			'Working with REST API',
			'Introduction to Block Editor',
			'Managing Media in WordPress',
			'Security Tips for WordPress',
			'Understanding WordPress Hooks',
			'Creating Custom Taxonomies',
			'WordPress Performance Tuning',
			'Building Custom Widgets',
			'Mastering WordPress Templates',
		],
		'page' => [
			'About Us',
			'Contact',
			'Services',
			'Our Team',
			'FAQ',
			'Privacy Policy',
			'Terms of Service',
			'Portfolio',
			'Blog',
			'Careers',
		],
	];

	private const PARAGRAPHS = [
		'Lorem ipsum dolor sit amet, consectetur adipiscing elit. Sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris.',
		'Duis aute irure dolor in reprehenderit in voluptate velit esse cillum dolore eu fugiat nulla pariatur. Excepteur sint occaecat cupidatat non proident, sunt in culpa qui officia deserunt mollit.',
		'Curabitur pretium tincidunt lacus. Nulla gravida orci a odio. Nullam varius, turpis et commodo pharetra, est eros bibendum elit, nec luctus magna felis sollicitudin mauris.',
		'Praesent dapibus, neque id cursus faucibus, tortor neque egestas augue, eu vulputate magna eros eu erat. Aliquam erat volutpat. Nam dui mi, tincidunt quis, accumsan porttitor.',
		'Vestibulum ante ipsum primis in faucibus orci luctus et ultrices posuere cubilia curae; Donec velit neque, auctor sit amet aliquam vel, ullamcorper sit amet ligula.',
	];

	private const FIELDS = [
		'first_name' => [
			'Alice', 'Bob', 'Charlie', 'Diana', 'Edward',
			'Fiona', 'George', 'Helen', 'Ivan', 'Julia',
			'Kevin', 'Laura', 'Mike', 'Nina', 'Oscar',
		],
		'last_name' => [
			'Smith', 'Johnson', 'Williams', 'Brown', 'Jones',
			'Garcia', 'Miller', 'Davis', 'Rodriguez', 'Martinez',
			'Anderson', 'Taylor', 'Thomas', 'Moore', 'Jackson',
		],
		'category_name' => [
			'Technology', 'Business', 'Health', 'Science', 'Sports',
			'Education', 'Entertainment', 'Travel', 'Food', 'Lifestyle',
			'Finance', 'Design', 'Marketing', 'Development', 'Culture',
		],
		'tag_name' => [
			'wordpress', 'php', 'javascript', 'react', 'tutorial',
			'guide', 'tips', 'best-practices', 'performance', 'security',
			'development', 'design', 'ux', 'api', 'testing',
			'automation', 'workflow', 'tools', 'plugins', 'themes',
		],
		'comment_text' => [
			'Great article! Very helpful for beginners.',
			'Thanks for sharing this. I learned a lot.',
			'This is exactly what I was looking for.',
			'Well written and easy to follow.',
			'Could you expand on this topic in a future post?',
			'I have been using this approach for a while and it works great.',
			'Excellent tips! I will definitely try these out.',
			'This saved me hours of debugging. Thank you!',
			'Clear and concise explanation. Bookmarked!',
			'Would love to see more content like this.',
		],
	];

	public function getTitle( string $context = 'post' ): string {
		$titles = self::TITLES[ $context ] ?? self::TITLES['post'];
		return $titles[ array_rand( $titles ) ];
	}

	public function getContent( string $context = 'post' ): string {
		$count      = rand( 2, 4 );
		$paragraphs = [];

		for ( $i = 0; $i < $count; $i++ ) {
			$paragraphs[] = '<!-- wp:paragraph --><p>' . self::PARAGRAPHS[ array_rand( self::PARAGRAPHS ) ] . '</p><!-- /wp:paragraph -->';
		}

		return implode( "\n\n", $paragraphs );
	}

	public function getField( string $field ): ?string {
		if ( ! isset( self::FIELDS[ $field ] ) ) {
			return null;
		}

		$values = self::FIELDS[ $field ];
		return $values[ array_rand( $values ) ];
	}

	public function supports( string $field ): bool {
		return isset( self::FIELDS[ $field ] );
	}

	public function getPriority(): int {
		return 0; // Lowest priority — always the fallback.
	}
}
