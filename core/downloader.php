<?php 
namespace Core;
/**
 * PHP Advanced Downloader
 *
 * This class is used for downloading files or data string which could be data of files 'binary string'
 * or just a plain string 'txt string'.
 * downloading in data mode allows to specify file name with extension to download in
 * downloading in file mode allows only to change downloade file name
 *
 * this class benifits:
 * protect files from direct access, control resumability, control speed, control download name,
 * control downloading with authentication, calculate downloaded bandwidth
 *
 * Some ideas has been taken from 'Nguyen Quoc Bao' , but code built from scratch
 *
 * @author    ahmed saad <a7mad.sa3d.2014@gmail.com>
 * @copyright ahmed saad 22 October 2014
 * @link      http://www.facebook.com/abu.sa3d
 * @version   1.2.1 < Updated 7 Jul 2016 >
 * @package   PHP Advanced Downloader
 * @license   GPL GNU V3.0 
 * 
 */

use Core\HttpErrorResponseTrait;

class Downloader
{

	use HttpErrorResponseTrait;
	
	/**
	 * Constants for Download Modes
	 */
	const DOWNLOAD_FILE = 1;
	const DOWNLOAD_DATA = 2;
	
	protected $_data;

	protected $_filename;
	protected $_file_basename;
	protected $_file_extension;

	protected $_mime;
	protected $_extensions_mime_arr;

	protected $_last_modified_time;

	protected $_full_size;
	protected $_required_download_size;
	protected $_downloaded = 0;

	protected $_seek_start = 0;
	protected $_seek_end;

	protected $_is_partial;
	protected $_is_resumable = true;

	protected $_speed_limit;

	protected $_buffer_size = 2048;

	protected $_auto_exit = false;

	protected $_use_authentiaction = false;
	protected $_auth_username;
	protected $_auth_password;
	protected $_auth_callback;

	protected $_record_downloaded;
	protected $_record_downloaded_callback;


