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
			throw new \Exception( sprintf( __( 'Image %s can not be found, try another image.' ), $filename ) );
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
	            throw new \Exception( __( 'File is not an image, please use another file type.' ), 1);
		}
	}

	/**
	 * Save the image as the image type the original image was
	 *
	 * @param  String[type] $savePath     - The path to store the new image
	 * @param  string $imageQuality 	  - The qulaity level of image to create
	 */
	public function save_image( $savePath, $imageQuality="100" ) {
	    switch( $this->ext ) {
	        case 'image/jpg':
	        case 'image/jpeg':
	            if ( imagetypes() & IMG_JPG ) {
	                imagejpeg( $this->new_image, $savePath, $imageQuality );
	            }
	            break;
	        case 'image/gif':
	            if ( imagetypes() & IMG_GIF ) {
	                imagegif( $this->new_image, $savePath );
	            }
	            break;
	        case 'image/png':
	            $invertScaleQuality = 9 - round(($imageQuality/100) * 9);
	            if ( imagetypes() & IMG_PNG ) {
	                imagepng( $this->new_image, $savePath, $invertScaleQuality );
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

		$image_ratio = $image_width / $image_height;
		$wanted_image_ratio = $width / $height;
	
		// To center the new image
		$margin_y = 0;
		$margin_x = 0;
		$new_image_width = $image_width;
		$new_image_height = $image_height;
	
		// To keep all the image size inside the new one, resize the height and the width according to the ratio difference 
		if ( $image_ratio > $wanted_image_ratio ) {
			$new_image_width = $width;
			$new_image_height = floor( ( $image_height / $image_width ) * $width );
			$margin_y = ( $height - $new_image_height ) / 2;
		}
		else if ( $image_ratio < $wanted_image_ratio ) {
			$new_image_width = floor( ( $image_width / $image_height ) * $height );
			$new_image_height = $height;
			$margin_x = ( $width - $new_image_width ) / 2;
		}

		// The new image is created transparent with the given size
		$this->new_image = imagecreatetruecolor( $width, $height );
		$black = imagecolorallocate($this->new_image, 0, 0, 0);
		imagecolortransparent( $this->new_image, $black );
 
		imagecopyresampled( $this->new_image, $this->image, $margin_x, $margin_y, 0, 0, $new_image_width, $new_image_height, $image_width, $image_height );
	}
}
?>