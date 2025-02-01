<?php
$downloader_data = wfu_read_downloader_data();
$GLOBALS["wfu_downloader_data"] = $downloader_data;
if ( !defined("ABSWPFILEUPLOAD_DIR") ) DEFINE("ABSWPFILEUPLOAD_DIR", dirname(__FILE__).'/');
if ( !defined("WFU_AUTOLOADER_PHP50600") ) DEFINE("WFU_AUTOLOADER_PHP50600", 'vendor/modules/php5.6/autoload.php');
include_once( ABSWPFILEUPLOAD_DIR.'lib/wfu_functions.php' );
include_once( ABSWPFILEUPLOAD_DIR.'lib/wfu_security.php' );
wfu_download_file();

/**
 * Read Downloader Data.
 *
 * This function reads the data that need to be passed to the downloader from a
 * temporary file. Previously these data were read using user state (either
 * session or cookies), however it had security issues. Reading from a file has
 * many benefits: a) the data cannot be altered by an external vector, b) the
 * temporary file location can be calculated only with core PHP function without
 * the need to initialize Wordpress.
 *
 * @since 4.24.14
 */
function wfu_read_downloader_data() {
	// read the temp file name from URL param
	$source = (isset($_POST['source']) ? $_POST['source'] : (isset($_GET['source']) ? $_GET['source'] : ''));
	if ( $source === '' ) die();
	// sanitize source file to avoid directory traversals through it
	$source = preg_replace("/[^A-Za-z0-9]/", "", $source);
	$filepath = sys_get_temp_dir();
	if ( substr($filepath, -1) != '/' ) $filepath .= '/';
	$filepath .= $source;
	if ( !file_exists($filepath) ) die();
	// read and decode the data from the file
	$dataenc = @file_get_contents($filepath);
	// remove the file immediately so that it cannot be reused
	@unlink($filepath);
	if ( $dataenc === false ) die();
	$data = json_decode($dataenc, true);
	if ( $data === null ) die();
	// validate type
	$type = ( array_key_exists('type', $data) ? $data['type'] : '' );
	if ( !in_array( $type, array( 'normal', 'exportdata', 'debuglog' ) ) ) die();
	// validate ticket
	$ticket = ( array_key_exists('ticket', $data) ? wfu_sanitize_code_downloader($data['ticket']) : '' );
	if ( empty($ticket) || $ticket !== $data['ticket'] ) die();
	// validate file
	$filepath = ( array_key_exists('filepath', $data) ? wfu_sanitize_url_downloader($data['filepath']) : '' );
	if ( empty($filepath) || $filepath !== $data['filepath'] ) die();
	// validate user state handler
	$handler = ( array_key_exists('handler', $data) ? $data['handler'] : null );
	if ( $handler === null || !in_array( $handler, array( 'session', 'dboption', '' ) ) ) die();
	// validate expire
	$expire = ( array_key_exists('expire', $data) ? intval($data['expire']) : 0 );
	if ( $expire <= 0 ) die();
	// validate wfu_ABSPATH
	$wfu_ABSPATH = ( array_key_exists('wfu_ABSPATH', $data) ? wfu_sanitize_url_downloader($data['wfu_ABSPATH']) : '' );
	if ( empty($wfu_ABSPATH) || $wfu_ABSPATH !== $data['wfu_ABSPATH'] ) die();
	// validate error messages
	$notexist = ( array_key_exists('wfu_browser_downloadfile_notexist', $data) ? strip_tags($data['wfu_browser_downloadfile_notexist']) : '' );
	$failed = ( array_key_exists('wfu_browser_downloadfile_failed', $data) ? strip_tags($data['wfu_browser_downloadfile_failed']) : '' );
	if ( empty($notexist) || empty($failed) || $notexist !== $data['wfu_browser_downloadfile_notexist'] || $failed !== $data['wfu_browser_downloadfile_failed'] ) die();
	
	return $data;
}

/**
 * Download a File.
 *
 * This function causes a file to be downloaded.
 *
 * @global array $wfu_downloader_data The downloader data.
 */
