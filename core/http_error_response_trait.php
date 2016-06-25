<?php

namespace Core;


/**
 *  Trait to Supply HTTP Error Response
 */
trait HttpErrorResponseTrait
{
	
	protected $http_version = 'HTTP/1.1';

	protected $error_title = [
		401 => 'Unauthorized',
		403 => 'Forbidden',
		404 => 'Not Found',
		405 => 'Method Not Allowed',
		416 => 'Requested range not satisfiable',
	];

	protected $html = 
		'<html>
			<head>
				<title>{title}</title>
			</head>
			<body>
			
				<h4>{status_code} : {message}!.</h4>
			</body>
		</html>';
	
	
	/**
	 * @param  int 		$int 	HTTP Error Code
	 * @param  string  	$message Custom Error Status Message
	 * @return Void
	 */
	protected function httpError( $status_code, $message = null )
	{
		$message = !empty( $message ) ? $message : $this->error_title[ $status_code ];

		header( $this->http_version . ' ' . $status_code . ' ' . $message );
			
		// Display Error Message For User
		echo str_replace( [ '{title}', '{status_code}', '{message}' ],
			[ $message, $status_code, $message ],
			$this->html );
		
		// Stop PHP Execution
		exit();
	}

	// protected function ( )

}