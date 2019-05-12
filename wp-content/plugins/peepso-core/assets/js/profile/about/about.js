import $ from 'jquery';

$( $ => {
	let $profiles = $( '.ps-js-profile-list' );
	if ( ! $profiles.length ) return;

	let $editAll = $( '.ps-js-btn-edit-all' ),
		$saveAll = $( '.ps-js-btn-save-all' ).hide();

	$editAll.click( () => {
		let $btns = $profiles.find( '.ps-js-btn-edit' );

		$btns.click();
		$editAll.hide();
		$saveAll.show();
	} );

	$saveAll.click( () => {
		let $btns = $profiles.find( '.ps-js-btn-save' );

		$btns.click();
		$saveAll.hide();
		$editAll.show();
	} );
} );
