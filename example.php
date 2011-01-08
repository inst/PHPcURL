<?php

/**
 * @mainpage
 *
 * @section Description
 * PHPcURL is an object-oriented wrapper for a PHP cURL extension.
 *
 * It is released under the terms of open-source MIT license so that you can
 * use it even in proprietary projects. For more information look at
 * @ref license.
 *
 * @section example Brief examples
 * @subsection Including
 * Using of class is simple enough, to use it in your project simply include
 * @c cURL.class.php:
 * @code
 * include_once dirname( __FILE__ ) . DIRECTORY_SEPARATOR . 'classes'
 * 		. DIRECTORY_SEPARATOR . 'cURL.class.php';
 * @endcode
 * Here supposed that you are saving classes in sub-directory @c classes.
 * @subsection Using
 * This include is the only thing you should do to use all features of the
 * class in your project.
 *
 * After that you can create a new instance of class and
 * perform queries you need:
 * @code
 * try
 * {
 * 	$curl = new cURL;
 * 	if ( $curl->init( 'http://www.example.org/' ) ) $curl->exec();
 * } catch( cURLException $e ) {
 * 	// cURL is not installed
 * 	print_r( $e->getMessage() );
 * }
 * @endcode
 * The example above will output to browser contents of the www.example.org.
 *
 * As you can see we didn't close created session as this class does it for us
 * automaticaly.
 * @subsection complex_example Some more complex example
 * Sometimes you might have to pass some parameters to cURL to control your
 * script execution. In such cases you have three ways to do it:
 * - Class constructor: to specify default transfer options. It has one option
 *   that can not be overwritten later.
 * - <tt>init( $url, array( option => value ) );</tt> second parameter to
 *   rewrite defaults assigned by constructor.
 * - Or you can change some options of already existed transfers with the
 *   <tt>set_option( option, $value [, $n] ) );</tt> and
 *   <tt>set_options( array( option => value ) [, $n] );</tt> functions.
 *
 * @code
 * try
 * {
 * 	$curl = new cURL( array(
 * 		'retry'					=> 3,
 * 		CURLOPT_USERAGENT		=> 'Mozilla/5.0 PHP cURL agent',
 * 		CURLOPT_RETURNTRANSFER	=> TRUE
 * 	) );
 * 	$curl->init( 'http://www.inst.tk/' );
 * 	$curl->init( 'http://twitter.com/', array( 'somewrongkey' => 'somewrongvalue' ) );
 * 	$curl->init( 'http://www.google.com/', array( CURLOPT_FOLLOWLOCATION => TRUE ) );
 * 	$curl->exec();
 * 	var_dump( $curl->info() );
 * 	$curl->clear();
 * 	$curl->init( array( 'http://www.google.com/', 'www.yahoo.com', 'http://www.flickr.com/' ) );
 * 	$curl->exec();
 * 	var_dump( $curl->info() );
 * } catch( cURLException $e ) {
 * 	print_r( $e->getMessage() );
 * } catch( RuntimeException $e ) {
 * 	print_r( $e->getMessage() );
 * } catch( Exception $e ) {
 * 	print_r( $e->getMessage() );
 * }
 * @endcode
 * Note that when we initiate more than one transfer class will automaticaly
 * perform they as multithreaded.
 *
 * Let's explain what we did in this example. First, we create a new object of
 * class cURL, but we did it in some tricky way by specifying default options
 * for every transfer (Useragent). Second, we init'ed three different sessions
 * but second one will not be performed 'cause it has wrong options. Third,
 * after performing requests ( @c exec() ) we gathered all info about sessions.
 * By clearing object we will forget about done sessions but will not lose
 * default options.
 *
 * As you can see after clearing we initiate three others transfers but do it
 * by using other feature: array of URL's. This time we can specify common
 * settings for these sessions by second parameter or do it later by using
 * @c set_option() or @c set_options() functions on every of them.
 *
 * Other feature you may have noted already we have no need to include schemas
 * in URL's ("http://" part).
 *
 * Last thing that need explanation is exceptions. We should catch only three
 * types of exceptions.
 * - cURLException: for now it can be thrown only if cURL PHP extension
 *   isn't installed on hosting.
 * - RuntimeException: throws only if error on multithreaded code occured.
 *   cURLException is a kind of RuntimeException.
 * - DomainException: only @c set_options() can throw it.
 *
 * For more detailed description of class see it docs: @ref cURL. Functions
 * with brief descriptions are listed in such order in that they are usualy
 * accessed.
 */
