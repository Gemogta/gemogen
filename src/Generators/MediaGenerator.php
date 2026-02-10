<?php

declare(strict_types=1);

namespace Gemogen\Generators;

use Gemogen\Contracts\GeneratorInterface;

class MediaGenerator implements GeneratorInterface {

	public function generate( array $params = [] ): int {
		$width  = $params['width'] ?? 800;
		$height = $params['height'] ?? 600;
		$color  = $params['color'] ?? $this->randomColor();
		$title  = $params['title'] ?? 'Gemogen Placeholder ' . wp_rand( 100, 9999 );

		// Create a placeholder image.
		$image = imagecreatetruecolor( $width, $height );

		if ( $image === false ) {
			throw new \RuntimeException( 'Failed to create image resource.' );
		}

		// Parse hex color.
		$r = hexdec( substr( $color, 0, 2 ) );
		$g = hexdec( substr( $color, 2, 2 ) );
		$b = hexdec( substr( $color, 4, 2 ) );

		$bg = imagecolorallocate( $image, (int) $r, (int) $g, (int) $b );
		imagefill( $image, 0, 0, $bg );

		// Add text overlay with dimensions.
		$text_color = imagecolorallocate( $image, 255, 255, 255 );
		$text       = "{$width}x{$height}";
		imagestring( $image, 5, (int) ( $width / 2 - strlen( $text ) * 4.5 ), (int) ( $height / 2 - 7 ), $text, $text_color );

		// Save to uploads directory.
		$upload_dir = wp_upload_dir();
		$filename   = 'gemogen-' . wp_rand( 10000, 99999 ) . '.png';
		$filepath   = $upload_dir['path'] . '/' . $filename;

		imagepng( $image, $filepath );
		imagedestroy( $image );

		$attachment_id = wp_insert_attachment(
			[
				'post_title'     => $title,
				'post_mime_type' => 'image/png',
				'post_status'    => 'inherit',
			],
			$filepath
		);

		if ( is_wp_error( $attachment_id ) || ! $attachment_id ) {
			throw new \RuntimeException( 'Failed to create media attachment.' );
		}

		// Generate attachment metadata.
		require_once ABSPATH . 'wp-admin/includes/image.php';
		$metadata = wp_generate_attachment_metadata( $attachment_id, $filepath );
		wp_update_attachment_metadata( $attachment_id, $metadata );

		update_post_meta( $attachment_id, '_gemogen_generated', 1 );

		return $attachment_id;
	}

	public function delete( int $id ): void {
		wp_delete_attachment( $id, true );
	}

	private function randomColor(): string {
		$colors = [ '3498db', 'e74c3c', '2ecc71', '9b59b6', 'f39c12', '1abc9c', 'e67e22', '34495e' ];
		return $colors[ array_rand( $colors ) ];
	}
}
