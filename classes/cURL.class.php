<?php

/**
 * @page license Copyright information
 * Copyright (c) 2011, inst, <inst@gmx.com>.
 *
 * All rights reserved.
 *
 * Permission to use, copy, modify, and distribute this software for any purpose
 * with or without fee is hereby granted, provided that the above copyright
 * notice and this permission notice appear in all copies.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT OF THIRD PARTY RIGHTS.
 * IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM,
 * DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR
 * OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE
 * USE OR OTHER DEALINGS IN THE SOFTWARE.
 *
 * Except as contained in this notice, the name of a copyright holder shall not
 * be used in advertising or otherwise to promote the sale, use or other
 * dealings in this Software without prior written authorization of the
 * copyright holder.
 */

/**
 * @section Information
 * @version 0.1
 * @author inst, http://www.inst.tk/
 * @date 2011
 *
 * @brief Object-oriented wrapper on default PHP cURL functions.
 */
class cURL
{
	private $sessions = array();
	private $options = array();
	private $retry = 0;

	/**
	 * @brief Create new object instance.
	 *
	 * While creating an instance of object of this class it will automaticaly
	 * check that needed cURL extension isn't availible on hosting and if so
	 * then will throw a cURLException.
	 *
	 * @param settings (optional)
	 *   An associative array of default values. It's used to
	 *   initialize every session. Every record in this array must be in format
	 *   where the key is one of default CURLOPT_XXX constants and the value is
	 *   proper value for this constant. The exception is a parameters applied
	 *   to whole class instance not every separate session. They can't be
	 *   reinitiated later. For now it's the only one parameter:
	 *     - retry: indicates how many tries should cURL do before returning
	 *       a server error.
	 *
	 * @see set_options()
	 */
	public function __construct( array $settings = array() )
	{
		if( !extension_loaded( 'curl' ) )
			throw new cURLException( 'cURL PHP extension required.' );
		if( $settings )
		{
			if( isset( $settings['retry'] ) )
			{
				$this->retry = (int)$settings['retry'];
				unset( $settings['retry'] );
			}
			$this->options = $settings;
		}
	}

	/**
	 * @brief Initialize internal session.
	 *
	 * Checks URL for protocol schema and creates a new cURL session.
	 */
	private function init_handler( $url, $options = FALSE )
	{
		if( !strstr( $url, '://' ) ) $url = 'http://' . $url;
		if( is_array( $options ) )
			$options += $this->options;
		else
			$options = $this->options;
		$this->sessions[] = curl_init( $url );
		if( is_array( $options ) && count( $options ) )
			$this->set_options( $options, array_pop( array_keys( $this->sessions ) ) );
	}

	/**
	 * @brief Add a new cURL session.
	 *
	 * Creates first cURL session or adds a new one to already exsisted queue.
	 * You can pass array as first parameter to initiate few sessions at once
	 * and every session will be associated with options specified by second
	 * parameter.
	 *
	 * @param url
	 *   A string containing URL to be proceed or an array of strings.
	 *   If you pass to it array and some records in the middle of it will
	 *   be wrong then they and the records followed after they wouldn't
	 *   be initialized.
	 * @param options (optional)
	 *   An associative array containing options of session or sessions
	 *   (in case of array passed first parameter) to be created. It has higher
	 *   priority and so will replace duplicate parameters from constructor.
	 *
	 * @return
	 *   Returns boolean FALSE, if session or sessions initiation failed.
	 *   Otherwise returns TRUE.
	 */
	public function init( $url, $options = FALSE )
	{
		try
		{
			if( is_array( $url ) )
				foreach ( $url as $k => $v ) $this->init_handler( $url[$k], $options );
			else if( is_string( $url ) )
				$this->init_handler( $url, $options );
			else
				throw new DomainException( 'Wrong URL parameter.' );
		} catch( DomainException $e ) {
			return FALSE;
		}
		return TRUE;
	}

	/**
	 * @brief Set an option for a cURL transfer.
	 *
	 * Sets an option on the given cURL session handle.
	 *
	 * @param option
	 *   The CURLOPT_XXX option to set.
	 * @param value
	 *   The value to be set on @p option.
	 * @param key (optional)
	 *   Number of session handle.
	 *   In normal flow there is no need to use it.
	 * 
	 * @return
	 *   Returns TRUE on success or FALSE on failure.
	 *
	 * @see http://www.php.net/curl_setopt
	 */
	public function set_option( $option, $value, $key = 0 )
	{
		return curl_setopt( $this->sessions[(int)$key], (int)$option, $value );
	}

