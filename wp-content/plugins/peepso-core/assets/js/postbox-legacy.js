/*
 * Implementation of PeepSo's PostBox
 * @package PeepSo
 * @author PeepSo
 */

( function( $ ) {
	$.fn.pspostbox = function( options ) {
		if ( this.length <= 0 ) return;

		var _self = this;

		this.$textarea = null;
		this.$access = null;
		this.$charcount = null;
		this.$cancel_button = null;
		this.$save_button = null;
		this.$posttabs = null;
		this.$privacy_dropdown = null;

		this.can_submit = false;

		/**
		 * Initialize this postbox instance
		 * @param  {array} opts Array of options to override the defaults
		 * @return {object}      The plugin instance
		 */
		this.init = function( opts ) {
			var _self = this;
			var _opts = {
				textarea: 'textarea.ps-postbox-textarea',
				mirror: '.ps-postbox-mirror',
				addons: '.ps-postbox-addons',
				access: '#postbox_acc',
				save_url: 'postbox.post',
				charcount: '.post-charcount',
				cancel_button: '.ps-button-cancel',
				save_button: '.postbox-submit',
				send_button_text: undefined,
				text_length: peepsodata.postsize,
				autosize: true // allows the textarea to adjust it's height based on the length of the content
			};

			this.opts = _opts;
			$.extend( true, this.opts, opts );

			this.guid = _.uniqueId( 'postbox-' );

			this.$posttabs = $( this )
				.find( '.ps-postbox-tab-root' )
				.ps_posttabs( {
					container: this
				} );

			this.$textarea = jQuery( this.opts.textarea, this );
			this.$mirror = jQuery( this.opts.mirror, this );
			this.$addons = jQuery( this.opts.addons, this );
			this.$access = $( this.opts.access, this );
			this._default_access = this.$access.val();
			this.$charcount = $( this.opts.charcount, this );
			this.$cancel_button = $( this.opts.cancel_button, this );
			this.$save_button = $( this.opts.save_button, this );

			if ( ! _.isUndefined( this.opts.send_button_text ) ) {
				this.$save_button.html( this.opts.send_button_text );
			}

			this.$privacy = $( '#privacy-tab', this );
			this.orig_height = this.$textarea.height();

			if ( this.opts.autosize ) this.$textarea.autosize();

			// Setup events
			this.$textarea
				.attr( 'maxlength', this.opts.text_length )
				.on( 'keydown', function( e ) {
					_self.on_keydown( e );
				} )
				.on( 'keypress', function( e ) {
					_self.on_keypress( e );
				} )
				.on( 'paste', function( e ) {
					_self.on_paste( e );
				} )
				.on( 'focus', function( e ) {
					_self.on_focus();
				} )
				.on( 'keyup', function( e ) {
					_self.on_change();
				} )
				.on( 'input', function( e ) {
					_self.on_input();
				} );

			this.$charcount.html( this.opts.text_length + '' );

			this.$privacy_dropdown = $( '.ps-privacy-dropdown', this.$privacy );

			// setup privacy control
			this.$privacy_dropdown.on(
				'click a',
				jQuery.proxy( function( e ) {
					var $a = jQuery( e.target ).closest( 'a' ),
						$btn = this.$privacy.find( '.interaction-icon-wrapper .pstd-secondary' ),
						$input = jQuery( '#postbox_acc' );

					e.stopPropagation();

					$btn.find( 'i' ).attr( 'class', $a.find( 'i' ).attr( 'class' ) );
					$btn.find( 'span' ).html( $a.find( 'span' ).text() );
					$input.val( $a.attr( 'data-option-value' ) );
					this.$privacy_dropdown.hide();
				}, this )
			);

			this.$privacy.on( 'click', function( e ) {
				_self.privacy( e );
			} );

			ps_observer.add_action(
				'postbox_group_set',
				function( $postbox ) {
					if ( $postbox === _self ) {
						_self.$privacy.hide();
					}
				},
				10,
				1
			);

			ps_observer.add_action(
				'postbox_group_reset',
				function( $postbox ) {
					if ( $postbox === _self ) {
						_self.$privacy.show();
					}
				},
				10,
				1
			);

			jQuery( 'nav.ps-postbox-tab ul li a' ).click( this.clear_tabs );

			jQuery( '#status-post', _self ).addClass( 'active' );
			jQuery( this.$posttabs ).on( 'peepso_posttabs_show-status', function() {
				jQuery( '#status-post', _self ).addClass( 'active' );
				jQuery( '.ps-postbox-status' ).show();
			} );

			ps_observer.add_action(
				'postbox_type_set',
				function( $postbox, type ) {
					if ( $postbox === _self && type === 'status' ) {
						jQuery( '#status-post', _self ).trigger( 'click' );
					}
				},
				10,
				2
			);

			jQuery( '#status-post', _self ).on( 'click', function() {
				jQuery( _self.$posttabs )
					.find( "[data-tab='status']" )
					.trigger( 'click' );
			} );

			this.$posttabs.on( 'peepso_posttabs_submit-status', function() {
				_self.save_post();
			} );

			this.$posttabs.on( 'peepso_posttabs_cancel-status', function() {
				jQuery( '#status-post', _self ).removeClass( 'active' );
			} );

			this.$posttabs.on( 'peepso_posttabs_submit', function() {
				_self.$textarea.attr( 'readonly', 'readonly' );
				_self.find( '.ps-postbox-status' ).css( 'opacity', 0.5 );
			} );

			this.$posttabs.on( 'peepso_posttabs_cancel', function() {
				_self.$textarea.val( '' );
				_self.cancel_post();
			} );

			this.find( '.interactions > ul > li > .interaction-icon-wrapper a' ).on( 'click', function(
				e,
				x
			) {
				if ( x ) return;

				_self
					.find( '.interactions > ul > li > .interaction-icon-wrapper a' )
					.not( this )
					.trigger( 'peepso.interaction-hide', [ true ] );
			} );

			this._load_addons();
		};

		/**
		 * Allows addons to get a reference to this postbox instance
		 */
		this._load_addons = function() {
			var addons = ps_observer.apply_filters( 'peepso_postbox_addons', [] );

			$( addons ).each( function( index, addon ) {
				addon.set_postbox( _self );
				addon.init();
			} );
		};

		/**
		 * Applies filter for postbox clear tabs
		 * called when any <a> link within postbox is clicked
		 */
		this.clear_tabs = function() {
			ps_observer.apply_filters( 'postbox_clear_tabs', null );
			//			jQuery(".ps-postbox div.ps-postbox-popup.active").hide();
		};

		/**
		 * Sets post privacy on mouse up
		 * @param {object} e Event triggered
		 */
		this.privacy = function( e ) {
			var that = this;

			this.$privacy_dropdown.show();

			jQuery( document ).on( 'mouseup.postbox-privacy', function( e ) {
				if (
					! that.$privacy_dropdown.is( e.target ) && // if the target of the click isn't the container...
					0 === that.$privacy_dropdown.has( e.target ).length
				) {
					// ... nor a descendant of the container
					that.$privacy_dropdown.hide();
					jQuery( document ).off( 'mouseup.postbox-privacy' );
				}
			} );
		};

		/**
		 * Saves the post
		 * Invokes when Post button is saved
		 */
		this.save_post = function() {
			var req = {
				content: this.$textarea.val(),
				id: peepsodata.currentuserid,
				uid: peepsodata.userid,
				acc: this.$access.val(),
				type: 'activity'
			};

			if (
				! _.isUndefined( this.opts.postbox_req ) &&
				typeof Function === typeof this.opts.postbox_req
			) {
				req = this.opts.postbox_req.apply( null, [ req ] );
			}

			// send req through filter
			req = ps_observer.apply_filters( 'postbox_req', req );
			req = ps_observer.apply_filters( 'postbox_req_' + this.guid, req );

			// add request parameter into queue
			this.save_post_queue || ( this.save_post_queue = [] );
			this.save_post_queue.push( req );

			this.on_before_save();

			if ( this.save_post_progress ) return;
			this.save_post_progress = true;
			this.save_post_execute();
		};

		/**
		 * TODO: docblock
		 */
		this.save_post_execute = function() {
			if ( ! this.save_post_queue.length ) {
				if ( ! ps_observer.apply_filters( 'peepso_postbox_enter_to_send', false ) ) {
					jQuery( '.ps-postbox-loading', this ).hide();
					jQuery( '.ps-postbox-action', this ).css( 'display', 'flex' );
					this.$posttabs.on_cancel();
				}
				this.save_post_progress = false;
				this.on_queue_clear();
				postbox.remove_broken_thumbnails();
				return;
			}

			var req = this.save_post_queue.shift();

			if ( ! ps_observer.apply_filters( 'peepso_postbox_enter_to_send', false ) ) {
				jQuery( '.ps-postbox-action', this ).css( 'display', 'none' );
				jQuery( '.ps-postbox-loading', this ).show();
			}

			// Set to async so our filters run in order.
			$PeepSo.disableAsync().postJson( this.opts.save_url, req, function( json ) {
				if ( json.success ) {
					_self.on_save( json );
					jQuery( _self ).trigger( 'postbox.post_saved', [ req, json ] );
				} else {
					_self.on_error( json );
				}
				_self.save_post_execute();
			} );
		};

		/**
		 * Called before post save
		 * @param {object} json JSON object
		 */
		this.on_before_save = function() {
			if ( typeof Function === typeof this.opts.on_before_save ) {
				this.opts.on_before_save.apply( this );
			}
		};

		/**
		 * Called on post save
		 * @param {object} json JSON object
		 */
		this.on_save = function( json ) {
			var onSaveHandler = ps_observer.apply_filters( 'peepso_postbox_onsave', false, this );
			if ( typeof onSaveHandler === 'function' ) {
				onSaveHandler.apply( this, [ json ] );
			} else if ( typeof this.opts.on_save === 'function' ) {
				this.opts.on_save.apply( this, [ json ] );
				this.$textarea.css( 'height', this.orig_height );
				jQuery( this ).trigger( 'postbox.post_saved', this );
			}
		};

		/**
		 * Called on post save
		 * @param {object} json JSON object
		 */
		this.on_queue_clear = function() {
			if ( typeof Function === typeof this.opts.on_queue_clear ) {
				this.opts.on_queue_clear.apply( this );
			}
		};

		/**
		 * Invoked when an error on posting has occured
		 * @param {object} json JSON object
		 */
		this.on_error = function( json ) {
			if ( typeof Function === typeof this.opts.on_error ) {
				this.opts.on_error.apply( this, [ json ] );
			} else if ( false === _.isUndefined( json.errors[ 0 ] ) ) {
				// TODO: this needs translation
				psmessage.show( 'Error', json.errors[ 0 ] );
			}

			return;
		};

		/**
		 * Called when Cancel post button is invoked
		 */
		this.cancel_post = function() {
			//resets the privacy setting
			// this.$privacy.find("[data-option-value='" + this._default_access + "']").trigger("click");
			this.$textarea.css( 'height', this.orig_height );
			this.$textarea.removeAttr( 'readonly' );
			this.find( '.ps-postbox-status' ).css( 'opacity', '' );
			this.on_change();
			jQuery( this ).trigger( 'postbox.post_cancel' );
		};

		/**
		 * On focus event handler
		 * Called when onfocus event is triggered
		 */
		this.on_focus = function() {
			jQuery( '.ps-postbox-tab-root', _self ).hide();
			jQuery( '.ps-postbox-tab.interactions', _self ).attr(
				'data-tab-shown',
				this.$posttabs.current_tab().data( 'tab' )
			);
			jQuery( '.ps-postbox-tab.interactions', _self ).show();
		};

		/**
		 * Keydown events handler
		 * Called when keydown events occured
		 * @param {object} e Event triggered
		 */
		this.on_keydown = function( e ) {
			this._go_submit = false;
			if ( e.keyCode !== 13 ) {
				return true;
			}

			var val = this.$textarea.val(),
				trimmed = jQuery.trim( val );

			if ( ! e.shiftKey && ps_observer.apply_filters( 'peepso_postbox_enter_to_send', false ) ) {
				if ( trimmed.length || this.submitable( val ) ) {
					e.preventDefault();
					this._go_submit = true;
					this.$posttabs.on_submit();
					return false;
				}
			}

			if ( ! trimmed.length ) {
				e.preventDefault();
				return false;
			}
		};

		/**
		 * Keypress events handler
		 * Called when key has been pressed
		 * @param {object} e Event triggered
		 */
		this.on_keypress = function( e ) {
			if ( this.$textarea.val() >= this.opts.text_length ) {
				return false;
			}
		};

		/**
		 * Paste events handler
		 * Called when paste is tiggered
		 * @param {object} e Event triggered
		 */
		this.on_paste = function( e ) {
			var _self = this;
			e.originalEvent.clipboardData.getData( 'text/plain' ).slice( 0, this.text_length );

			setTimeout( function() {
				_self.on_change();
			}, 100 );
		};

		/**
		 * Input events handler
		 * Called when input is tiggered
		 * @param {object} e Event triggered
		 */
		this.on_input = function( e ) {
			if ( ! this._go_submit ) {
				ps_observer.apply_filters( 'peepso_postbox_input_changed', this.$textarea.val() );
				this.update_beautifier();
			}
		};

		/**
		 * Updates the character counter
		 */
		this.on_change = function() {
			var val = this.$textarea.val();
			var len = val.length;

			len = this.opts.text_length - len;

			if ( len < 0 ) len = 0;

			this.$charcount.html( len + '' );

			if ( len >= 50 ) this.$charcount.removeClass( 'alert-info' ).removeClass( 'alert-error' );
			else if ( 0 === len ) {
				// TODO: localize
				pswindow.show( '', 'You may only enter up to ' + this.opts.text_length + ' characters' );
			} else {
				if ( len < 30 ) this.$charcount.removeClass( 'alert-info' ).addClass( 'alert-error' );
				else if ( len < 50 ) this.$charcount.addClass( 'alert-info' ).removeClass( 'alert-error' );
			}

			if (
				this.submitable( val ) &&
				! ps_observer.apply_filters( 'peepso_postbox_enter_to_send', false )
			) {
				this.$save_button.show();
			} else {
				this.$save_button.hide();
			}

			// Escape newlines and
			var mirrorText = val
				.replace( /</g, '&lt;' )
				.replace( />/g, '&gt;' )
				.replace( /\n/g, '<br>' );

			this.$mirror.html( mirrorText );

			this.addons_update();
		};

		this.submitable = function( val ) {
			var len = Math.max( 0, this.opts.text_length - val.length );
			var can_submit = ps_observer.apply_filters(
				'peepso_postbox_can_submit',
				{
					hard: [],
					soft: [ len < this.opts.text_length && '' !== jQuery.trim( val ) ]
				},
				this
			);

			// Notice users upon leaving the page when postbox content is not empty.
			( ( { hard, soft } ) => {
				let showNotice = false;
				if ( hard.length ) {
					showNotice = hard.indexOf( false ) === -1;
				} else {
					showNotice = soft.indexOf( true ) !== -1;
				}

				if ( showNotice && ! this.beforeUnloadHandler ) {
					this.beforeUnloadHandler = () => {
						return true;
					};
					peepso.observer.addFilter( 'beforeunload', this.beforeUnloadHandler );
				} else if ( ! showNotice && this.beforeUnloadHandler ) {
					peepso.observer.removeFilter( 'beforeunload', this.beforeUnloadHandler );
					this.beforeUnloadHandler = null;
				}

				if ( showNotice ) {
					this.$cancel_button.show();
				} else {
					this.$cancel_button.hide();
				}
			} )( can_submit );

			if ( can_submit.hard.length ) {
				can_submit = can_submit.hard.indexOf( false ) > -1 ? false : true;
			} else can_submit = can_submit.soft.indexOf( true ) > -1 ? true : false;

			return can_submit;
		};

		this.addons_update = jQuery.proxy(
			_.debounce( function() {
				var list = ps_observer.apply_filters( 'peepso_postbox_addons_update', [] );
				if ( list && list.length ) {
					var placeholder = this.$textarea.attr( 'placeholder' );
					this.$textarea.data( 'placeholder', placeholder ).removeAttr( 'placeholder' );
					this.$addons.html( '&mdash; ' + list.join( ' and ' ) );
					this.$addons.show();
				} else {
					var placeholder = this.$textarea.data( 'placeholder' );
					this.$textarea.attr( 'placeholder', placeholder );
					this.$addons.hide().empty();
				}
			}, 10 ),
			this
		);

		this.update_beautifier = _.throttle( function() {
			_.defer(
				_.bind( function() {
					var $wrapper, html;

					if ( ! this.$beautifier ) {
						this.$textarea.addClass( 'ps-tagging-textarea' );
						$wrapper = this.$textarea.parent( '.ps-tagging-wrapper' );
						if ( ! $wrapper.length ) {
							this.$textarea.wrap( '<div class=ps-tagging-wrapper />' );
							$wrapper = this.$textarea.parent( '.ps-tagging-wrapper' );
						}
						this.$beautifier = $wrapper.children( '.ps-tagging-beautifier' );
						if ( ! this.$beautifier.length ) {
							this.$beautifier = $( '<div class=ps-tagging-beautifier />' );
							this.$beautifier.prependTo( $wrapper );
						}
						this.$textarea.focus();
					}

					// Disable WP Emoji
					( function( settings ) {
						if ( settings && settings.supports ) {
							settings.supports.everything = true;
						}
					} )( window._wpemojiSettings || {} );

					html = this.$textarea.val() || '';
					html = peepso.observer.applyFilters( 'peepso_postbox_beautifier', html, this.$textarea );
					html = html
						// Replace html tags not added by peepso.
						.replace( /<(?!\/?ps_)/g, '&lt;' )
						// Replace peepso tags.
						.replace( /<(\/?)ps_/g, '<$1' )
						// Replace newlines.
						.replace( /\r?\n/g, '<br />' );

					this.$beautifier.html( html );
				}, this )
			);
		}, 100 );

		this.init( options );

		return this;
	};
} )( jQuery );

