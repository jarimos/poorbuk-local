<?php

class PeepSoBruteForce
{
	const TABLE = 'peepso_brute_force_attempts_logs';
	const TYPE_LOGIN = 0;
	const TYPE_RESET_PASSWORD = 1;

	public static $max_retries;
	public static $max_lockouts;
	public static $lockout_time;
	public static $lockouts_extend;
	public static $reset_retries;
	public static $retries_left;
	public static $notify_email;

	public static $cannot_login;
	public static $bruteforce_error;
	public static $current_ip;

	public static $error;

	private static $instance = NULL;

	public function __construct() 
	{
		self::initialize();
	}

    public static function get_instance() {
        if(NULL == self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

	public static function initialize()
	{
		self::$max_retries = PeepSo::get_option('brute_force_max_retries', 3);
		self::$max_lockouts = PeepSo::get_option('brute_force_max_lockout', 5);
		self::$lockout_time = PeepSo::get_option('brute_force_lockout_time', 15);
		self::$lockouts_extend = PeepSo::get_option('brute_force_extend_lockout', 24);
		self::$reset_retries = PeepSo::get_option('brute_force_reset_retries', 24);
		self::$notify_email = PeepSo::get_option('brute_force_email_notification', 0);

		self::$lockout_time = self::$lockout_time * 60;
		self::$lockouts_extend = self::$lockouts_extend * 60 * 60;
		self::$reset_retries = self::$reset_retries * 60 * 60;

		// When was the database cleared last time
		$last_reset  = PeepSo::get_option('brute_force_last_reset', time());
		
		// Clear retries
		if((time() - $last_reset) >= self::$reset_retries){
			self::reset_retries();
		}

		self::$error = array();
	}

	/**
	 * Create gdpr table if not exists
	 */
	public static function create_table()
	{
		// create table if not exists
		global $wpdb;
		
		$wpdb->query("CREATE TABLE IF NOT EXISTS `{$wpdb->prefix}" . self::TABLE . "` (
					`attempts_id`			INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
					`attempts_username`		VARCHAR(255) NOT NULL DEFAULT '',
					`attempts_time`			INT(10) NOT NULL DEFAULT '0',
					`attempts_count` 		INT(10) NOT NULL DEFAULT '0',
					`attempts_lockout` 		INT(10) NOT NULL DEFAULT '0',
					`attempts_ip` 			VARCHAR(100) NOT NULL DEFAULT '',
					`attempts_url` 			VARCHAR(255) NOT NULL DEFAULT '',
					`attempts_created_at`	TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
					`attempts_type`			TINYINT(1) UNSIGNED NOT NULL DEFAULT '0',

					PRIMARY KEY (`attempts_id`),
					UNIQUE KEY `ip` (`attempts_ip`, `attempts_type`)
				) ENGINE=InnoDB");
	}

	/**
	 * Brute Force authenticate
	 */
	public static function authenticate($user, $username, $password)
	{
		if(!PeepSo::get_option('brute_force_enable', 0)) {
			return $user;
		}

		$bruteforce = self::get_instance();

		if($bruteforce::can_login()){
			return $user;
		}
		
		$bruteforce::$cannot_login = 1;
		
		return new WP_Error('ip_blocked', implode('', $bruteforce::$error), 'peepso-core');
	}

	/**
	 * Brute force retrieve password
	 */
	public static function retrieve_password_key($username, $key)
	{
		global $wpdb;

		$url = @addslashes((!empty($_SERVER['HTTPS']) ? 'https://' : 'http://').$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI']);
		$url = esc_url($url);

		$result = $wpdb->get_row("SELECT * FROM `" . $wpdb->prefix . self::TABLE . "` WHERE `attempts_ip` = '".self::getip()."' AND `attempts_type` = ".self::TYPE_RESET_PASSWORD."", ARRAY_A);

		if(!empty($result)){
			$query = $wpdb->prepare(
				"UPDATE `" . $wpdb->prefix . self::TABLE . "` SET `attempts_username` = %s, `attempts_time` = %d,  `attempts_url` = %s WHERE `attempts_ip` = %s AND `attempts_type` = %d",
				$username,
				time(),
				$url,
				self::getip(),
				self::TYPE_RESET_PASSWORD
			);
		} else {
			$query = $wpdb->prepare(
				"INSERT INTO `" . $wpdb->prefix . self::TABLE . "` SET `attempts_username` = %s, `attempts_time` = %d, `attempts_count` = '0', `attempts_ip` = %s, `attempts_lockout` = '0', `attempts_url` = %s, `attempts_type` = %d",
				$username,
				time(),
				self::getip(),
				$url,
				self::TYPE_RESET_PASSWORD
			);
		}

		$result = $wpdb->query($query);

		return $result;
	}

	public static function allow_password_reset($allow, $user_id)
	{
		if($allow) {

			global $wpdb;

			// Get the logs
			$result = $wpdb->get_row("SELECT * FROM `".$wpdb->prefix . self::TABLE . "` WHERE `attempts_ip` = '" . self::getip() . "' AND `attempts_type` = ".self::TYPE_RESET_PASSWORD."", ARRAY_A);

			if(!empty($result['attempts_time'])){
				
				// Is he in the lockout time ?
				$lockout_time = 15 * 60;
				if($result['attempts_time'] >= (time() - $lockout_time)) {
					$banlift = ceil((($result['attempts_time'] + $lockout_time) - time()) / 60);
					
					$_time = sprintf( _n( '%s minute', '%s minutes', $banlift, 'peepso-core' ), number_format_i18n( $banlift ) );
					
					if($banlift > 60){
						$banlift = ceil($banlift / 60);
						$_time = sprintf( _n( '%s hour', '%s hours', $banlift, 'peepso-core' ), number_format_i18n( $banlift ) );
					}

					return (new WP_Error('no_password_reset', sprintf(__('Sorry you can only do it once every 15 minutes. Please try again after %s.', 'peepso-core'), $_time)));;
				}
			}
		}

		return $allow;
	}

	// Get the client IP
	private static function _getip()
	{
		if(isset($_SERVER["HTTP_X_FORWARDED_FOR"])){
			return $_SERVER["HTTP_X_FORWARDED_FOR"];
		}elseif(isset($_SERVER["REMOTE_ADDR"])){
			return $_SERVER["REMOTE_ADDR"];
		}elseif(isset($_SERVER["HTTP_CLIENT_IP"])){
			return $_SERVER["HTTP_CLIENT_IP"];
		}
	}

	// Get the client IP
	private static function getip()
	{
		
		// Just so that we have something
		$ip = self::_getip();
		
		// Hacking fix for X-Forwarded-For
		if(!self::valid_ip($ip)){
			return '';
		}
		
		return $ip;
		
	}

	// Check if an IP is valid
	private static function valid_ip($ip)
	{
		
		// IPv6
		if(self::valid_ipv6($ip)){
			return true;
		}
		
		// IPv4
		if(!ip2long($ip)){
			return false;
		}
		
		return true;
	}

	private static function valid_ipv6($ip)
	{

		$pattern = '/^((([0-9A-Fa-f]{1,4}:){7}[0-9A-Fa-f]{1,4})|(([0-9A-Fa-f]{1,4}:){6}:[0-9A-Fa-f]{1,4})|(([0-9A-Fa-f]{1,4}:){5}:([0-9A-Fa-f]{1,4}:)?[0-9A-Fa-f]{1,4})|(([0-9A-Fa-f]{1,4}:){4}:([0-9A-Fa-f]{1,4}:){0,2}[0-9A-Fa-f]{1,4})|(([0-9A-Fa-f]{1,4}:){3}:([0-9A-Fa-f]{1,4}:){0,3}[0-9A-Fa-f]{1,4})|(([0-9A-Fa-f]{1,4}:){2}:([0-9A-Fa-f]{1,4}:){0,4}[0-9A-Fa-f]{1,4})|(([0-9A-Fa-f]{1,4}:){6}((\b((25[0-5])|(1\d{2})|(2[0-4]\d)|(\d{1,2}))\b)\.){3}(\b((25[0-5])|(1\d{2})|(2[0-4]\d)|(\d{1,2}))\b))|(([0-9A-Fa-f]{1,4}:){0,5}:((\b((25[0-5])|(1\d{2})|(2[0-4]\d)|(\d{1,2}))\b)\.){3}(\b((25[0-5])|(1\d{2})|(2[0-4]\d)|(\d{1,2}))\b))|(::([0-9A-Fa-f]{1,4}:){0,5}((\b((25[0-5])|(1\d{2})|(2[0-4]\d)|(\d{1,2}))\b)\.){3}(\b((25[0-5])|(1\d{2})|(2[0-4]\d)|(\d{1,2}))\b))|([0-9A-Fa-f]{1,4}::([0-9A-Fa-f]{1,4}:){0,5}[0-9A-Fa-f]{1,4})|(::([0-9A-Fa-f]{1,4}:){0,6}[0-9A-Fa-f]{1,4})|(([0-9A-Fa-f]{1,4}:){1,7}:))$/';
		
		if(!preg_match($pattern, $ip)){
			return false;	
		}
		
		return true;
		
	}

	private static function can_login()
	{
		
		global $wpdb;

		self::$current_ip = self::getip();
		
		// Get the logs
		$result = $wpdb->get_row("SELECT * FROM `".$wpdb->prefix . self::TABLE . "` WHERE `attempts_ip` = '" . self::$current_ip . "' AND `attempts_type` = ".self::TYPE_LOGIN."", ARRAY_A);
		
		if(!empty($result['attempts_count']) && ($result['attempts_count'] % self::$max_retries) == 0){
			
			// Has he reached max lockouts ?
			if($result['attempts_lockout'] >= self::$max_lockouts){
				self::$lockout_time = self::$lockouts_extend;
			}
			
			// Is he in the lockout time ?
			if($result['attempts_time'] >= (time() - self::$lockout_time)){
				$banlift = ceil((($result['attempts_time'] + self::$lockout_time) - time()) / 60);
				
				$_time = sprintf( _n( '%s minute', '%s minutes', $banlift, 'peepso-core' ), number_format_i18n( $banlift ) );
				
				if($banlift > 60){
					$banlift = ceil($banlift / 60);
					$_time = sprintf( _n( '%s hour', '%s hours', $banlift, 'peepso-core' ), number_format_i18n( $banlift ) );
				}
				self::$error['ip_blocked'] = sprintf(__('You have exceeded maximum login retries. Please try after %s', 'peepso-core'), $_time);
				
				return false;
			}
		}
		
		return true;
	}

	// When the login fails, then this is called
	// We need to update the database
	public static function login_failed($username){
		
		global $wpdb;

		$bruteforce = self::get_instance();

		if(empty($bruteforce::$cannot_login) && PeepSo::get_option('brute_force_enable', 0) === 1){
			
			$url = @addslashes((!empty($_SERVER['HTTPS']) ? 'https://' : 'http://').$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI']);
			$url = esc_url($url);
			
			$result = $wpdb->get_row("SELECT * FROM `" . $wpdb->prefix . $bruteforce::TABLE . "` WHERE `attempts_ip` = '".$bruteforce::$current_ip."' AND `attempts_type` = 0", ARRAY_A);

			if(!empty($result)){
				$lockout = floor((($result['attempts_count']+1) / $bruteforce::$max_retries));

				$query = $wpdb->prepare(
					"UPDATE `" . $wpdb->prefix . $bruteforce::TABLE . "` SET `attempts_username` = %s, `attempts_time` = %d, `attempts_count` = `attempts_count`+1, `attempts_lockout` = %d, `attempts_url` = %s WHERE `attempts_ip` = %s AND `attempts_type` = %d", 
					$username,
					time(),
					$lockout,
					$url,
					$bruteforce::$current_ip,
					self::TYPE_LOGIN
				);	
				$sresult = $wpdb->query($query);
				
				// Do we need to email admin ?
				if(!empty($bruteforce::$notify_email) && $lockout >= $bruteforce::$notify_email){
					if (strpos($username, '@') !== FALSE) {
						$get_by = 'email';
					} else {
						$get_by = 'login';
					}
					$user = PeepSoUser::get_instance(get_user_by($get_by, $username)->ID);

					$data = array(
	                    'attempts_ip' => $bruteforce::$current_ip,
	                    'attempts_time' => date('d/m/Y H:i:s', time()),
	                    'attempts_count' => ($result['attempts_count']+1),
	                    'attempts_lockout' => $lockout,
	                    'attempts_username' => $username,
						'attempts_lockout_until' => date('d/m/Y H:i:s', time() + $bruteforce::$lockout_time),
						'useremail' => $user->get_email(),
						'userfirstname' => $user->get_firstname(),
						'currentuserfullname' => $user->get_fullname(),
					);

					// send user an email
					if (!empty($user->get_id())) {
						PeepSoMailQueue::add_message($user->get_id(), $data, __('{sitename} - Failed Login Attempts', 'peepso-core'), 'failed_login_attempts', PeepSo::MODULE_ID);
					}
				}
			}else{
				$query = $wpdb->prepare(
					"INSERT INTO `" . $wpdb->prefix . $bruteforce::TABLE . "` SET `attempts_username` = %s, `attempts_time` = %d, `attempts_count` = '1', `attempts_ip` = %s, `attempts_lockout` = '0', `attempts_url` = %s, `attempts_type` = %d",
					$username,
					time(),
					$bruteforce::$current_ip,
					$url,
					self::TYPE_LOGIN
				);
				$insert = $wpdb->query($query);
			}
		
			// We need to add one as this is a failed attempt as well
			$result['attempts_count'] = isset($result['attempts_count']) ? $result['attempts_count'] : 0;
			$result['attempts_count'] = $result['attempts_count'] + 1;

			$bruteforce::$retries_left = ($bruteforce::$max_retries - ($result['attempts_count'] % $bruteforce::$max_retries));
			$bruteforce::$retries_left = ($bruteforce::$retries_left == $bruteforce::$max_retries) ? 0 : $bruteforce::$retries_left;
			
		}
	}


	// Handles the error of the password not being there
	public static function error_handler($errors, $redirect_to){
		
		global $wpdb;

		$bruteforce = self::get_instance();
		
		// Remove the empty password error
		if(is_wp_error($errors)){
			
			$codes = $errors->get_error_codes();
			
			foreach($codes as $k => $v){
				if($v == 'invalid_username' || $v == 'incorrect_password'){
					$show_error = 1;
				}
			}
			
			$errors->remove('invalid_username');
			$errors->remove('incorrect_password');
			
		}

		// Add the error
		if(!empty($show_error) && empty($bruteforce::$cannot_login)){
			$errors->add('invalid_userpass', __('<b>ERROR:</b> Incorrect Username or Password', 'peepso-core'));
		}
		
		// Add the number of retires left as well
		if(count($errors->get_error_codes()) > 0 && isset($bruteforce::$retries_left)){
			$errors->add('retries_left', $bruteforce::retries_left());
		}
		
		return $errors;
		
	}



	// Returns a string with the number of retries left
	public static function retries_left(){
		
		global $wpdb;

		$bruteforce = self::get_instance();
		
		// If we are to show the number of retries left
		if(isset($bruteforce::$retries_left)){
			return sprintf( _n( '%s  attempt left', '%s  attempts left', $bruteforce::$retries_left, 'peepso-core' ), number_format_i18n( $bruteforce::$retries_left ) );
		}
		
	}

	public static function reset_retries(){
		
		global $wpdb;

		$bruteforce = self::get_instance();
		
		$deltime = time() - $bruteforce::$reset_retries;	
		$result = $wpdb->query("DELETE FROM `".$wpdb->prefix . self::TABLE . "` WHERE `attempts_time` <= '".$deltime."';");
		
		PeepSo::set_option('brute_force_last_reset', time());
		
	}


	/**
	 * Fetches all data on the database.
	 * @param  int $limit  How many records to fetch.
	 * @param  int $offset Fetch records beginning from this index.
	 * @param  string  $order  Order by column.
	 * @param  string  $dir    The sort direction, defaults to 'asc'
	 * @return array Array of the result set.
	 */
	public static function fetch_all($limit = NULL, $offset = 0, $order = NULL, $dir = 'asc')
	{
		global $wpdb;

		$query = 'SELECT *				
			FROM `' . self::get_table_name() . '` ';

		if (isset($order))
			$query .= ' ORDER BY `' . $order . '` ' . $dir;

		if (isset($limit))
			$query .= ' LIMIT ' . $offset . ', ' . $limit;

		return ($wpdb->get_results($query, ARRAY_A));
	}

	/**
	 * Convenience function to return the login failed attempts log table name as a string.
	 * @return string The table name.
	 */
	public static function get_table_name()
	{
		global $wpdb;

		return ($wpdb->prefix . self::TABLE);
	}
}

// EOF
