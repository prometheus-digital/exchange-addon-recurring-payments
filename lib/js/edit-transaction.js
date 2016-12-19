jQuery( document ).ready( function ( $ ) {

	var df = $( 'input[name=it_exchange_recurring-payment_date_picker_format]' ).val();
	var EXCHANGE_RP = window.EXCHANGE_RP || { user: 0 };

	$( '.rp-expires' ).datepicker( {
		dateFormat: df,
		prevText  : '',
		nextText  : '',
	} );

	$( "#recurring-payments-save" ).click( function ( e ) {
		e.preventDefault();

		var $button = $( this );
		$button.attr( 'disabled', true );
		var allInputs = $( '.recurring-options input, .recurring-options select' ).attr( 'disabled', true );

		var saved = {};
		var erred = {};

		function save( product ) {
			saved[product] = true;

			for ( var save in saved ) {
				if ( !save ) {
					return;
				}
			}

			$button.removeAttr( 'disabled' );
			allInputs.removeAttr( 'disabled' );

			wp.heartbeat.interval( 'fast', 6 );
		}

		function error( product, message ) {
			erred[product] = message;

			if ( !message in erred ) {
				alert( message );
			}
		}

		$( '.recurring-options' ).each( function () {
			var $container = $( this ), route = $container.data( 'route' ), product = $container.data( 'product' );

			saved[product] = false;

			var sub_id = $( '#rp-sub-id-' + product ).val();
			var status = $( '#rp-status-' + product ).val();
			var expiration = $( '#rp-expires-' + product ).val();
			expiration = new Date( expiration );
			expiration = expiration.toISOString();

			$.ajax( {
				method : 'PUT',
				url    : route,
				data   : {
					status       : status,
					expiry_date  : expiration,
					subscriber_id: sub_id
				},
				success: function () {
					save( product );
				},
				error  : function ( xhr ) {
					alert( ExchangeCommon.getErrorFromXhr( xhr ) );
				},
			} );
		} );

	} );

	$( "#pause-subscription" ).click( function ( e ) {
		e.preventDefault();

		$( ".transaction-actions" ).slideUp();
		$( '#subscription-pause-manager' ).slideDown();
	} );

	$( "#cancel-pause-subscription" ).click( function ( e ) {

		e.preventDefault();

		$( ".transaction-actions" ).slideDown();
		$( '#subscription-pause-manager' ).slideUp();
	} );

	$( '#confirm-pause-subscription' ).click( function ( e ) {
		e.preventDefault();

		var $this = $( this );
		$this.attr( 'disabled', true );

		$.ajax( {
			type   : 'POST',
			url    : $( "#pause-subscription" ).data( 'route' ),
			data   : {
				paused_by: EXCHANGE_RP.user,
			},
			success: function ( data ) {
				wp.heartbeat.interval( 'fast', 6 );
				$( '#rp-status-' + data.product ).val( data.status.slug );
				$this.removeAttr( 'disabled' );

				$( ".transaction-actions" ).slideDown();
				$( '#subscription-pause-manager' ).slideUp();
				$( "#pause-subscription" ).remove();
			},

			error: function ( xhr ) {
				alert( ExchangeCommon.getErrorFromXhr( xhr ) );

				$this.removeAttr( 'disabled' );
			}
		} );
	} );

	$( "#resume-subscription" ).click( function ( e ) {
		e.preventDefault();

		$( ".transaction-actions" ).slideUp();
		$( '#subscription-resume-manager' ).slideDown();
	} );

	$( "#cancel-resume-subscription" ).click( function ( e ) {

		e.preventDefault();

		$( ".transaction-actions" ).slideDown();
		$( '#subscription-resume-manager' ).slideUp();
	} );

	$( '#confirm-resume-subscription' ).click( function ( e ) {
		e.preventDefault();

		var $this = $( this );
		$this.attr( 'disabled', true );

		$.ajax( {
			type   : 'POST',
			url    : $( "#resume-subscription" ).data( 'route' ),
			data   : {
				paused_by: EXCHANGE_RP.user,
			},
			success: function ( data ) {
				wp.heartbeat.interval( 'fast', 6 );
				$( '#rp-status-' + data.product ).val( data.status.slug );
				$this.removeAttr( 'disabled' );

				$( ".transaction-actions" ).slideDown();
				$( '#subscription-resume-manager' ).slideUp();
				$( "#resume-subscription" ).remove();
			},

			error: function ( xhr ) {

				alert( ExchangeCommon.getErrorFromXhr( xhr ) );

				$this.removeAttr( 'disabled' );
			}
		} );
	} );

	$( "#cancel-subscription" ).click( function ( e ) {
		e.preventDefault();

		$( ".transaction-actions" ).slideUp();
		$( '#subscription-cancellation-manager' ).slideDown();
	} );

	$( "#cancel-cancel-subscription" ).click( function ( e ) {

		e.preventDefault();

		$( ".transaction-actions" ).slideDown();
		$( '#subscription-cancellation-manager' ).slideUp();
	} );

	$( '#confirm-cancel-subscription' ).click( function ( e ) {
		e.preventDefault();

		var $this = $( this );
		$this.attr( 'disabled', true );

		$.ajax( {
			type   : 'POST',
			url    : $( "#cancel-subscription" ).data( 'route' ),
			data   : {
				cancelled_by: EXCHANGE_RP.user,
				reason      : $( "#cancel-subscription-reason" ).val(),
			},
			success: function ( data ) {
				wp.heartbeat.interval( 'fast', 6 );
				$( '#rp-status-' + data.product ).val( data.status.slug );
				$this.removeAttr( 'disabled' );

				$( ".transaction-actions" ).slideDown();
				$( '#subscription-cancellation-manager' ).slideUp();
				$( "#cancel-subscription" ).remove();
			},

			error: function ( xhr ) {

				alert( ExchangeCommon.getErrorFromXhr( xhr ) );

				$this.removeAttr( 'disabled' );
			}
		} );
	} );
} );