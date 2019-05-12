<?php

class PeepSoUserSearch
{
	public $results;
	public $query;

	private $_iterator = NULL,
		$_array_object = NULL;

	/**
	 * Search for users
	 * @param  string $search  The search string
	 * @param  int $user_id The user doing the search, if set to NULL defaults to the current user
	 * @return WP_User_Query
	 */
	public function __construct($args = array(), $user_id = NULL, $search = '')
	{
		global $wpdb;

		if (is_null($user_id)) {
			$user_id = get_current_user_id();
		}

		$args = apply_filters('peepso_user_search_args',
			array_merge(
				$args,
				array(
					'fields' => 'ID',
					'_peepso_user_id' => intval($user_id),
					'_peepso_search' => $search
				)
			)
		);

		add_action('pre_user_query', array(&$this, 'pre_user_query'));

		$this->query = new WP_User_Query($args);
		
		$this->results = $this->query->results;
		$this->total = $this->query->get_total();
		$this->_array_object = new ArrayObject($this->results);
		$this->_iterator = $this->_array_object->getIterator();
		remove_action('pre_user_query', array(&$this, 'pre_user_query'));
	}

	/**
	 * Alter the WP_User_Query object to account for privacy settings
	 * @param  WP_User_Query $wp_user_query
	 */
	public function pre_user_query(WP_User_Query $wp_user_query)
	{
		global $wpdb;

		$user_id = $wp_user_query->query_vars['_peepso_user_id'];
		$search = $wp_user_query->query_vars['_peepso_search'];

		$search_in = FALSE;
		if(count($search_arr = explode(' ', trim($search))) > 1) {
			$search_in = TRUE;
			$search_regex=implode('|', $search_arr);
		}

		global $wp_version;
		if (version_compare($wp_version, '4.0', 'lt'))
			$search = like_escape($search);
		else
			$search = $wpdb->esc_like($search);

		// check to see if the "Allow User to Override Name Setting" option is enabled.
		if (!empty($user_id) && 1 === intval(PeepSo::get_option('system_override_name', 0))) {
			// read the user's setting for display options
			$current_user = PeepSoUser::get_instance($user_id);
			$display_name_as = $current_user->get_display_name_as();
		} else // get the site config setting for the display name style.
			$display_name_as = PeepSo::get_option('system_display_name_style', 'username');

		/** ORDERING */
		// Is there custom ordering defined?
		$wp_user_field = 'user_login';
		$order = isset($wp_user_query->query_vars['order']) ? $wp_user_query->query_vars['order'] : 'ASC';

		$wp_user_query->query_from .= " LEFT JOIN (SELECT GROUP_CONCAT(DISTINCT `meta_value` ORDER BY `meta_key` ASC SEPARATOR '') AS `meta_value`, `user_id` FROM `$wpdb->usermeta` WHERE `meta_key` IN ('first_name', 'nickname') GROUP BY `user_id`) `psmeta1`
			ON `$wpdb->users`.`ID` = `psmeta1`.`user_id` ";
		$wp_user_query->query_from .= " LEFT JOIN (SELECT GROUP_CONCAT(DISTINCT `meta_value` ORDER BY `meta_key` ASC SEPARATOR '') AS `meta_value`, `user_id` FROM `$wpdb->usermeta` WHERE `meta_key` IN ('last_name', 'nickname') GROUP BY `user_id`) `psmeta2`
			ON `$wpdb->users`.`ID` = `psmeta2`.`user_id` ";

		$wp_user_query->query_orderby = " ORDER BY `psmeta1`.`meta_value` $order, `psmeta2`.`meta_value` $order";

		if( isset($wp_user_query->query_vars['orderby']) && isset($wp_user_query->query_vars['order']) ) {
			$order_by    = $wp_user_query->query_vars['orderby'];
			$order 		 = $wp_user_query->query_vars['order'];

			// Go deeper only if the order_by is peepso, otherwise we let WP handle it
			if(!empty($order_by)) {
				switch ($order_by) {
					case 'peepso_last_activity':
						$order_by = '`acc`.`usr_last_activity`';
						break;
					case 'registered':
						$order_by = "`$wpdb->users`.`user_registered`";
						break;
					case 'username':
						$order_by = "`$wpdb->users`.`user_login`";
						break;
					case 'meta_value':
						if ($wp_user_query->query_vars['meta_key'] == 'last_name') {
							$order_by = "`psmeta2`.`meta_value`";
						} else {
							$order_by = "`psmeta1`.`meta_value`";
						}
						break;
					default: 
						$order_by = '';
						break;
				}

				if(strlen($order_by)) {
					$wp_user_query->query_orderby = " ORDER BY $order_by $order";
				}
			}

		}  else if ($display_name_as != 'real_name') {
			$wp_user_query->query_orderby = ' ORDER BY `user_login` ';
		}

		/** SEARCH */
		if (!empty($search)) {
			if ($search_in === TRUE) {
				$search_key = 'REGEXP';
				$search_value = $search_regex;
			} else {
				$search_key = 'LIKE';
				$search_value = '%' . $search . '%';
			}

			$wp_user_query->query_where .= ' AND ( CASE
					WHEN `acc`.`usr_first_name_acc` <> ' . PeepSo::ACCESS_PRIVATE . ' AND `acc`.`usr_last_name_acc` <> ' . PeepSo::ACCESS_PRIVATE . '
					THEN (' . $wpdb->prepare(' CAST(`psmeta1`.`meta_value` AS CHAR) ' . $search_key . ' %s ', $search_value) . ' OR ' . 
					$wpdb->prepare(' CAST(`psmeta2`.`meta_value` AS CHAR) ' . $search_key . ' %s ', $search_value) . ' OR ' . $wpdb->prepare('`' . $wpdb->users . '`.`' . $wp_user_field . '` ' . $search_key . ' %s ', $search_value) . ')
					WHEN `acc`.`usr_first_name_acc` <> ' . PeepSo::ACCESS_PRIVATE . '
					THEN ' . $wpdb->prepare(' CAST(`psmeta1`.`meta_value` AS CHAR) ' . $search_key . ' %s ', $search_value) . '
					WHEN `acc`.`usr_last_name_acc` <> ' . PeepSo::ACCESS_PRIVATE . '
					THEN ' . $wpdb->prepare(' CAST(`psmeta2`.`meta_value` AS CHAR) ' . $search_key . ' %s ', $search_value) . '
				END
			) ';

			if (PeepSo::get_option('members_email_searchable', 0) === 1) {
				$wp_user_query->query_where .= $wpdb->prepare(' OR `' . $wpdb->users . '`.`user_email` ' . $search_key . ' %s ', $search_value);
			} 
			$wp_user_query->query_orderby = ' GROUP BY `' . $wpdb->users . '`.`ID` ' . $wp_user_query->query_orderby;
		}

		$wp_user_query->query_from .= '
			LEFT JOIN `' . $wpdb->prefix . PeepSoUser::TABLE . '` `acc`
				ON `acc`.`usr_id` = `' . $wpdb->users . '`.`ID`
			LEFT JOIN `' . $wpdb->prefix . PeepSoActivity::BLOCK_TABLE_NAME  . '` `blk`
				ON `blk_user_id` = `' . $wpdb->users . '`.`ID` AND `blk_blocked_id`= ' . $user_id . '
					OR `blk_user_id` = ' . $user_id . ' AND `blk_blocked_id` = `' . $wpdb->users . '`.`ID`
		';

		/** EXCLUDE SELF*/
		#$wp_user_query->query_where .= ' AND `ID` <> ' . $user_id . ' ';
		// exclude banned users and unvalidated users
		$wp_user_query->query_where .= ' AND `acc`.`usr_role` NOT IN ("register", "verified", "ban") ';

		/** PRIVACY **/
		$wp_user_query->query_where .= '
			AND `acc`.`usr_profile_acc` <> ' . PeepSo::ACCESS_PRIVATE . '
		';
		// Members only
		$wp_user_query->query_where .= '
			AND IF (`acc`.`usr_profile_acc` = ' . PeepSo::ACCESS_MEMBERS . ', ' . $user_id . ' > 0, TRUE)
		';

		$blocked_query = ' AND `blk_blocked_id` IS NULL ';
		$following_query ='';

		// Check config option for Allow users to hide themselves from all user listings
		if ((!PeepSo::is_admin()) && (1 === intval(PeepSo::get_option('allow_hide_user_from_user_listing', 0)))) {
			$wp_user_query->query_from .= ' LEFT JOIN `' . $wpdb->usermeta . '` `psmeta_hideme` 
                    ON (`' . $wpdb->users . '`.`ID` = `psmeta_hideme`.`user_id` AND `psmeta_hideme`.`meta_key` = \'peepso_is_hide_profile_from_user_listing\') ';
            $wp_user_query->query_where .= ' AND (  `psmeta_hideme`.`meta_value` <> \'1\' OR `psmeta_hideme`.`user_id` IS NULL )';
		}

		/** MORE FILTERING **/
		if(array_key_exists('_peepso_args', $wp_user_query->query_vars) ) {
			$peepso_vars = $wp_user_query->query_vars['_peepso_args'];
			if( is_array($peepso_vars) && count($peepso_vars) ) {
				foreach ($peepso_vars as $key=>$value) {
					$key = $wpdb->_real_escape($key);
					$value = $wpdb->_real_escape($value);

					if('blocked' == $key) {
                        $blocked_query = ' AND `blk_blocked_id` IS NOT NULL ';
                    }
                    elseif('following' == $key) {
					    if(0 == $value) {
                            $blocked_query = ' AND (
                                                    NOT EXISTS (SELECT uf_id FROM ' . $wpdb->prefix . 'peepso_user_followers WHERE `uf_passive_user_id`=`'.$wpdb->users . '`.`ID` AND `uf_active_user_id`='.$user_id.')
                                                    OR
                                                    EXISTS (SELECT uf_id FROM ' . $wpdb->prefix . 'peepso_user_followers WHERE `uf_follow`=0 AND `uf_passive_user_id`=`'.$wpdb->users . '`.`ID` AND `uf_active_user_id`='.$user_id.')
                                               )';
                        } elseif(1 == $value) {
                            $blocked_query = ' AND EXISTS (SELECT uf_id FROM ' . $wpdb->prefix . 'peepso_user_followers WHERE `uf_follow`=1 AND `uf_passive_user_id`=`'.$wpdb->users . '`.`ID` AND `uf_active_user_id`='.$user_id.') ';
                        }
                    }
					elseif ('meta_' == substr($key,0,5)) {
						$key ='peepso_user_field_'.str_replace('meta_','',$key);
						$wp_user_query->query_where .= $wpdb->prepare('AND ID IN (SELECT user_id FROM ' . $wpdb->usermeta . ' WHERE `meta_key` = "%s" AND CAST(`meta_value` AS CHAR) REGEXP %s) ', $key, $value);
					} else {
						if ($key == 'cover_custom') {
							$wp_user_query->query_where .= " AND `acc`.`usr_cover_photo` IS NOT NULL ";
						} else {
							$wp_user_query->query_where .= " AND `acc`.`usr_$key`='$value' ";
						}
					}
				}
			}
		}

		$wp_user_query->query_where .= $blocked_query;

		/**
		 * Fires after the WP_User_Query has been parsed, and before
		 * the query is executed.
		 *
		 * The passed WP_User_Query object contains SQL parts formed
		 * from parsing the given query.
		 *
		 * @since 3.1.0
		 *
		 * @param WP_User_Query $this The current WP_User_Query instance,
		 *                            passed by reference.
		 */
		do_action_ref_array('peepso_pre_user_query', array(&$wp_user_query, $user_id));
	}

	/**
	 * Iterates through the ArrayObject and returns the current user in the loop as an
	 * instance of PeepSoUser.
	 * @return PeepSoUser A PeepSoUser instance of the current friend in the loop.
	 */
	public function get_next()
	{
		if (is_null($this->_array_object))
			return (FALSE);

		if ($this->_iterator->valid()) {
			$user = PeepSoUser::get_instance($this->_iterator->current());
			$this->_iterator->next();
			return ($user);
		}

		return (FALSE);
	}
}