<?php
namespace TFI;

require_once TFI_PATH . 'plugins/gif-endec-master/src/Decoder.php';
require_once TFI_PATH . 'plugins/gif-endec-master/src/Encoder.php';
require_once TFI_PATH . 'plugins/gif-endec-master/src/Color.php';
require_once TFI_PATH . 'plugins/gif-endec-master/src/Frame.php';
require_once TFI_PATH . 'plugins/gif-endec-master/src/Renderer.php';
require_once TFI_PATH . 'plugins/gif-endec-master/src/IO/PhpStream.php';
require_once TFI_PATH . 'plugins/gif-endec-master/src/IO/MemoryStream.php';
require_once TFI_PATH . 'plugins/gif-endec-master/src/IO/FileStream.php';
require_once TFI_PATH . 'plugins/gif-endec-master/src/Events/FrameDecodedEvent.php';
require_once TFI_PATH . 'plugins/gif-endec-master/src/Events/FrameRenderedEvent.php';
require_once TFI_PATH . 'plugins/gif-endec-master/src/Geometry/Point.php';
require_once TFI_PATH . 'plugins/gif-endec-master/src/Geometry/Rectangle.php';
require_once TFI_PATH . 'utilities/resize-image.php';

class ResizeGif {
    /**
     * Filename.
     * 
     * This is the filename of the gif
     * 
     * @since 1.1.5
     * 
     * @var string
     */
    private $filename;

    /**
     * Frames
     * 
     * Keep all frames path in mind with their duration
     * 
     * @since 1.1.5
     * 
     * @var array
     */
    private $frames;

    /**
     * Max_width.
     * 
     * The new max width of the gif when resize_to is called
     * We need to keep it to apply it to all frames
     * 
     * @since 1.1.5
     * 
     * @var int
     */
    private $max_width;

    /**
     * Max_height.
     * 
     * The new max height of the gif when resize_to is called
     * We need to keep it to apply it to all frames
     * 
     * @since 1.1.5
     * 
     * @var int
     */
    private $max_height;

    /**
     * Temp_folder_path.
     * 
     * The temporary folder where all gif frames are saved
     * This folder is created on resize_to method and then destroyed in save_image method
     * 
     * @since 1.1.5
     * 
     * @var string
     */
    private $temp_folder_path;

	/**
	 * Class constructor requires to send through the image filename
	 *
     * @since 1.1.5
	 * @param string $filename  - Filename of the gif you want to resize
     * @throws \Exception       - If the filename is not a gif 
	 */
	public function __construct( $filename )
	{
		if ( file_exists( $filename ) ) {
            if ( getimagesize( $filename )['mime'] == 'image/gif' ) {
                $this->frames = array();
                $this->filename = $filename;
            }
            else {
                throw new \Exception( __( 'File is not a gif, please give a gif image.' ) );
            }
		}
		else {
			throw new \Exception( __( 'The given gif can not be found for an unknown reason, try another image.' ) );
		}
    }
    
	/**
	 * Save the resized gif in a new place and then destroy the temporary folder
	 *
     * @since 1.1.5
	 * @param  String[type] $save_path		- The path to store the new image
	 */
	public function save_image( $save_path ) {
        if ( empty( $this->frames ) ) {
            return;
        }

        $gif = new \GIFEndec\Encoder();

        foreach ( $this->frames as $file => $duration ) {
            $stream = new \GIFEndec\IO\FileStream( $file );
            $frame = new \GIFEndec\Frame();
            $frame->setDisposalMethod( 1 );
            $frame->setStream( $stream );
            $frame->setDuration( $duration );
            $frame->setTransparentColor( new \GIFEndec\Color( 255, 255, 255 ) );
            $gif->addFrame( $frame );
        }
        
        $gif->addFooter();
        $gif->getStream()->copyContentsToFile( $save_path );
	}

	/**
	 * Resize the image to these set dimensions
	 *
     * @since 1.1.5
	 * @param  int $width        	- Max width of the image
	 * @param  int $height       	- Max height of the image
	 */
	public function resize_to( $width = 0, $height = 0 ) {
        list( $image_width, $image_height ) = getimagesize( $this->filename );

		if ( $width == 0 ) { $width = $image_width; }
        if ( $height == 0 ) { $height = $image_height; }

        // Don't resize the gif if the size is good
		if ( $width == $image_width && $height == $image_height ) {
			return;
        }

        $this->max_height = $height;
        $this->max_width = $width;

        /**
         * The shutdown hook is called to destroy the temporary folder
         * We did that because for no reason, the folder isn't empty after removing everything inside
         * It seems that an invisible "thing" is inside the folder during some microseconds or I don't know.
         * When the method is called with the hook, it works. 
         */
        $this->temp_folder_path = tfi_add_temp_folder();
        add_action( 'shutdown', array( $this, 'remove_temp_folder' ) );

        $gif_stream = new \GIFEndec\IO\FileStream( $this->filename );
        $gif_decoder = new \GIFEndec\Decoder( $gif_stream );

        $gif_decoder->decode( function (\GIFEndec\Events\FrameDecodedEvent $event) {
            // Convert frame index to zero-padded strings (001, 002, 003)
            $padded_index = str_pad( $event->frameIndex, 3, '0', STR_PAD_LEFT );
            $temp_name = $this->temp_folder_path . 'temp-' . $padded_index . '.gif';
            
            // Write frame images into temp directory
            $event->decodedFrame->getStream()->copyContentsToFile( $temp_name );
            
            $resize_image = new ResizeImage( $temp_name );
            $resize_image->resize_to( $this->max_width, $this->max_height );
            $resize_image->save_image( $temp_name );
        
            // Remember the file name and the duration of the frame
            $this->frames[$temp_name] = $event->decodedFrame->getDuration();
        });
    }

    /**
     * This call is done each time that a page is displayed.
     * It will delete all tfi temp folders and files.
     * 
     * @since 1.1.5
     */
	public function remove_temp_folder() {
        tfi_remove_temp_folder( $this->temp_folder_path );
    }
}