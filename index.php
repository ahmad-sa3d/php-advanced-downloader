<?php

	require( './core/autoload.php' );

	use Core\Downloader as Downloader;
	
	
	ini_set( 'display_errors', 1 );

	error_reporting( E_ALL );
	
	if( isset( $_POST[ 'download' ] ) ):
	

		$file = is_file( './files/' . @$_POST[ 'file' ] ) ?
			'./files/' . @$_POST[ 'file' ]  // Download a file
			:
			@$_POST[ 'file' ];  // Download string, not prepend files folder path

		
		$save_as = !@$_POST['save_name'] ?: $_POST['save_name'];
		
		$resumable = ( bool ) @$_POST['resumable'];
		
		$speed = intval( @$_POST[ 'speed' ] );

		$mode = @$_POST['mode'] == 'data' ? Downloader::DOWNLOAD_DATA : Downloader::DOWNLOAD_FILE;
		
		$auth_username = @$_POST[ 'auth_username' ];
		$auth_password = @$_POST[ 'auth_password' ];

		$record = strlen( @$_POST[ 'record' ] ) > 1 ? 'recordBytesCallback' : ( int ) @$_POST[ 'record' ];
		
		$auto_exit = ( bool ) @$_POST['auto_exit'];

		// var_dump( $record );
		// exit();

		// Authentication Callback
		function authCallback( $php_user, $php_password )
		{
			return ( $php_user === 'login_user' && $php_password === 'login_password' ) ? true : false;
		}


		// Download Recorder Callback
		function recordBytesCallback( $bytes, $file_name )
		{
			$path = './bytes.txt';

			$file = fopen( $path, 'a+t' );

			fwrite( $file, $file_name . '   |   ' . $bytes . " Bytes \n\r" );

			fclose( $file );
			
			// file_put_contents( '', $file_name . '    |   ' . $bytes );
		}

		
		// Start Download
		$downloader = ( new Downloader( $file, $mode ) )
			->resumable( $resumable )
			->speedLimit( $speed )
			->setDownloadName( $save_as )
			->autoExit( true )
			// ->authenticate( 'authCallback' )
			->recordDownloaded( $record );

		
		if( $auth_username && $auth_password )

			$downloader->authenticate( $auth_username, $auth_password );

		
		// Start Download
		$downloader->download();


	
	else: ?>

	<!DOCTYPE html>
	<html>
		<head>
			<title>Test Downloader Class @Ahmed Saad</title>
			<meta charset="utf-8" />
			<meta name="author" content="Ahmed Saad" />
			
			<style>

				body{
					/*text-align: center;*/
					color: #666;
				}

				form{
					width: 400px;
					margin: 30px auto;
					
				}

				form fieldset{
					border: 1px solid #999;
					border-radius: 0 3px 3px 3px;
					padding: 25px 14px;
					margin-bottom: 20px;
				}

				form legend{
					border: 1px solid #999;
					border-bottom: none;
					border-radius: 3px 3px 0 0;
					position: relative;
					left: 2px;
					bottom: -1px;
					padding: 5px 10px;
					
				}

				form fieldset, form legend{
					background: #fcfcfc;
				}

				input[type="text"]{
					width: 250px;
					border-radius: 4px;
					border: 1px solid #999;
					outline: none;
					
				}

				input[type="text"]:focus{
					box-shadow: 1px 1px 4px #6ae inset, -1px -1px 1px #6ae inset;
					border-color: #6af;
				}

				input[type="submit"]
				{
					/*padding: 4px;*/
					border-radius: 4px;
					border: 1px solid rgb( 80, 150, 240 );
					/*background: rgb( 100,170,230 );*/
					background: -webkit-linear-gradient( rgb( 100,170,240 ) 0, rgb( 54,150,230 ) 100% );
					color: rgb( 250, 250, 250 );
					cursor: pointer;
				}

				input[type="submit"]:hover{
					border-color: rgb( 10, 150, 240 );
				}

				input{
					line-height: 15px;
					padding: 5px;
				}

				.files{
					border-top: 1px dashed #999;
					margin-top: 20px;
				}

				.files li{
					color: rgb( 230, 100, 130 );
				}

				form label:not([for*="mode"]):not([for*="record"])
				{
					display:inline-block;
					width: 100px;
				}

				form label[for*="mode"] + input, form label[for*="record"] + input
				{
					margin-right: 30px;
				}


				input[name="download"]{
					display: block;
					margin: 0 auto;
				}

				fieldset h4
				{
					margin: 0 auto;
					text-align: center;
					color: rgb( 230, 100, 130 );
				}

				#auth_username, #auth_password{
					width: 117px;
				}


			</style>
		</head>
		<body>

			<div class="container">

				<?php

				if( $file = @$_GET['file'] ):
				?>

				<!-- Prepare Sellected File -->
				<form method="POST">
					
					<legend>Sellect Download Options</legend>
					
					<fieldset>
						
						<h4><?= $file ?></h4>

						<!-- Hidden File Name -->
						<input type="hidden" name="file" value="<?= $file ?>" />

						<!-- Save Name -->
						<p>
							<label for="save_name">Save As</label>
							<input type="text" name="save_name" id="save_name" value="<?= $file ?>">
						</p>

						<!-- Use Resume -->
						<p>
							<label for="resumable">Use Resume</label>
							<input type="checkbox" name="resumable" id="resumable" checked>
						</p>

						<!-- Speed Limit -->
						<p>
							<label for="speed">Speed</label>
							<input type="text" name="speed" id="speed" placeholder="Unlimited 'use integers kBps'" value="">
						</p>

						<!-- Download Mode -->
						<p>
							<label>Mode</label>
							<label for="file_mode">File</label>
							<input type="radio" name="mode" id="file_mode" value="file" checked>
							
							<label for="data_mode">Data</label>
							<input type="radio" name="mode" id="data_mode" value="data">

						</p>

						<!-- Authenticate -->
						<p>
							<label>Authentication</label>
							<input type="text" name="auth_username" id="auth_username" placeholder="username">
							<input type="text" name="auth_password" id="auth_password" placeholder="password">
						</p>

						<!-- Record Downloaded Bytes -->
						<p>
							<label>Record Bytes</label>
							
							<label for="record_0">No</label>
							<input type="radio" name="record" id="record_0" value="0" checked>
							
							<label for="record_1">Yes</label>
							<input type="radio" name="record" id="record_1" value="1">

							<label for="record_callback">Callback</label>
							<input type="radio" name="record" id="record_callback" value="callback">
						</p>

						<!-- Auto Exit -->
						<p>
							<label for="auto_exit">Auto Exit</label>
							<input type="checkbox" name="auto_exit" id="auto_exit">
						</p>

						<!-- Download -->
						<input type="submit" name="download" value="Download" />

					</fieldset>

				</form>


				<?
				else:// Display Form To Enter File Name
				// get $files
				$files = scandir( './files/' );
				
				foreach( $files as $key => &$file )
				{
					$file_type = @pathinfo( $file )['extension'];

					$ignore = []; // [ 'php' => 1, 'html' => 1 ];

					if( !is_file( './files/' . $file ) || strpos( $file, '.DS' ) === 0 || @$ignore[ $file_type ] )
					{
						unset( $files[ $key ] );
					}
				}

				?>

				<form method="GET">
					
					<legend>Type File Name To Download</legend>
					<fieldset>

						<input type="text" name="file" placeholder="filename..." validate />

						<input type="submit" value="Prepare" />

						<div class="files">
							<h4>existing files</h4>
							
							<?php
								if( $files )

									echo '<ul><li>' . implode( '</li><li>', $files ) . '</li></ul>';

								else

									echo '<h5>No Files Found</h5>';
							?>

						</div>

					</fieldset>


				</form>

			<?php endif; ?>
				
			</div>

		</body>
	</html>

	<? endif;
?>