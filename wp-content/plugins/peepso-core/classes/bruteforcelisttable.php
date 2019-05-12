<?php

class PeepSoBruteForceListTable extends PeepSoListTable 
{
	/**
	 * Defines the query to be used, performs sorting, filtering and calling of bulk actions.
	 * @return void
	 */
	public function prepare_items()
	{
		global $wpdb;
		$input = new PeepSoInput();
		if (isset($_POST['action']))
			$this->process_bulk_action();

		$limit = 20;
		$offset = ($this->get_pagenum() - 1) * $limit;

		$this->_column_headers = array(
			$this->get_columns(),
			array(),
			$this->get_sortable_columns()
		);

		$totalItems = count(PeepSoBruteForce::fetch_all());

		$aQueueu = PeepSoBruteForce::fetch_all($limit, $offset, $input->val('orderby', NULL), $input->val('order', NULL));

		$this->set_pagination_args(array(
				'total_items' => $totalItems,
				'per_page' => $limit
			)
		);
		$this->items = $aQueueu;
	}

	/**
	 * Return and define columns to be displayed on the Request Data Queue table.
	 * @return array Associative array of columns with the database columns used as keys.
	 */
	public function get_columns()
	{
		return array(
			'attempts_ip' => __('IP', 'peepso-core'),
			'attempts_username' => __('Attempted Username', 'peepso-core'),
			'attempts_created_at' => __('Last Failed Attempt ', 'peepso-core'),
			'attempts_count' => __('Failed Attempts Count', 'peepso-core'),
			'attempts_lockout' => __('Lockouts Count', 'peepso-core'),
			'attempts_url' => __('URL Attacked', 'peepso-core'),
			'attempts_type' => __('Attempts Type', 'peepso-core'),
		);
	}

	/**
	 * Return and define columns that may be sorted on the Request Data Queue table.
	 * @return array Associative array of columns with the database columns used as keys.
	 */
	public function get_sortable_columns()
	{
		return array(
			'attempts_ip' => array('attempts_ip', false), 
			'attempts_username' => array('attempts_username', false), 
			'attempts_created_at' => array('attempts_created_at', false), 
			'attempts_count' => array('attempts_count', false),
			'attempts_lockout' => array('attempts_lockout', false),
			'attempts_url' => array('attempts_url', false),
			'attempts_type' => array('attempts_type', false)
		);
	}

	/**
	 * Return default values to be used per column
	 * @param  array $item The post item.
	 * @param  string $column_name The column name, must be defined in get_columns().
	 * @return string The value to be displayed.
	 */
	public function column_default($item, $column_name)
	{
		return $item[$column_name];
	}

	/**
	 * Returns the output for the type column.
	 * @param  array $item The current post item in the loop.
	 * @return string The type cell's HTML.
	 */
	public function column_attempts_type($item)
	{
	    switch ($item['attempts_type']) {
		case PeepSoBruteForce::TYPE_LOGIN:
			$ret = __('Login', 'peepso-core');
			break;
		case PeepSoBruteForce::TYPE_RESET_PASSWORD:
			$ret = __('Reset Password', 'peepso-core');
			break;
		default:
			$ret = __('Unknown', 'peepso-core');
			break;
		}

		return $ret;
	}
}

// EOF