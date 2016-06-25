<?php


	require( './core/downloader.php' );


		$file = is_file( './files/' . @$_GET[ 'file' ] ) ?
			'./files/' . @$_GET[ 'file' ]	// Download a file
			:
			@$_GET[ 'file' ];	// Download string

		$speed = intval( @$_GET[ 'speed' ] );

		$resumable = ( bool ) @$_GET['resumable'];

		$mode = @$_GET['mode'] == 'data' ? Downloader::DOWNLOAD_DATA : Downloader::DOWNLOAD_FILE;

		$auto_exit = ( bool ) @$_GET['auto_exit'];

		$save_as = !@$_GET['save_name'] ?: $_GET['save_name'];

	

	( new Downloader( $file, $mode ) )
		->resumable( $resumable )
		->speedLimit( $speed )
		->setDownloadName( $save_as )
		// ->authenticate( 'ahmed', 'saad' )
		->autoExit(  )
		->download();
		

?>