// delcare class
function PsPostboxLegacy() {
	this.can_submit = false;
	this.found_url = false;
	this.show_preview = true;
	this.$postbox = null;
	this.$url_preview_container = jQuery( "<div class='url-preview-container'></div>" );
}

/**
 * Initializes Postbox
 */
PsPostboxLegacy.prototype.init = function() {
	var _self = this;

	ps_observer.add_filter(
		'peepso_postbox_can_submit',
		function( can_submit ) {
			if ( can_submit ) return can_submit;
			return _self.can_submit;
		},
		20,
		1
	);

	this.$activity_stream_recent = jQuery( '#ps-activitystream-recent' );
	this.$activity_stream = jQuery( '#ps-activitystream' );

	this.$postbox = jQuery( '#postbox-main' ).pspostbox( {
		postbox_req: function( req ) {
			req.show_preview = _self.show_preview ? '1' : '0';
			return req;
		},
		on_save: function( json ) {
			// Resets the postbox to the "Status" post
			jQuery( this.$posttabs )
				.find( "[data-tab='status']" )
				.trigger( 'click' );
			return _self.append_to_stream( json );
		}
	} );

	if ( undefined !== this.$postbox ) {
		this.$postbox.$textarea
			.on( 'blur', function( e ) {
				_self.check_url_preview();
			} )
			.on( 'keyup', function( e ) {
				if ( 32 === e.keyCode ) _self.check_url_preview();
			} );

		this.$postbox.on( 'postbox.post_saved postbox.post_cancel', function() {
			_self.found_url = false;
			_self.show_preview = true;
			_self.can_submit = false;
			_self.$url_preview_container.empty().remove();
		} );
	}

	return this.$postbox;
};

