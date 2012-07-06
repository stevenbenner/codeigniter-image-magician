<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * Image Magician
 *
 * ImageMagick interface class for CodeIgniter
 *
 * @category	Libraries
 * @author		Steven Benner
 * @link		https://github.com/stevenbenner/codeigniter-image-magician
 * @version		1.0
 */
class Image_magician
{
	protected $CI;

	// Example path override for Windows systems:
	// $config['imagemagick_path'] = 'C:\Progra~1\ImageMagick-6.6.6-Q16\\';
	// $this->load->library('image_magician', $config);

	// library path must include trailing slash
	private $imagemagick_path = '/usr/bin/';

	// internal variables used for building commands
	private $source_image	= '';
	private $new_image		= '';
	private $im_crop		= '';
	private $im_thumbnail	= '';
	private $im_fill		= '';
	private $im_opaque		= '';
	private $im_strip		= FALSE;
	private $im_quality		= 90;
	private $im_gravity		= '';
	private $im_background  = '';
	private $im_format		= '';
	private	$im_overwrite	= FALSE;

	/**
	 * Constructor
	 */
	public function __construct($params = array())
	{
		$this->CI =& get_instance();

		if (count($params) > 0)
		{
			foreach ($params as $key => $val)
			{
				if ($key === 'imagemagick_path')
				{
					// lib path must have trailing slash
					$this->imagemagick_path = rtrim($val, '/') . '/';
				}
				else
				{
					$this->$key = $val;
				}
			}
		}

		log_message('debug', 'Image Magician Class Initialized');
	}

	/*********************************************
	 * BASIC IMAGE MODIFICATION METHODS
	 *********************************************/

	public function create_thumbnail($source_image, $new_image, $width, $height = 0, $maintain_ratio = TRUE)
	{
		$modifier = $maintain_ratio ? '>' : '!';

		$width  = $width === 0 ? '' : $width;
		$height = $height === 0 ? '' : $height;

		$this->source_image = $source_image;
		$this->new_image = $new_image;
		$this->im_thumbnail = '"' . $width . 'x' . $height . $modifier . '"';
		$this->im_strip = TRUE;
		// replace transparency with white
		$this->im_fill = 'white';
		$this->im_opaque = 'none';

		$cmd = $this->compile_command();

		return $this->execute($cmd);
	}

	public function create_clipped_thumbnail($source_image, $new_image, $width, $height)
	{
		$info = $this->get_image_info($source_image);

		$image_aspect_ratio = $info['width'] / $info['height'];
		$crop_aspect_ratio = $width / $height;

		$x_constraint = $width;
		$y_constraint = $height;
		if ($image_aspect_ratio > $crop_aspect_ratio)
		{
			$x_constraint = round($width * $image_aspect_ratio);
		}
		elseif ($image_aspect_ratio < $crop_aspect_ratio)
		{
			$y_constraint = round($height / $image_aspect_ratio);
		}

		$resize_success = $this->create_thumbnail($source_image, $new_image, $x_constraint, $y_constraint);
		$crop_success = $this->crop_image($new_image, $width, $height, 0, 0, 'center', TRUE);

		return ($resize_success AND $crop_success);
	}

	public function crop_image($source_image, $width, $height, $top = 0, $left = 0, $gravity = 'center', $overwrite_original = FALSE, $new_image = '')
	{
		$this->source_image = $source_image;
		$this->new_image = $new_image;
		$this->im_overwrite = $overwrite_original;
		$this->im_crop = $width . 'x' . $height . '+' . $left . '+' . $top;
		if ( ! empty($gravity))
		{
			$this->im_gravity = $gravity;
		}

		$cmd = $this->compile_command();

		return $this->execute($cmd);
	}

	/**
	 * Change Image Format
	 *
	 * Processes an image and saves it as another format. The new image will
	 * have the same file name but with the extention you supply as the new
	 * format. This function will not delete the original image.
	 *
	 * NOTE: You do not need to use this function with any of the other image
	 * manipulation functions. Simply pass a filename with the appropriate
	 * file extension as the new_file parameter and the image will be
	 * converted to that format automatically.
	 *
	 * ImageMagick supports about 100 formats, but some have dependancies and
	 * not all are supported for writing. For details please read:
	 * http://www.imagemagick.org/www/formats.html
	 *
	 * @access	public
	 * @param	string	Path to the image file.
	 * @param	string	New file format (e.g. 'jpg', 'gif', 'png')
	 * @return	boolean	TRUE on success, FALSE on failure
	 */
	public function change_format($image_path, $new_format = 'jpg')
	{
		$dot_pos = strripos($image_path, '.');
		if (substr($image_path, ($pos + 1)) === $new_format)
		{
			return TRUE;
		}

		$this->source_image = $image_path;
		$this->new_image = substr($image_path, 0, $pos) . '.' . $new_format;
		$this->im_overwrite = $overwrite_original;
		$this->im_format = $new_format;

		$cmd = $this->compile_command();

		return $this->execute($cmd);
	}

	/**
	 * Strip EXIF
	 *
	 * Strips an image of its EXIF comments and original color profile. Note
	 * that this operation will result in reprossesing of the image. Images
	 * that are saved in a lossy format (e.g. jpeg) will lose a little bit of
	 * their original quality. This degradation should not be visible to the
	 * naked eye.
	 *
	 * If a new_image path is not supplied then the original with be replaced.
	 *
	 * @access	public
	 * @param	string	Path to the image file.
	 * @param	string	OPTIONAL Path to save the new file as
	 * @return	boolean	TRUE on success, FALSE on failure
	 */
	public function strip_exif($image_path, $new_image = '')
	{
		$this->source_image = $image_path;
		$this->new_image = $new_image;
		$this->im_strip = TRUE;
		$this->im_quality = 100;
		if (empty($new_image))
		{
			$this->im_overwrite = TRUE;
		}

		$cmd = $this->compile_command();

		return $this->execute($cmd);
	}

