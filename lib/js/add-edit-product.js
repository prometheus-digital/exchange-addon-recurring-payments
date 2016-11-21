jQuery( document ).ready( function ( $ ) {
	$( '#it-exchange-recurring-payment-settings' ).on( 'change', '#it-exchange-recurring-payments-enabled', function () {
		$( '#recurring-payment-options' ).toggleClass( 'hidden' );
	} );
	$( '#it-exchange-recurring-payment-settings' ).on( 'change', '#it-exchange-recurring-payments-auto-renew', function () {
		$( '#trial-period-settings' ).toggleClass( 'hidden' );
		$( "#max-occurrences-settings" ).toggleClass( 'hidden' );
	} );
	$( '#it-exchange-recurring-payment-settings' ).on( 'change', '#it-exchange-recurring-payments-trial-enabled', function () {
		$( '#trial-period-options' ).toggleClass( 'hidden' );
	} );

	var hierarchy = $( '#it-exchange-product-subscription-hierarchy' );

	function subscriptionHierarchyDuplicateCheck( product_id ) {

		var found = false;
		$( 'div.it-exchange-subscription-child-ids-list-div ul li' ).each( function () {
			if ( $( this ).data( 'child-id' ) == product_id ) {
				alert( 'Already a child of this subscription' );
				found = true;
			}
		} );
		if ( ! found ) {
			$( 'div.it-exchange-subscription-parent-ids-list-div ul li' ).each( function () {
				if ( $( this ).data( 'parent-id' ) == product_id ) {
					alert( 'Already a parent of this subscription' );
					found = true;
				}
			} );
		}
		return found;
	}


	hierarchy.on( 'click', '.it-exchange-subscription-hierarchy-add-child a', function ( event ) {

		event.preventDefault();

		var data = {
			'action'    : 'it-exchange-recurring-payments-addon-add-subscription-child',
			'product_id': $( '.it-exchange-subscription-child-id option:selected' ).val(),
			'child_ids' : $( 'input[name=it-exchange-subscription-child-ids\\[\\]]' ).serializeArray(),
			'post_id'   : $( 'input[name=post_ID]' ).val(),
		};

		if ( 0 !== data[ 'product_id' ].length ) {
			var found = subscriptionHierarchyDuplicateCheck( data[ 'product_id' ] );

			if ( ! found ) {
				$.post( ajaxurl, data, function ( response ) {
					$( 'ul li', response ).each( function () {
						child_id = $( this ).data( 'child-id' );
						$( 'div.it-exchange-subscription-parent-ids-list-div ul li' ).each( function () {
							if ( $( this ).data( 'parent-id' ) == child_id ) {
								alert( 'Already a parent of this subscription' );
								found = true;
								return;
							}
						} );
					} );

					if ( ! found )
						$( '.it-exchange-subscription-child-ids-list-div' ).html( response );
				} );
			}
		}
	} );

	hierarchy.on( 'click', '.it-exchange-subscription-hierarchy-add-parent a', function ( event ) {
		event.preventDefault();
		var data = {
			'action'    : 'it-exchange-recurring-payments-addon-add-subscription-parent',
			'product_id': $( '.it-exchange-subscription-parent-id option:selected' ).val(),
			'parent_ids': $( 'input[name=it-exchange-subscription-parent-ids\\[\\]]' ).serializeArray(),
			'post_id'   : $( 'input[name=post_ID]' ).val(),
		};

		if ( 0 !== data[ 'product_id' ].length ) {
			var found = subscriptionHierarchyDuplicateCheck( data[ 'product_id' ] );

			if ( ! found ) {
				$.post( ajaxurl, data, function ( response ) {
					$( '.it-exchange-subscription-parent-ids-list-div' ).html( response );
				} );
			}
		}
	} );

	hierarchy.on( 'click', '.it-exchange-subscription-addon-delete-subscription-child, .it-exchange-subscription-addon-delete-subscription-parent', function ( event ) {
		event.preventDefault();
		$( this ).closest( 'li' ).remove();
	} );
} );