function wfu_download_file() {
	global $wfu_downloader_data;

	extract($wfu_downloader_data);

	//if download ticket does not exist or is expired die
	if ( time() > $expire ) {
		wfu_update_download_status('failed');
		die();
	}
	
	//if file_code starts with exportdata, then this is a request for export of
	//uploaded file data, so disposition_name wont be the filename of the file
	//but wfu_export.csv; also set flag to delete file after download operation
	if ( $type == "exportdata" ) {
		$disposition_name = "wfu_export.csv";
		$delete_file = true;
	}
	//if file_code starts with debuglog, then this is a request for download of
	//debug_log.txt
	elseif ( $type == "debuglog" ) {
		$disposition_name = wfu_basename($filepath);
		$delete_file = false;
	}
	else {
		$filepath = wfu_flatten_path($filepath);
		if ( substr($filepath, 0, 1) == "/" ) $filepath = substr($filepath, 1);
		$filepath = ( substr($filepath, 0, 6) == 'ftp://' || substr($filepath, 0, 7) == 'ftps://' || substr($filepath, 0, 7) == 'sftp://' ? $filepath : $wfu_ABSPATH.$filepath );
		$disposition_name = wfu_basename($filepath);
		$delete_file = false;
	}
	//check that file exists
	if ( !wfu_file_exists_for_downloader($filepath) ) {
		wfu_update_download_status('failed');
		die('<script language="javascript">alert("'.$wfu_browser_downloadfile_notexist.'");</script>');
	}

	$open_session = false;
	@set_time_limit(0); // disable the time limit for this script
	$fsize = wfu_filesize_for_downloader($filepath);
	if ( $fd = wfu_fopen_for_downloader($filepath, "rb") ) {
		$open_session = ( ( $handler == "session" || $handler == "" ) && ( function_exists("session_status") ? ( PHP_SESSION_ACTIVE !== session_status() ) : ( empty(session_id()) ) ) );
		if ( $open_session ) session_start();
		header('Content-Type: application/octet-stream');
		header("Content-Disposition: attachment; filename=\"".$disposition_name."\"");
		header('Content-Transfer-Encoding: binary');
		header('Connection: Keep-Alive');
		header('Expires: 0');
		header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
		header('Pragma: public');
		header("Content-length: $fsize");
		$failed = false;
		while( !feof($fd) ) {
			$buffer = @fread($fd, 1024*8);
			echo $buffer;
			ob_flush();
			flush();
			if ( connection_status() != 0 ) {
				$failed = true;
				break;
			}
		}
		fclose ($fd);
	}
	else $failed = true;
	
	if ( $delete_file ) wfu_unlink_for_downloader($filepath);
	
	if ( !$failed ) {
		wfu_update_download_status('downloaded');
		if ( $open_session ) session_write_close();
		die();
	}
	else {
		wfu_update_download_status('failed');
		if ( $open_session ) session_write_close();
		die('<script type="text/javascript">alert("'.$wfu_browser_downloadfile_failed.'");</script>');
	}
}

/**
 * Update Download Status.
 *
 * Stores in user state the new download status.
 *
 * @global array $wfu_downloader_data The downloader data.
 *
 * @param string $new_status The new download status.
 */
function wfu_update_download_status($new_status) {
	global $wfu_downloader_data;
	require_once $wfu_downloader_data['wfu_ABSPATH'].'wp-load.php';
	WFU_USVAR_store('wfu_download_status_'.$wfu_downloader_data['ticket'], $new_status);
}

/**
 * Sanitize a Code.
 *
 * This function sanitizes a code. A code must only contain latin letters and
 * numbers.
 *
 * @since 4.24.14
 *
 * @param string $code The code to sanitize.
 *
 * @return string The sanitized code.
 */
function wfu_sanitize_code_downloader($code) {
	return preg_replace("/[^A-Za-z0-9]/", "", $code);
}

/**
 * Sanitize a URL.
 *
 * This function sanitizes a URL.
 *
 * @since 4.24.14
 *
 * @param string $url The URL to sanitize.
 *
 * @return string The sanitized URL.
 */
function wfu_sanitize_url_downloader($url) {
	return filter_var(strip_tags($url), FILTER_SANITIZE_URL);
}

/**
 * Check User State Variable For Existence.
 *
 * Checks whether a user state variable exists.
 *
 * @global string $wfu_user_state_handler The user state handler.
 *
 * @param mixed $var The user state variable to check.
 * @return bool True if it exists, false otherwise.
 */
function WFU_USVAR_exists_downloader($var) {
	global $wfu_user_state_handler;
	if ( $wfu_user_state_handler == "dboption" && WFU_VAR("WFU_US_DBOPTION_BASE") == "cookies" ) return isset($_COOKIE[$var]);
	else return WFU_USVAR_exists_session($var);
}

/**
 * Get User State Variable.
 *
 * Returns the value of a user state variable. The variable needs to exist.
 *
 * @global string $wfu_user_state_handler The user state handler.
 *
 * @param mixed $var The user state variable to read.
 * @return mixed The variable value.
 */
function WFU_USVAR_downloader($var) {
	global $wfu_user_state_handler;
	if ( $wfu_user_state_handler == "dboption" && WFU_VAR("WFU_US_DBOPTION_BASE") == "cookies" ) return $_COOKIE[$var];
	else return WFU_USVAR_session($var);
}

/**
 * Unset User State Variable.
 *
 * Unsets a user state variable.
 *
 * @global string $wfu_user_state_handler The user state handler.
 *
 * @param mixed $var The user state variable to unset.
 */
function WFU_USVAR_unset_downloader($var) {
	global $wfu_user_state_handler;
	if ( $wfu_user_state_handler == "session" || $wfu_user_state_handler == "" ) WFU_USVAR_unset_session($var);
}

