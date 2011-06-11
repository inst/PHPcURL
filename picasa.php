<?php

// настройки
$google_email = "email@gmail.com";
$google_password = 'pass';
$google_app_source = 'inst-phpagent-1';	// companyName-applicationName-versionID
$local_file_to_load = '/home/inst/pictures/rustydamask_1280x1024_theal.jpg';

// Инициализация
require_once dirname( __FILE__ ) . DIRECTORY_SEPARATOR . 'classes' . DIRECTORY_SEPARATOR . 'cURL.class.php';
$curl = new cURL( array(
	'retry'					=> 3,
	CURLOPT_USERAGENT		=> 'Mozilla/5.0 PHP Picasa web agent',
	CURLOPT_RETURNTRANSFER	=> TRUE,
	CURLOPT_FOLLOWLOCATION	=> TRUE
) );

// Аутентификация. Подробнее: http://goo.gl/k3kNx (рус)
$curl->init( 'https://www.google.com/accounts/ClientLogin', array(
	CURLOPT_POST		=> TRUE,
	CURLOPT_POSTFIELDS	=> "accountType=GOOGLE&Email=$google_email&Passwd=$google_password&service=lh2&source=$google_app_source",
	CURLOPT_SSL_VERIFYPEER => FALSE,
	CURLOPT_SSL_VERIFYHOST => FALSE
) );
$auth = NULL;
foreach( explode( "\n", $curl->exec() ) as $v )
	if( ! strcasecmp( substr( $v, 0, 5 ), 'Auth=' ) )
		$auth = substr( $v, 5 );
$info = $curl->info();
if( $info[0]['http_code'] != 200 )
	exit( 'Ошибка аутентификации' );
if( $auth == NULL )
	exit( 'Внутренняя ошибка' );
$curl->clear();

// Получения двоичных данных из локального файла
$handle = fopen( $local_file_to_load, 'rb' );
$content = fread( $handle, filesize( $local_file_to_load ) );
fclose( $handle );
// Непосредственно загрузка изображения. Подробнее: http://goo.gl/v7BRE
$curl->init( "https://picasaweb.google.com/data/feed/api/user/default/albumid/default", array(
	CURLOPT_POST		=> TRUE,
	CURLOPT_POSTFIELDS	=> $content,
	CURLOPT_HTTPHEADER	=> array( "Authorization: GoogleLogin auth=$auth", 'Content-type: image/jpeg' ),
	CURLOPT_SSL_VERIFYPEER => FALSE,
	CURLOPT_SSL_VERIFYHOST => FALSE
) );

print_r( $curl->info() );	// Некоторая служебная информация, включая код результата,
							// а также скорость загрузки и пр.
print_r( $curl->exec() );	// Результат загрузки. Здесь, среди прочего, будет и
							// URL новозагруженной фотки