	/**
	 * Downloader constructor
	 *
	 * Constructor will prepare data file and calculate start byte and end byte
	 * 
	 * @param string  $to_download    file path or data string
	 * @param integer $download_mode  file mode or data mode
	 */
	public function __construct( $to_download, $download_mode = self::DOWNLOAD_FILE )
	{

		global $HTTP_SERVER_VARS;

		$this->_initialize();

		if( $download_mode == self::DOWNLOAD_FILE )
		{
			// Download by file path

			$this->_download_mode = $download_mode;
			
			// Check if File exists and is file or not
			if( !is_file( $to_download ) )
			{
				// Not Found
				// $this->_setHeader( 'HTTP/1.0 404 File Not Found' );
				
				// exit();
				$this->httpError( 404, 'File Not Found' );

			}// Try To Open File for read
			else if( !is_readable( $to_download ) || !( $this->_data = fopen( $to_download, 'rb' ) ) )
			{
				// File is not readable, couldnot open
				// $this->_setHeader( 'HTTP/1.0 403 Forbidden File Not Accissible.' );
				
				// exit();

				$this->httpError( 403, 'File Not Accissible' );
			}

			$this->_full_size = filesize( $to_download );

			$info = pathinfo( $to_download );

			$this->_filename = $info['filename'];
			$this->_file_basename = $info[ 'basename' ];
			$this->_file_extension= $info[ 'extension' ];

			$this->_mime = $this->_getMimeOf( $this->_file_extension );

			$this->_last_modified_time = filemtime( $to_download );

			

		}
		else if( $download_mode == self::DOWNLOAD_DATA )
		{
			// Download By Data String

			$this->_download_mode = $download_mode;

			if( is_file( $to_download ) )
			{
				// the given is a file so we will get it as string data
				$this->_data = file_get_contents( $to_download );

				// $this->_data = implode( '', file( $to_download ) );

				$info = pathinfo( $to_download );

				$this->_filename = $info[ 'filename' ];

				$this->_basename = $info[ 'basename' ];

				$this->_file_extension = $info[ 'extension' ];

			}
			else
			{
				// The give data may be binary data or basic string or whatever in string formate
				// so we will assume by default that the given string is basic txt file
				// you can change this behaviour via setDownloadName() method and pass to it file basename

				$this->_data = $to_download;

				$this->_filename = 'file';
				
				$this->_file_extension = 'txt';
				
				$this->_basename = $this->_filename . '.' . $this->_file_extension;
			}


			$this->_full_size = strlen( $this->_data );

			

			$this->_mime = $this->_getMimeOf( $this->_file_extension );

			$this->_last_modified_time = time();

		}
		else
		{
			// Bad Request
			// $this->_setHeader( 'HTTP/1.0 400 Bad Request Download Mode Error' );

			// exit();

			$this->httpError( 400, 'Bad Request, Undefined Download Mode' );

		}


		// Range
		if( isset( $_SERVER['HTTP_RANGE'] ) || isset( $HTTP_SERVER_VARS['HTTP_RANGE'] ) )
		{
			
			// Partial Download Request, for Resumable
			$this->_is_partial = true;

			$http_range = isset( $_SERVER['HTTP_RANGE'] ) ?  $_SERVER['HTTP_RANGE'] : $HTTP_SERVER_VARS['HTTP_RANGE'];		

			if( stripos( 'bytes' ) === false )
			{
				// Bad Request for range
				// $this->_setHeader( 'HTTP/1.0 416 Requested Range Not Satisfiable' );

				// exit();

				$this->httpError( 416 );
			}

			$range = substr( $http_range , strlen('bytes=') );

			// $range = str_replace( 'bytes=', '', $http_range );

			$range = explode( '-', $range, 3 );

			// full_size = 100byte
			// range = bytes=0-99
			// seek_start = 0, seek_end = 99

			// Set Seek
			// Let Keep Default behaviour to be resumable, later immediately before downloading
			// we will check if resumability is turned off we will ovverride the comming three lines to be non resumable
			$this->_seek_start = ( $range[0] > 0 && $range[0] < $this->_full_size - 1 ) ? $range[0] : 0;

			$this->_seek_end = ( $range[1] > 0 && $range[1] < $this->_full_size && $range[1] > $this->_seek_start ) ? $range[1] : $this->_full_size - 1;

			$this->_required_download_size = $this->_seek_end - $this->_seek_start + 1;

		}
		else
		{
			// Full File Download Request
			$this->_is_partial = false;

			$this->_seek_start = 0;

			$this->_seek_end = $this->_full_size - 1;

			$this->_required_download_size = $this->_full_size;
		}


		// Construct End
	}

	