	/*********************************************
	 * MISCELLANEOUS METHODS
	 *********************************************/

	/**
	 * Get Image Information
	 *
	 * Wrapper for the getimagesize() function. Will return an array
	 * containing width, height, type and size_str keys.
	 *
	 * @access	public
	 * @param	string
	 * @return	mixed	Array or FALSE on failure
	 */
	public function get_image_info($image_path)
	{
		if ( ! file_exists($image_path))
		{
			return FALSE;
		}

		$vals = getimagesize($image_path);

		if ($vals === FALSE)
		{
			return FALSE;
		}
		else
		{
			return array(
				'width' => $vals['0'],
				'height' => $vals['1'],
				'type' => $vals['2'],
				'size_str' => $vals['3']
			);
		}
	}

	/**
	 * Is Animated
	 *
	 * Checks to see if a gif image is animated.
	 *
	 * Based on the code from "frank at huddler dot com" posted on PHP.net.
	 *
	 * @access	public
	 * @link	http://www.php.net/manual/en/function.imagecreatefromgif.php#88005
	 * @param	string	Path to the image file
	 * @return	boolean
	 */
	public function is_animated($image_path)
	{
		if ( ! @file_exists($image_path))
		{
			return FALSE;
		}

		if (substr(strrchr($image_path, '.'), 1) !== 'gif')
		{
			return FALSE;
		}

		if ( ! $fh = @fopen($image_path, 'rb'))
		{
			return FALSE;
		}

		flock($fh, LOCK_SH);

		$frame_count = 0;

		// an animated gif contains multiple "frames", with each frame having a
		// header made up of:
		// * a static 4-byte sequence (\x00\x21\xF9\x04)
		// * 4 variable bytes
		// * a static 2-byte sequence (\x00\x2C)

		// we read through the file til we reach the end of the file, or we've
		// found at least 2 frame headers
		while ( ! feof($fh) && $frame_count < 2)
		{
			$chunk = fread($fh, 1024 * 100); // read 100kb at a time
			$frame_count += preg_match_all('#\x00\x21\xF9\x04.{4}\x00\x2C#s', $chunk, $matches);
		}

		flock($fh, LOCK_UN);
		fclose($fh);

		return $frame_count > 1;
	}

	/*********************************************
	 * PRIVATE HELPER METHODS
	 *********************************************/

	/**
	 * Compile ImageMagick Command
	 *
	 * Builds an ImageMagick command based on the im_ variables.
	 *
	 * @access	private
	 * @return	string	ImageMagick command
	 */
	private function compile_command()
	{
		$cmd = '';

		if ($this->im_overwrite === FALSE)
		{
			$cmd .= ' "' . $this->source_image . '[0]"';
		}

		if ( ! empty($this->im_thumbnail))
		{
			$cmd .= ' -thumbnail ' . $this->im_thumbnail;
		}

		if ( ! empty($this->im_fill))
		{
			$cmd .= ' -fill ' . $this->im_fill;
		}

		if ( ! empty($this->im_opaque))
		{
			$cmd .= ' -opaque ' . $this->im_opaque;
		}

		if ( ! empty($this->im_crop))
		{
			$cmd .= ' -crop ' . $this->im_crop;
		}

		if ( ! empty($this->im_gravity))
		{
			$cmd .= ' -gravity ' . $this->im_gravity;
		}

		if ( ! empty($this->im_quality))
		{
			$cmd .= ' -quality ' . $this->im_quality;
		}

		if ( ! empty($this->im_background))
		{
			$cmd .= ' -background ' . $this->im_background;
		}

		if ( ! empty($this->im_format))
		{
			$cmd .= ' -format ' . $this->im_format;
		}

		if ($this->im_overwrite === TRUE)
		{
			$cmd .= ' "' . $this->source_image . '[0]"';
		}
		else
		{
			$cmd .= ' "' . $this->new_image . '"';
		}

		return trim($cmd, ' ');
	}

	/**
	 * Execute ImageMagick Command
	 *
	 * Invokes ImageMagick with the specified command.
	 *
	 * @access	private
	 * @param	string
	 * @return	boolean	TRUE on success or FALSE on failure
	 */
	private function execute($cmd)
	{
		$binary = $this->im_overwrite ? 'mogrify' : 'convert';

		$this->reset();

		$retval = null;
		$output = array();
		$cmd = $this->imagemagick_path . $binary . ' ' . $cmd . ' 2>&1';
		@exec($cmd, $output, $retval);

		if ($retval !== 0)
		{
			log_message('error', 'Failed to execute ImageMagick command "'.$cmd.'" -- MESSAGE: '.implode('::', $output));
			return FALSE;
		}
		else
		{
			log_message('debug', 'Executed ImageMagick command "'.$cmd.'"');
			return TRUE;
		}
	}

	/**
	 * Reset Image Magician Class
	 *
	 * Resets all of the im_ class variables to their defaults.
	 *
	 * @access	private
	 * @return	void
	 */
	private function reset()
	{
		$this->source_image		= '';
		$this->new_image		= '';

		$this->im_crop			= '';
		$this->im_thumbnail		= '';
		$this->im_fill			= '';
		$this->im_opaque		= '';
		$this->im_strip			= FALSE;
		$this->im_quality		= 90;
		$this->im_gravity		= '';
		$this->im_background	= '';
		$this->im_format		= '';
		$this->im_overwrite		= FALSE;
	}
}

/* End of file Image_magician.php */
/* Location: ./application/libraries/Image_magician.php */