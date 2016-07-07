## php-advanced-downloader

>php advanced downloader allow you to control file download process with many options and protecting real file paths


### License

>This Package is distributed under [GNU GPL V3 License](http://choosealicense.com/licenses/gpl-3.0/)


### Copyright
>@author	Ahmed Saad <a7mad.sa3d.2014@gmail.com>

### Version
> 1.2.1 
>> Updated at : *`7 Jul 2016`*

>> ### Changelog :

>> Fix Byte range end ( By	chardinge1 )


----

### Features:

1. Protect Real Files from direct access.
2. Control download resume capability.
3. Control download speed _(** requires testing on real host to know minimum safe speed ** )_.
4. Control download with authentication, ( _`WWW Basic Authentication`_ or  _`callback handler`_ ).
5. Support two diffrent modes for download, _`File mode`_ and _`Data mode`_.
6. Change download file name
	* file type _extension_ might also changable only for Downloading in `Downloader::DOWNLOAD_DATA` mode .
7. Record downloaded bytes _total bandwidth_ in a text file  or add more control with a _callback handler_.
8. Support alot of file mime types.
9. Support _Method Chaining_.
10. __`Designed to use namespace and autoload`__


>Note: This Class is supposed to be used in downloading processes, so throwing errors or displaying messages isn't the good idea here, So this class will depends on header status code and status text
so if something happens and you want to get information, see response status code and status text.



--
### Installation:

- just require `autoload.php` file in your script :
	>this autoloader will automatically load any defined class based on its namespace .
		
		// inside index.php
		require_once( './core/autoload.php' );

--
### Usage:
- Basic file download :
	
		<?php
			
			require_once( './core/autoload.php' );
			
			use Core\Downloader;

			$file_path = 'files/my_file.rar';

			( new Downloader( $file_path ) )->download();

		?>



- Advanced file download

		<?php

			require_once( './core/autoload.php' );
			
			use Core\Downloader;

			( new Downloader( $file_path, Downloader::DOWNLOAD_FILE ) ) // Download file in File mode
				->setDownloadName( 'new_name' )							// Change download file name
				->resumable( false ) 									// turn off resumable capability
				->speedLimit( 200 )										// Limit download speed in kbyte/sec
				->authenticate( 'login_name', 'login_password' )		// authenticate before downloading
				->recordDownloaded( true )								// Used bandwith counter
				->autoExit( true )										// Auto exit after download completed
				->download();											// Start Download Process

		?>

--
### Documentation
___

* `Download Mode`
	
	there are two types of downloading mode:
		
	> Downloader::DOWNLOAD_FILE
	
	> Downloader::DATA

	are defined as the second arguments for Downloader constructor, default one is file mode

	
	- file mode : `Downloader::DOWNLOAD_FILE`

		> in file mode the downloaded file must be file and exists and readabe, otherwise an header status code 404 for file not found, or 405 for non readable files

		>if we change download name file type and extension will not be affected by the new name, only file name will be changed


	- data mode : `Downloader::DOWNLOAD_DATA`

		> data mode can download a file or string or any thing
		if the downloaded is file it will download it, other wise it will consider that we are downloading a basic txt file, so it will set extension to txt and and give a default filename

		>> __Note :__
		
		>> * Downloading in data mode will change last modified time to the downloaded time __*Current download time*__.

		>> * to controll downloaded file format, you can use _`setDownloadName()`_ method then the given extension will be used as file type

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

--

* `Downloader::setDownloadName( string $filename )`

	change downloaded file name
	
	> this method behaviour depends also on download mode :
	
	>> if new extenstion supplied :
	
	>> in file mode ===> only file name will be changed and extension will be ignored .
	
	>> in data mode ===> file name and type will be change .

		<?php

			// assuming original file is 'file.mp3'
			
			// Download in File Mode
			$downloader->setDownloadName( 'another_name.avi' ); //  another_name.mp3


			// Download in Data Mode
			$downloader->setDownloadName( 'another_name.avi' ); // another_name.avi


			$downloader->setDownloadName( 'another_name' ); // another_name.mp3 ( if file.mp3 exists )
			 												// another_name.txt ( if file.mp3 doesn't exists )

		?>

--

* `Downloader::resumable( bool $bool )`

	turn on or off resume capability
	
	> Downloader Default behaviour is to use resume

		<?php

			
			
			$downloader->resumable();  		// turn on

			$downloader->resumable( true ); // turn on

			$downloader->resumable( false ); // Turn off

		?>

--

* `Downloader::speedLimit( int $speed )`

	$speed is integer represent downlowd speed in Kilobytes per second
	
	> __Note :__
	
	> Speed Limiting depends on micro sleep,
	So, Becarefull while using speed limit, in any value it works very well and almost accurate in local host testing.
	in real host you should test to know the best safe minimum speed because in some hosts speed limit may cause download corrupt as server may close connection due to script sleeping

	> Limited speed is defined for one connection, so while using resume and downloading with download programs such as IDM, there eill be multi connections, every single connection will download with defined speed, so overall speed will be many doubles of defined speed.

			// Limit speed to 100 kB/s
			$downloader->speedLimit( 100 );