	/**
	 * Start download process
	 *
	 * @return  null
	 */
	public function download()
	{

		// Actual Download Steps

		// Check If Authentication Required
		if( $this->_use_authentiaction )
		{

			// Try To Use Basic WWW-Authenticate
			if( !$this->_authenticate() )
			{

				// Authenticate Headers, this Will Popup authentication process then redirect back to the same request with provided username, password
				
				$this->_setHeader( 'WWW-Authenticate', 'Basic realm="This Process Require authentication, please provide your Credentials."' );

				$this->_setHeader( 'HTTP/1.0 401 Unauthorized' );

				$this->_setHeader( 'Status', '401 Unauthorized' );

				// Exit if auto exit is enabled
				if( $this->_auto_exit )

					exit();

				return false; // Making sure That script stops here
			}

		}

		
		// check resumability, Headers Stage
		if( $this->_is_partial )
		{
			// Resumable Request
			if( $this->_is_resumable )
			{
				// Allow to resume

				// Resume Headers >>>
				$this->_setHeader( 'HTTP/1.0 206 Partial Content' );

				$this->_setHeader( 'Status', '206 Partial Content' );

				$this->_setHeader( 'Accept-Ranges', 'bytes' );

				$this->_setHeader( 'Content-range', 'bytes ' . $this->_seek_start . '-' . $this->_seek_end . '/' . $this->_full_size );
			}
			else
			{
				// Turn off resume capability
				$this->_seek_start = 0;

				$this->_seek_end = $this->_full_size - 1;

				$this->_required_download_size = $this->_full_size;

			}
		}

		
		// Commom Download Headers content type, content disposition, content length and Last Modified Goes Here >>>

		$this->_setHeader( 'Content-Type', $this->_mime );

		$this->_setHeader( 'Content-Disposition', 'attachment; filename=' . $this->_file_basename );

		$this->_setHeader( 'Content-Length', $this->_required_download_size );

		$this->_setHeader( 'Last-Modified', date( 'D, d M Y H:i:s \G\M\T', $this->_last_modified_time ) );

		// End Headers Stage

		

		// Work On Download Speed Limit

		if( $this->_speed_limit )
		{
			// how many buffers ticks per second
			$buf_per_second = 10;	//10

			// how long one buffering tick takes by micro second
			$buf_micro_time = 150; // 100

			// Calculate sleep micro time after each tick
			$sleep_micro_time = round( ( 1000000 - ( $buf_per_second * $buf_micro_time ) ) /  $buf_per_second );

			// Calculate required buffer per one tick, make sure it is integer so round the result
			$this->_buffer_size = round( $this->_speed_limit * 1024 / $buf_per_second );

		}


		// Immediatly Before Downloading

		// clean any output buffer
		@ob_end_clean();
		
		// get oignore_user_abort value, then change it to yes
		$old_user_abort_setting = ignore_user_abort();
		ignore_user_abort( true );


		// set script execution time to be unlimited
		@set_time_limit( 0 );
		
		
		// Download According Download Mode
		
		if( $this->_download_mode == self::DOWNLOAD_FILE )
		{
			// Download Data by fopen

			$bytes_to_download = $this->_required_download_size;

			$downloaded = 0;

			// goto the position of the first byte to download
			fseek( $this->_data,  $this->_seek_start );

			while( $bytes_to_download > 0 && !( connection_aborted() || connection_status() == 1 ) )
			{
				// still Downloading
				if( $bytes_to_download > $this->_buffer_size )
				{
					// send buffer size
					echo fread( $this->_data, $this->_buffer_size ); // this also will seek to after last read byte

					$downloaded += $this->_buffer_size;	// updated downloaded

					$bytes_to_download -= $this->_buffer_size;	// update remaining bytes

				}
				else
				{
					// send required size
					// this will happens when we reaches the end of the file normally we wll download remaining bytes
					echo fread( $this->_data, $bytes_to_download );	// this also will seek to last reat	
					
					$downloaded += $bytes_to_download; 	// Add to downloaded
					
					
					$bytes_to_download = 0;	// Here last bytes have been written

				}

				// send to buffer
				flush();

				// Check For Download Limit
				if( $this->_speed_limit )
				
					usleep( $sleep_micro_time );
				
			

			}


			// all bytes have been sent to user
			// Close File
			fclose( $this->_data );


		}
		else
		{
			// Download Data String

			$bytes_to_download = $this->_required_download_size;
			
			$downloaded = 0;

			$offset = $this->_seek_start;

			while( $bytes_to_download > 0 && ( !connection_aborted() ) )
			{

				if( $bytes_to_download > $this->_buffer_size )
				{
					// Download by buffer

					echo mb_strcut( $this->_data, $offset, $this->_buffer_size );

					$bytes_to_download -= $this->_buffer_size;

					$downloaded += $this->_buffer_size;

					$offset += $this->_buffer_size;
				}
				else
				{
					// download last bytes

					echo mb_strcut( $this->_data, $offset, $bytes_to_download );

					$downloaded += $bytes_to_download;

					$offset += $bytes_to_download;

					$bytes_to_download = 0;

				}

				// Send Data to Buffer
				flush();

				// Check Limit
				if( $this->_speed_limit )
				
					usleep( $sleep_micro_time );
				

			}

		}


		// Set Downloaded Bytes
		$this->_downloaded = $downloaded;

		ignore_user_abort( $old_user_abort_setting ); // Restore old user abort settings

		set_time_limit( ini_get( 'max_execution_time' ) ); // Restore Default script max execution Time

		
		// Check if to record downloaded bytes
		if( $this->_record_downloaded )

			$this->_recordDownloaded();

		
		if( $this->_auto_exit ) exit();

		// End download
	}

	
	