/**
 * Check for File Existence.
 *
 * Checks whether a file exists.
 *
 * @param string $filepath The full path of the file to check.
 * @return bool True if the file exists, false otherwise.
 */
function wfu_file_exists_for_downloader($filepath) {
	if ( substr($filepath, 0, 7) != "sftp://" ) return file_exists($filepath);
	$ret = false;
	$ftpinfo = wfu_decode_ftpurl($filepath);
	if ( $ftpinfo["error"] ) return $ret;
	$data = $ftpinfo["data"];
	{
		$conn = @ssh2_connect($data["ftpdomain"], $data["port"]);
		if ( $conn && @ssh2_auth_password($conn, $data["username"], $data["password"]) ) {
			$sftp = @ssh2_sftp($conn);
			$ret = ( $sftp && @file_exists("ssh2.sftp://".intval($sftp).$data["filepath"]) );
		}
	}
	
	return $ret;
}

/**
 * Get File Size.
 *
 * Return the size of a file.
 *
 * @param string $filepath The full path of the file.
 * @return int|bool The size of the file on success, false on failure.
 */
function wfu_filesize_for_downloader($filepath) {
	if ( substr($filepath, 0, 7) != "sftp://" ) return filesize($filepath);
	$ret = false;
	$ftpinfo = wfu_decode_ftpurl($filepath);
	if ( $ftpinfo["error"] ) return $ret;
	$data = $ftpinfo["data"];
	{
		$conn = @ssh2_connect($data["ftpdomain"], $data["port"]);
		if ( $conn && @ssh2_auth_password($conn, $data["username"], $data["password"]) ) {
			$sftp = @ssh2_sftp($conn);
			if ( $sftp ) $ret = @filesize("ssh2.sftp://".intval($sftp).$data["filepath"]);
		}
	}
	
	return $ret;
}

/**
 * Get File Handler.
 *
 * Returns a file handler for reading the contents of the file. If the file is
 * in an FTP location, then it is first copied in a memory stream and the
 * handler of the memory stream is returned.
 *
 * @param string $filepath The full path of the file.
 * @param string $mode The reading mode.
 * @return resource|bool The file handler on success, false on failure.
 */
function wfu_fopen_for_downloader($filepath, $mode) {
	if ( substr($filepath, 0, 7) != "sftp://" ) return @fopen($filepath, $mode);
	$ret = false;
	$ftpinfo = wfu_decode_ftpurl($filepath);
	if ( $ftpinfo["error"] ) return $ret;
	$data = $ftpinfo["data"];
	{
		$conn = @ssh2_connect($data["ftpdomain"], $data["port"]);
		if ( $conn && @ssh2_auth_password($conn, $data["username"], $data["password"]) ) {
			$sftp = @ssh2_sftp($conn);
			if ( $sftp ) {
				//$ret = @fopen("ssh2.sftp://".intval($sftp).$data["filepath"], $mode);
				$contents = @file_get_contents("ssh2.sftp://".intval($sftp).$data["filepath"]);
				$stream = fopen('php://memory', 'r+');
				fwrite($stream, $contents);
				rewind($stream);
				$ret = $stream;
			}
		}
	}
	
	return $ret;
}

/**
 * Delete a File.
 *
 * Deletes a file. It also supports FTP locations.
 *
 * @param string $filepath The full file path of the file to delete.
 * @return bool True on success, false otherwise.
 */
function wfu_unlink_for_downloader($filepath) {
	if ( substr($filepath, 0, 7) != "sftp://" ) return @unlink($filepath);
	$ret = false;
	$ftpinfo = wfu_decode_ftpurl($filepath);
	if ( $ftpinfo["error"] ) return $ret;
	$data = $ftpinfo["data"];
	{
		$conn = @ssh2_connect($data["ftpdomain"], $data["port"]);
		if ( $conn && @ssh2_auth_password($conn, $data["username"], $data["password"]) ) {
			$sftp = @ssh2_sftp($conn);
			if ( $sftp ) $ret = @unlink("ssh2.sftp://".intval($sftp).$data["filepath"]);
		}
	}
	
	return $ret;
}

/**
 * Validate Storage Filepath.
 *
 * It validates the filepath that was retrieved from user state storage. For the
 * moment it works only when the downloaded file is an export of the plugin's
 * data. It validates that the retrieved filepath is the PHP temp path.
 *
 * @since 4.24.12
 *
 * @param string $code The download code.
 * @param string $filepath The retrieved full file path.
 * @return bool True if validation has passed, false otherwise.
 */
function wfu_validate_storage_filepath($code, $filepath) {
	$result = false;
	if ( $code === "exportdata" ) {
		$onlypath = wfu_basedir($filepath);
		if ( substr($onlypath, -1) !== '/' ) $onlypath .= '/';
		$exportpath = sys_get_temp_dir();
		if ( substr($exportpath, -1) !== '/' ) $exportpath .= '/';
		$result = ( $onlypath === $exportpath );
	}
	return $result;
}