	/**
	 * @brief Set multiple options for a cURL transfer.
	 *
	 * Sets multiple options for a cURL session. This function is useful
	 * for setting a large amount of cURL options without repetitively
	 * calling set_option(). May throw DomainException.
	 *
	 * @param options
	 *   An array specifying which options to set and their values.
	 *   The keys should be valid curl_setopt() constants or their
	 *   integer equivalents.
	 * @param key (optional)
	 *   Number of session handle.
	 *   In normal flow there is no need to use it.
	 *
	 * @return
	 *   Returns TRUE if all options were successfully set. If an option
	 *   could not be successfully set, FALSE is immediately returned,
	 *   ignoring any future options in the options array.
	 *
	 * @see http://www.php.net/curl_setopt_array
	 */
	public function set_options( array $options, $key = 0 )
	{
		$key = (int)$key;
		foreach( $options as $k => $v )
			if( is_string( $k ) )
			{
				$this->close( $key );
				throw new DomainException( 'Options array contains invalid keys.' );
			}
		return curl_setopt_array( $this->sessions[$key], $options );
	}

	/**
	 * @brief Perform a queued cURL transfers.
	 *
	 * Executes the given cURL session.
	 * This function should be called after initializing all cURL sessions and
	 * all the options for the sessions are set.
	 * When key is not specified, if more than one session were initialized
	 * performs multithreaded execution.
	 * Should throw RuntimeException if some errors occured while multithreaded
	 * execution.
	 *
	 * @param key (optional)
	 *   Number of session handle.
	 *   Usualy there is no need to use it.
	 *
	 * @return
	 *   Returns TRUE on success or FALSE on failure. However,
	 *   if the CURLOPT_RETURNTRANSFER option is set, it will return
	 *   the result (or the array of results) on success, FALSE on failure.
	 */
	public function exec( $key = FALSE )
	{
		$cnt = count( $this->sessions );

		$res = FALSE;
		if( $cnt > 1 )
		{
			if( $key === FALSE )
				$res = $this->exec_multi();	
			else
				$res = $this->exec_single( (int)$key );
		} else if( $cnt == 1 )
			$res = $this->exec_single();

		if ( $res ) return $res;
	}

	/**
	 * @brief Perform single session with specified number.
	 *
	 * @param key (optional)
	 *   The number of session to be executed.
	 *
	 * @return
	 *   Returns TRUE on success or FALSE on failure. However,
	 *   if the CURLOPT_RETURNTRANSFER option is set, it will return
	 *   the result on success, FALSE on failure.
	 */
	private function exec_single( $key = 0 )
	{
		if( $this->retry > 0 )
		{
			$retry = $this->retry;
			$code = 0;
			while( $retry >= 0 && ( $code == 0 || $code >= 400 ) )
			{
				$res = curl_exec( $this->sessions[$key] );
				$code = $this->info( $key, CURLINFO_HTTP_CODE );

				$retry--;
			}
		} else
			$res = curl_exec( $this->sessions[$key] );

		return $res;
	}

	/**
	 * @brief Execute all initialized session in multithread mode.
	 *
	 * Can throw RuntimeException.
	 *
	 * @return
	 *   Returns TRUE on success or FALSE on failure. However,
	 *   if the CURLOPT_RETURNTRANSFER option is set, it will return
	 *   the array of results on success, FALSE on failure.
	 */
	private function exec_multi()
	{
		$mh = curl_multi_init();

		foreach ( $this->sessions as $i => $url )
			curl_multi_add_handle( $mh, $this->sessions[$i] );

		$active = NULL;
		do
			$mrc = curl_multi_exec( $mh, $active );
		while( $mrc == CURLM_CALL_MULTI_PERFORM );

		while( $active && $mrc == CURLM_OK )
		{
			if ( curl_multi_select( $mh ) != -1 )
			{
				do
					$mrc = curl_multi_exec( $mh, $active );
				while( $mrc == CURLM_CALL_MULTI_PERFORM );
			}
		}

		if( $mrc != CURLM_OK )
			throw new RuntimeException( "cURL multi read error $mrc." );

		foreach( $this->sessions as $i => $url )
		{
			$code = $this->info( $i, CURLINFO_HTTP_CODE );
			if( $code > 0 && $code < 400 )
				$res[] = curl_multi_getcontent( $this->sessions[$i] );
			else {
				if( ( $retry = $this->retry ) > 0 )
				{
					$this->retry--;
					$eRes = $this->exec_single( $i );

					$res[] = $eRes ? $eRes : FALSE;

					$this->retry = $retry;
				} else
					$res[] = FALSE;
			}
			curl_multi_remove_handle( $mh, $this->sessions[$i] );
		}

		curl_multi_close( $mh );

		return $res;
	}