	/**
	 * Force download process
	 *
	 * @return  null
	 */
	public function forceDownload()
	{
		// Force mime
		$this->_mime = 'Application/octet-stream';

		$this->download();

		// End forceDownload
	}
	

	
	/************************************************************************************/
	/* 									Setters Methods									*/
	/************************************************************************************/
	


	/**
	 * Change file downloading name
	 *
	 * This method will download file with given name
	 * if the given download name is a basename 'including extension'
	 * then note that while download mode is file download,
	 * file extension and also mime type will not be changed
	 * and if downloade mode is data download,
	 * file extension and also mime type will changed
	 * 
	 * @param string $file_basename name to be downloaded with
	 */
	public function setDownloadName( $file_basename = null )
	{
		if( $file_basename )
		{
			if( preg_match( '/(?P<name>.+?)(\.(?P<ext>.+))?$/', $file_basename, $matches ) )
			{
				// Set filename and extension
				$this->_filename = $matches[ 'name' ];

				$this->_file_extension = ( @$matches['ext'] && $this->_download_mode == self::DOWNLOAD_DATA ) ? $matches['ext'] : $this->_file_extension;

			}

			$this->_file_basename = $this->_filename . '.' . $this->_file_extension;

			$this->_mime = $this->_getMimeOf( $this->_file_extension );

		}

		
		return $this;

		// End setDownloadName
	}

	

	/**
	 * Set download resume capability
	 * 
	 * @param  boolean $resumable resumable or not
	 * @return class              current instance
	 */
	public function resumable( $resumable = true )
	{

		$this->_is_resumable = ( bool ) $resumable;

		return $this;

		// End resumable
	}

	

	/**
	 * Set download speed limit 'KBytes/sec'
	 *
	 * Using download speed limit may be affects on download process, using sleep alots
	 * may make script to exit on some hosts
	 * i tested this method on local hosr server and it works perfectly on any limit
	 * and test on areal host but on speed limit of 100 kBps it works but not every time
	 * and for more slower more failure
	 * so becarefull while using 
	 * 
	 * @param  integer $limit speed in KBps
	 * @return class          current instance
	 */
	public function speedLimit( $limit )
	{

		$limit = intval( $limit );

		$this->_speed_limit = $limit;

		return $this;

		// End speedLimit

	}

	
	
	/**
	 * Set script auto exit after download process completed
	 * 
	 * @param  boolean $val auto exit or not
	 * @return class        current instance
	 */
	public function autoExit( $val = true )
	{

		$this->_auto_exit = ( bool ) $val;
		
		return $this;

		// End autoExit
	}


	
	/**
	 * Download with authenticating
	 *
	 * Set download with authentication process using a built in handler
	 * or using given callback handler
	 * 
	 * @param  mixid  $username_or_callback username or authentication callback handler
	 * @param  string $password             password to authenticate againest in built in authenticatinon handler
	 * @return class                        current instance
	 */
	public function authenticate( $username_or_callback, $password = null )
	{
		
		
		if( is_callable( $username_or_callback ) )
		
			// Via Callback
			$this->_auth_callback = $username_or_callback;

		
		else if( strlen( $username_or_callback ) == 0 || strlen( $password ) == 0 )
		{
			//  Error
			// throw new Exception( 'authenticate() requires one argument to be a callback function or two arguments to be username, password respectively.' );
			header_remove(); // remove pre sent headers

			$this->_setHeader( 'HTTP/1.0 400 Bad Request Authentication Syntax Error' );

			exit();
		}
		else
		{
			// Built in basic authentication
			$this->_auth_username = $username_or_callback;

			$this->_auth_password = $password;
		}
		
		
		$this->_use_authentiaction = true;
		
		return $this;

		// End authenticate
	}

	


