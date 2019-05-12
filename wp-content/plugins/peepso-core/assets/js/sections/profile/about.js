import $ from 'jquery';
import { observer, modules } from 'peepso';
import { userid as USER_ID } from 'peepsodata';

let $fields;
let $btnEditAll;
let $btnSaveAll;

/**
 * Initialize user profile form section.
 *
 * @private
 */
function init() {
	let $wrapper = $( '.ps-js-profile-list' );
	if ( ! $wrapper.length ) {
		return;
	}

	$fields = $wrapper.find( '.ps-js-profile-item' );
	$btnEditAll = $( '.ps-js-btn-edit-all' );
	$btnSaveAll = $( '.ps-js-btn-save-all' ).hide();

	if ( $fields.length ) {
		$fields.each( ( i, field ) => initField( field, 'lazy' ) );
	} else {
		$btnEditAll.hide();
	}
}

/**
 * Initialize field functionality.
 *
 * @param {HTMLElement} container
 * @param {string} lazy
 * @private
 */
function initField( container, lazy ) {
	// Defer initialization until the edit button is clicked.
	if ( lazy === 'lazy' ) {
		$( '.ps-js-btn-edit', container ).one( 'click', function() {
			initField( container );
			$( this ).click();
		} );
		return;
	}

	let $container = $( container );
	let $ctView = $container.find( '.ps-list-info-content-text' );
	let $ctEdit = $container.find( '.ps-list-info-content-form' );
	let $ctError = $container.find( '.ps-list-info-content-error' );
	let $ctValidation = $container.find( '.ps-js-validation' );
	let $btnEdit = $ctView.find( '.ps-js-btn-edit' );
	let $btnSave = $ctEdit.find( '.ps-js-btn-save' );
	let $btnCancel = $ctEdit.find( '.ps-js-btn-cancel' );
	let $input = $ctEdit.find(
		'input[type=text],input[type=checkbox],input[type=radio],textarea,select'
	);

	let fieldId = $container.data( 'id' );
	let fieldValue = getFieldValue( $.makeArray( $input ) );

	$btnEdit.on( 'click', function() {
		$ctView.hide();
		$ctError.hide();
		$ctEdit.show();
		$ctValidation.removeClass( 'ps-alert-danger' );
	} );

	$btnCancel.on( 'click', function() {
		restoreFieldValue( $.makeArray( $input ), fieldValue );
		toggleBeforeUnload();
		$ctEdit.hide();
		$ctError.hide();
		$ctView.show();
		$btnSave.add( $btnCancel ).removeAttr( 'disabled' );
	} );

	$btnSave.on( 'click', function() {
		if ( $btnSave.data( 'saving' ) ) {
			return;
		}

		$btnSave.find( 'img' ).show();
		$btnSave.data( 'saving', true );
		$btnSave.add( $btnCancel ).attr( 'disabled', 'disabled' );

		modules.user.updateField();

		// toggleBeforeUnload();
		// $ctEdit.hide();
		// $ctError.hide();
		// $ctView.show();
	} );

	// let $trigger =
	// let $container = $( container ),
	// 	$ctView = $container.find( '.' );
	// $ctEdit = $container.find( '.' );
	// ( $btnEdit = $container.find( '.ps-js-btn-edit' ) ),
	// 	( $btnSave = $container.find( '.ps-js-btn-save' ) ),
	// 	( $btnCancel = $container.find( '.' ) );
	// console.log( container );
}

/**
 * Get the submittable value of a field value.
 *
 * @param {Array.<HTMLElement} inputs
 * @return {*}
 * @private
 */
function getFieldValue( inputs ) {}

/**
 * Get the submittable value of a field value.
 *
 * @param {Array.<HTMLElement>} inputs
 * @param {*} value
 * @private
 */
function restoreFieldValue( inputs, value ) {}

/**
 * Toggle "beforeunload" event handler based on current form state.
 */
function toggleBeforeUnload() {}

export default { init };
