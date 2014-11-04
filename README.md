php-advanced-downloader
=======================

php advanced downloader allow you to control file download process with many options and protecting real file paths


Featutes:
---------
	- Protect Real Files from direct access.
	- Control download resume capability.
	- Control download speed (** requires testing on real host to know minimum safe speed ** ).
	- Control download with authentication, in basic or via a callback handler.
	- Support two diffrent modes for download, File mode and Data mode.
	- Change download file name, 'even change file type only while downloading with Data mode' .
	- Record downloaded bytes 'total bandwidth' in a text file  or add more control with a callback handler.
	- Support alot of file mime types, you can add more.
	- Support Method Chaining.

-- You can run index.php file to test features easily


Note: This Class is supposed to be used in downloading processes, so throwing errors or displaying messages isn't the good idea in here, So this class will depends on header status code and status text
so if something happens and you want to get information, see response status code and status text.




Installation:
-------------

	- just require downloader.class.php in your script


Usage:
------

	- Basic file download
	
		<?php
			
			require ( 'downloader.class.php' );

			$file_path = 'files/my_file.rar';

			( new Downloader( $file_path ) )->download();

		?>



	- Advanced file download

		<?php

			require ( 'downloader.class.php' );

			$file_path = 'files/my_file.rar';

			( new Downloader( $file_path, Downloader::DOWNLOAD_FILE ) ) // Download file in File mode
				->setDownloadName( 'new_name' )							// Change download file name
				->resumable( false ) 									// turn off resumable capability
				->speedLimit( 200 )										// Limit download speed in kbyte/sec
				->authenticate( 'login_name', 'login_password' )		// authenticate before downloading
				->recordDownloaded( true )								// Used bandwith counter
				->autoExit( true )										// Auto exit after download completed
				->download();											// Start Download Process

		?>


Documentation
-------------

- Download Mode
	
	there are two types of downloading mode:
		
		file mode	defined with	DOWNLOADER::DOWNLOAD_FILE

		data mode 	defined with	DOWNLOADER::DOWNLOAD_DATA

	are defined as the second arguments for Downloader constructor, default one is file mode

	
	- file mode

		in file mode the downloaded file must be file and exists and readabe, otherwise an header status code 404 for file not found, or 405 for non readable files

		if we change download name file type and extension will not be affected by the new name, only file name will be changed


	- data mode

		data mode can download a file or string or any thing
		if the downloaded is file it will download it, other wise it will consider that we are downloading a basic txt file, so it will set extension to txt and and give a default filename

		Note:
		*Downloading in data mode will change last modified time to the downloaded time 'Current download time'.*

		to controll downloaded file format, you can use 'setDownloadName()' method then the given extension will be used as file type


	-- EXAMPLES

		<?php
			
			// File Mode
			( new Downloader( 'files/file.mp3' ) )
				->setDownloadName( 'newname.mp4' )
				->download();						// Downloaded file name will be newname.mp3,  mp4 will be ignored


			// Data Mode
			( new Downloader( 'files/file.mp3', Downloader::DOWNLOAD_DATA ) )
				->setDownloadName( 'newname.mp4' )
				->download();						// Downloaded file name will be newname.mp4, and couldn't be opened due to wrong extension, So becareful while downloading in data mode

		?>

---------------------------------------------------------------

- Change Download Name

	to change download name to another name use 'setFileName( $filename )' this method behaviour depends also on download mode

	while downloading in file mode setFileName method will change file name only and keep original file extension

		<?php

			// assuming original file is 'file.mp3'
			
			$downloader->setDownloadName( 'another_name.avi' );
			// .avi extension will be ignored and file basename will be another_name.mp3

		?>

	while downloading in data mode setFileName method will change file name and file extension also which will change file type, so becarefull while using this method on data mode download

		<?php

			// assuming original file is 'file.mp3'

			$downloader->setDownloadName( 'another_name.avi' );
			// .avi extension will be used and file basename will be another_name.avi


			$downloader->setDownloadName( 'another_name' );
			// if file.mp3 is a real existing file, then downloaded file name will be another_name.mp3
			// if file.mp3 isn't areal file 'doesnot exists' then downloaded file basename will be another_name.txt. as txt files is default extension for data download for none existing files

		?>


---------------------------------------------------------------

- Control Resume Capability

	resumable( $bool )
	
	to control resume capability this can be done with 'resumable()' method

	Downloader Default behaviour is to use resume

	resumable uses one argument to be boolean which represent turn on or off resume
	Default argument value is true to turn on resume capability

		<?php

			
			// the coming two lines has the same effect to turn on resumablity feature
			
			$downloader->resumable(); 

			$downloader->resumable( true );


			// Turn off resumability
			$downloader->resumable( false );

		?>

---------------------------------------------------------------

- Control Download Speed

	speedLimit( $speed )

	$speed is integer represent downlowd speed in Kilobytes per second

	Note:
		
	*Speed Limiting depends on micro sleep,
	
	*So, Becarefull while using speed limit, in any value it works very well and almost accurate in local host testing.
	in real host you should test to know the best safe minimum speed because in some hosts speed limit may cause download corrupt as server may close connection due to script sleeping

	Limited speed is defined for one connection, so while using resume and downloading with download programs such as IDM, there eill be multi connections, every single connection will download with defined speed, so overall speed will be many doubles of defined speed.*

		<?php

			// Limit speed to 100 kB/s
			$downloader->speedLimit( 100 );

		?>

--------------------------------------------------------------