	/**
	 * Record download process
	 *
	 * Set if to record download process or not
	 * or set callback handler that perform recording process
	 * 
	 * @param  mixid $use_or_callback record or not or record with callback handler
	 * @return class                  current instance
	 */
	public function recordDownloaded( $use_or_callback = true )
	{

		if( is_callable( $use_or_callback ) )
		{
			// Record Via Callback
			$this->_record_downloaded_callback = $use_or_callback;

			$this->_record_downloaded = true;
		}
		else
		
			$this->_record_downloaded = ( bool ) $use_or_callback;

		
		return $this;
	
		// End recordDownloaded
	}


	
	/********************************************************************************************/
	/*										PRIVATE METHODS										*/
	/********************************************************************************************/



	/**
	 * Initialization
	 *
	 * Set of code performed immediately before calling download method
	 *
	 * @access private
	 * @return null
	 */
	private function _initialize()
	{
		// Initializing code goes here
		
		// allow for sending partial contents to browser, so turn off compression on the server and php config
		
		// Disables apache compression mod_deflate || mod_gzip
		@apache_setenv( 'no-gzip', 1 );

		// disable php cpmpression
		@ini_set( 'zlib.output_compression', 'Off' );
		
	}



	/**
	 * Get file mime type
	 *
	 * This method return mime type of given extension
	 * 
	 * @param  string $extension extension
	 * @return string            mime type
	 */
	private function _getMimeOf( $extension )
	{
		if( !isset( $this->_extensions_mime_arr ) )

			$this->_extensions_mime_arr = @include( 'extensions_mime.php' );

		
		$extension = strtolower( $extension );

		return ( null !== @$this->_extensions_mime_arr[ $extension ] ) ? $this->_extensions_mime_arr[ $extension ] : 'Application/octet-stream';

		// End _getMimeOf
	}

	

	/**
	 * Set header
	 *
	 * @access private
	 * @param  string $key   header key
	 * @param  string $value header value
	 * @return null
	 */
	private function _setHeader( $key, $value = null )
	{

		if( !$value )
			// one value header
			header( $key );
		else
			header( $key . ': ' . $value );

		// End _setHeader
	}

	

	/**
	 * Perform authentication process
	 *
	 * @access private
	 * @return boolean represent authentication success or failed
	 */
	private function _authenticate()
	{
		// Perform Authentication

		$username = @$_SERVER[ 'PHP_AUTH_USER' ];

		$password = @$_SERVER[ 'PHP_AUTH_PW' ];

		
		if( !isset( $username ) )

			return false;

		else if( $this->_auth_callback )
		
			// authenticate via callback
			return call_user_func( $this->_auth_callback, $username, $password );
		
		else
		
			// Built in Authentication
			return ( $username === $this->_auth_username && $password === $this->_auth_password ) ? true : false;
		

		// End _authenticate
	}


	/**
	 * Write downloaded bytes to bandwidth file
	 * 
	 * Record download process to a file
	 * by default it will update 'total_downloaded_bytes.txt' file by adding downloaded bytes
	 * or if a callback handler was supplied it will use it instead for recording process
	 *
	 * @access private
	 * @return null
	 */
	private function _recordDownloaded()
	{
		
		if( $this->_record_downloaded_callback )
		
			// Via Callback
			call_user_func( $this->_record_downloaded_callback, $this->_downloaded, $this->_file_basename );

		
		else
		{
			// Default Recorder
						
			$file = __DIR__ . DIRECTORY_SEPARATOR . 'total_downloaded_bytes.txt';

			$bandwidth = intval( @file_get_contents( $file ) ) + $this->_downloaded;

			file_put_contents( $file, $bandwidth );

		}
		
		// End _recordDownloaded
	}


}

?>