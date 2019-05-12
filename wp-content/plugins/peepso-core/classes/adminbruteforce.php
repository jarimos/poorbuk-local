<?php

class PeepSoAdminBruteForce
{
	/** 
	 * DIsplays the table for managing the Request data queue.
	 */
	public static function administration()
	{
		$oPeepSoListTable = new PeepSoBruteForceListTable();
		$oPeepSoListTable->prepare_items();

		#echo "<div id='peepso' class='wrap'>";
		// PeepSoAdmin::admin_header(__('Brute Force Attempts Logs', 'peepso-core'));

		echo '<form id="form-request-data" method="post">';
		wp_nonce_field('bulk-action', 'request-data-nonce');
		$oPeepSoListTable->display();
		echo '</form>';
	}
}