	/**
	 * @brief Return error number and it's description.
	 *
	 * @param key (optional)
	 *   Number of cURL handle.
	 *
	 * @return
	 *   If @p key is given, returns array with keys @c num and @c err that are
	 *   equivalent to descibed below.
	 *   If session performed succesfuly then @c num will be 0 (zero) and
	 *   @c err will be just an empty string.
	 *   If @p key is not specified, returns multidimensional array in format
	 *    - <tt>result[@b handle_number]['num']</tt>: Internal number of cURL
	 *      error.
	 *    - <tt>result[@b handle_number]['err']</tt>: Clear text error message.
	 *
	 * @see http://curl.haxx.se/libcurl/c/libcurl-errors.html
	 *   For possible error codes.
	 */
	public function errors( $key = FALSE )
	{
		$errors = array();
		if( $key === FALSE )
			foreach( $this->sessions as $session )
			{
				$errors[]['num'] = curl_errno( $session );
				$errors[]['err'] = curl_error( $session );
			}
		else {
			$key = (int)$key;
			$errors['num'] = curl_errno( $this->sessions[$key] );
			$errors['err'] = curl_error( $this->sessions[$key] );
		}

		return $errors;
	}

	private function get_info( $session, $option = FALSE )
	{
		if ( $option ) return curl_getinfo( $session, $option );
		else return curl_getinfo( $session );
	}

	/**
	 * @brief Get information regarding a specific transfer number.
	 *
	 * Gets information about the last transfer in session with specified
	 * @p key.
	 *
	 * @param key (optional)
	 *   A number of a cURL handle.
	 * @param option (optional)
	 *   An option to check for.
	 *
	 * @return
	 *   If both parameters are specified, returns string value of an @p option
	 *   for session with number @p key. If only first parameter assigned then
	 *   returns an associative array with the informational elements.
	 *   If none parameters given, returns multidimensional array
	 *   which as the first dimension contains a number of handle and the
	 *   second is associative array of informational elements for that
	 *   session.
	 *
	 * @see http://www.php.net/curl_getinfo
	 */
	public function info( $key = FALSE, $option = FALSE )
	{
		$info = array();
		if( $key === FALSE )
			foreach( $this->sessions as $session )
				$info[] = $this->get_info( $session, $option );
		else
			$info = $this->get_info( $this->sessions[(int)$key], $option );

		return $info;
	}

	/**
	 * @brief Close session.
	 *
	 * Closes one session if first parameter given or works like clear()
	 * otherwise.
	 *
	 * @param key (optional)
	 *   Number of session handle. If not passed, all sessions will be closed.
	 */
	public function close( $key = FALSE )
	{
		if ( $key === FALSE ) $this->clear();
		else {
			curl_close( $this->sessions[$key] );
			unset( $this->sessions[$key] );
		}
	}

	/**
	 * @brief Close all the sessions invoked with this object.
	 *
	 * Destroys all sessions if there were any by closing them.
	 */
	public function clear()
	{
		foreach ( $this->sessions as $session ) curl_close( $session );
		$this->sessions = array();
	}

	/**
	 * @brief Destroy object.
	 *
	 * Here is the benefits of object-oriented programing begins:
	 * now we have no need to remember what sessions we have initialized,
	 * because they will be closed automaticaly on object destroying.
	 */
	public function __destruct()
	{
		$this->clear();
	}
}

/**
 * @brief cURL specific exception.
 *
 * An exception used to be thrown when further using of object is impossible.
 *
 * Extends runtime exceptions class.
 */
class cURLException extends RuntimeException {}
