<?php
namespace TFI;

/**
 * Resize image class will allow you to resize an image
 *
 * Can resize to exact size
 * Max width size while keep aspect ratio
 * Max height size while keep aspect ratio
 * Automatic while keep aspect ratio
 * 
 * @author Paul Underwood https://dzone.com/articles/resize-image-class-php
 * @author Benoît Huftier Rewriting class to be used by Tempus Fugit Intranet plugin
 */
class ResizeImage {
	private $ext;
	private $image;
	private $new_image;

	/**
	 * Class constructor requires to send through the image filename
	 *
	 * @param string $filename - Filename of the image you want to resize
	 */
	public function __construct( $filename )
	{
		if ( file_exists( $filename ) ) {
			$this->set_image( $filename );
		}
		else {
			throw new \Exception( __( 'The given image can not be found for an unknown reason, try another image.' ) );
		}
	}

	/**
	 * Set the image variable by using image create
	 *
	 * @param string $filename - The image filename
	 */
	private function set_image( $filename ) {
		$size = getimagesize( $filename );
		$this->ext = $size['mime'];

		switch( $this->ext ) {
	        case 'image/jpg':
	        case 'image/jpeg':
	            $this->image = imagecreatefromjpeg( $filename );
	            break;
	        case 'image/gif':
	            $this->image = @imagecreatefromgif( $filename );
	            break;
	        case 'image/png':
	            $this->image = @imagecreatefrompng( $filename );
	            break;
	        default:
	            throw new \Exception( __( 'File is not an image, please use another file type.' ) );
		}

		if ( $this->image === false ) {
			throw new \Exception( __( 'Sorry, parsing your image failed for an unknown reason. First try to be sure that your image has good dimensions.' ) );
		}
	}

	/**
	 * Save the image as the image type the original image was
	 *
	 * @param  String[type] $save_path		- The path to store the new image
	 * @param  string $image_quality 	  	- The qulaity level of image to create
	 */
	public function save_image( $save_path, $image_quality="100" ) {
		if ( $this->new_image == null ) {
			return;
		}

	    switch( $this->ext ) {
	        case 'image/jpg':
	        case 'image/jpeg':
	            if ( imagetypes() & IMG_JPG ) {
					imagejpeg( $this->new_image, $save_path, $image_quality );
	            }
	            break;
	        case 'image/gif':
	            if ( imagetypes() & IMG_GIF ) {
	                imagegif( $this->new_image, $save_path );
	            }
	            break;
	        case 'image/png':
	            $invert_scale_quality = 9 - round(($image_quality/100) * 9);
	            if ( imagetypes() & IMG_PNG ) {
	                imagepng( $this->new_image, $save_path, $invert_scale_quality );
	            }
	            break;
	    }
	    imagedestroy( $this->new_image );
	}

	/**
	 * Resize the image to these set dimensions
	 *
	 * @param  int $width        	- Max width of the image
	 * @param  int $height       	- Max height of the image
	 */
	public function resize_to( $width = 0, $height = 0 ) {
	    $image_width = imagesx( $this->image );
		$image_height = imagesy( $this->image );

		if ( $width == 0 ) { $width = $image_width; }
		if ( $height == 0 ) { $height = $image_height; }

		if ( $width == $image_width && $height == $image_height ) {
			return;
		}

		// Calculate ratio of desired maximum sizes and original sizes.
		$width_ratio = $width / $image_width;
		$height_ratio = $height / $image_height;

		// Ratio used for calculating new image dimensions.
		$ratio = min( $width_ratio, $height_ratio );

		// Calculate new image dimensions.
		$new_image_width  = $image_width  * $ratio;
		$new_image_height = $image_height * $ratio;
		
		// To center the new image
		$margin_x = ( $width - $new_image_width ) / 2;
		$margin_y = ( $height - $new_image_height ) / 2;

		// The new image is created with the given size
		$this->new_image = imagecreatetruecolor( $width, $height );
		imagealphablending( $this->new_image, false );
		imagesavealpha( $this->new_image, true );
 
		imagecopyresampled( $this->new_image, $this->image, $margin_x, $margin_y, 0, 0, $new_image_width, $new_image_height, $image_width, $image_height );
	}
}
?>