// Checks if there's a URL in the post and display a preview of it
PsPostboxLegacy.prototype.check_url_preview = function() {
	// Check if we're on the status tab
	if ( 'status' !== this.$postbox.$posttabs.current_tab().data( 'tab' ) ) return;
	// Let's do this process just once, even if all content is removed and a new URL is entered
	if ( this.found_url ) return;

	var _self = this;

	// https://jasontucker.blog/8945/what-is-the-longest-tld-you-can-get-for-a-domain-name
	var regex = /((https?:\/\/|www\.)([a-z0-9-]+\.)+[a-z]{2,24}(:\d+)?(\/.*)?)/gi,
		matches = this.$postbox.$textarea.val().match( regex ),
		url = matches && matches[ 0 ],
		allowNonSSLEmbed = +peepsodata.allow_non_ssl_embed;

	// Exit if no url found.
	if ( ! url ) {
		return;
	}

	// Disable embedding insecure content if setting is disabled.
	if ( ! url.match( /^https:\/\//i ) && ! allowNonSSLEmbed ) {
		return;
	}

	// Prevent multiple fetching.
	if ( this.fetch_url ) {
		return;
	}

	this.fetch_url = url;

	var $postboxcontainer = this.$postbox.$textarea.closest( '.ps-postbox-input' );

	jQuery( '.ps-postbox-loading', this.$postbox ).show();
	jQuery( '.ps-postbox-action', this.$postbox ).css( 'display', 'none' );
	// use POST to avoid conflicts in the URL
	var req = { url: url };
	peepso.postJson( 'postbox.get_url_preview', req, function( json ) {
		_self.fetch_url = false;

		jQuery( '.ps-postbox-loading', _self.$postbox ).hide();
		jQuery( '.ps-postbox-action', _self.$postbox ).css( 'display', 'flex' );
		// Show preview, attach the html
		if ( json.success ) {
			if ( jQuery( $postboxcontainer ).find( '.url-preview-container' ).length === 0 ) {
				// No container for the post mood is defined
				// fix for firefox's image loading hidden iframe issue
				if (
					/firefox/i.test( navigator.userAgent ) &&
					/iframe/i.test( json.data.html ) &&
					/secret=/.test( json.data.html )
				) {
					json.data.html = json.data.html.replace( /display\s*:\s*none/, '' );
				}
				// create the post mood container
				_self.$url_preview_container.html( json.data.html );
				jQuery( $postboxcontainer ).append( _self.$url_preview_container );
				_self.$url_preview_container.find( '.remove-preview' ).on( 'click', function( e ) {
					_self.show_preview = false;
					e.preventDefault();
					_self.$url_preview_container.empty().remove();
					_self.can_submit = false;
				} );
				// remove broken thumbnail
				_self.remove_broken_thumbnails();

				// Manually render Facebook embedded content.
				peepso.util.fbParseXFBML();
			}

			// Prevent calling this again
			_self.found_url = true;
			_self.can_submit = true;
			_self.$postbox.on_change();
		}
	} );
};

/**
 * Appends a newly added post's HTML to the activity stream
 * @param  {array} json The AJAX response from add_post
 */
PsPostboxLegacy.prototype.append_to_stream = function( json ) {
	// do not proceed if post_id is not found
	if ( ! ( json.data && +json.data.post_id ) ) {
		return;
	}

	if ( jQuery( '#ps-no-posts' ).length > 0 ) {
		// special case for stream/profile when no posts are showing
		jQuery( this.$activity_stream.css( 'display', 'block' ) );
		jQuery( '#ps-no-posts' ).remove();
	}
	// hook up the drop-down menu within the new post
	var post_id = json.data.post_id,
		html = peepso.observer.applyFilters( 'peepso_activity_content', json.data.html );

	this.$activity_stream_recent.show();

	jQuery( html )
		.hide()
		.prependTo( this.$activity_stream_recent )
		.fadeIn( 'slow', function() {
			jQuery( this )
				.find( '.comment-container' )
				.hide();
			jQuery( document ).trigger( 'ps_activitystream_append', [
				jQuery( '#peepso-wrap .ps-js-activity--' + post_id + ' .ps-js-dropdown-toggle' )
			] );
		} );

	ps_observer.apply_filters( 'peepso_posttabs_cancel-status' );
};

/**
 * Scan for broken thumbnails and remove them.
 */
PsPostboxLegacy.prototype.remove_broken_thumbnails = function() {
	jQuery( '.ps-media-thumbnail img' ).each( function() {
		var tester = new Image();
		var img = this;
		tester.onerror = function() {
			jQuery( img )
				.closest( '.ps-media-thumbnail' )
				.remove();
		};
		tester.src = img.src;
	} );
};

window.postbox = new PsPostboxLegacy();

jQuery( document ).ready( function() {
	postbox.init();
} );

/**
 * Workaround for IE11 placeholder support.
 */
jQuery( function() {
	jQuery.support.placeholder = false;
	var webkit_type = document.createElement( 'input' );
	if ( 'placeholder' in webkit_type ) jQuery.support.placeholder = true;

	if ( ! jQuery.support.placeholder ) {
		var active = document.activeElement;
		jQuery( 'textarea' )
			.focus( function() {
				if (
					jQuery( this ).attr( 'placeholder' ) &&
					jQuery( this ).attr( 'placeholder' ).length > 0 &&
					'' !== jQuery( this ).attr( 'placeholder' ) &&
					jQuery( this ).val() === jQuery( this ).attr( 'placeholder' )
				) {
					jQuery( this )
						.val( '' )
						.removeClass( 'hasPlaceholder' );
				}
			} )
			.blur( function() {
				if (
					jQuery( this ).attr( 'placeholder' ) &&
					jQuery( this ).attr( 'placeholder' ).length > 0 &&
					'' !== jQuery( this ).attr( 'placeholder' ) &&
					( '' === jQuery( this ).val() ||
						jQuery( this ).val() === jQuery( this ).attr( 'placeholder' ) )
				) {
					jQuery( this )
						.val( jQuery( this ).attr( 'placeholder' ) )
						.addClass( 'hasPlaceholder' );
				}
			} );

		jQuery( 'textarea' ).blur();
		jQuery( active ).focus();
		jQuery( 'form' ).submit( function() {
			jQuery( this )
				.find( '.hasPlaceholder' )
				.each( function() {
					jQuery( this ).val( '' );
				} );
		} );
	}
} );

// EOF
