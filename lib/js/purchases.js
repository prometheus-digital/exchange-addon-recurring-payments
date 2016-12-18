(function ( $, api, common, config ) {
	"use strict";

	var subscriptions = {}, subscription;

	for ( var i = 0; i < config.subscriptions.length; i++ ) {
		subscription = config.subscriptions[i];
		subscriptions[subscription.id] = new api.Models.Subscription( subscription );
	}

	$( document ).ready( function () {
		updatePaymentMethod.start( { subscriptions: subscriptions } );
		renewSubscription.start( { subscriptions: subscriptions } );

		$( '.it-exchange-cancel-subscription-api' ).click( function ( e ) {

			e.preventDefault();

			var $this = $( this ), id = $this.data( 'id' ), subscription = subscriptions[id];

			if ( !subscription ) {
				return;
			}

			if ( !subscription.get( 'can_be_cancelled' ) ) {
				alert( config.i18n.cannotCancel );

				return;
			}

			if ( $this.data( 'processing' ) ) {
				return;
			}

			var cancelling = config.i18n.cancelling;

			$this.attr( 'disabled', true );
			$this.data( 'processing', true );

			var original_text = $this.text();
			$this.text( cancelling );

			var i = 0;
			setInterval( function () {
				i = ++i % 4;
				var arr = new Array( i + 1 );
				$this.text( cancelling + arr.join( '.' ) );
			}, 500 );

			subscription.cancel().done( function () {
				$this.replaceWith(
					$( '<span class="it-exchange-cancel-subscription-api-done"></span>' ).text( subscription.get( 'status.label' ) )
				);
			} ).fail( function () {
				$this.removeAttr( 'disabled' );
				$this.data( 'processing', false );
				$this.text( original_text );
			} );
		} );

		$( '.it-exchange-pause-subscription-payment' ).click( function ( e ) {

			e.preventDefault();

			var $this = $( this ), id = $this.data( 'id' ), subscription = subscriptions[id];

			if ( !subscription ) {
				return;
			}

			if ( !subscription.get( 'can_be_paused' ) ) {
				alert( config.i18n.cannotPause );

				return;
			}

			if ( $this.data( 'processing' ) ) {
				return;
			}

			var pausing = config.i18n.pausing;

			$this.attr( 'disabled', true );
			$this.data( 'processing', true );

			var original_text = $this.text();
			$this.text( pausing );

			var i = 0, interval = setInterval( function () {
				i = ++i % 4;
				var arr = new Array( i + 1 );
				$this.text( pausing + arr.join( '.' ) );
			}, 500 );

			subscription.pause().done( function () {
				$this.text( subscription.get( 'status.label' ) );
				clearInterval( interval );
			} ).fail( function () {
				$this.removeAttr( 'disabled' );
				$this.data( 'processing', false );
				$this.text( original_text );
				clearInterval( interval );
			} );
		} );

		$( '.it-exchange-resume-subscription-payment' ).click( function ( e ) {

			e.preventDefault();

			var $this = $( this ), id = $this.data( 'id' ), subscription = subscriptions[id];

			if ( !subscription ) {
				return;
			}

			if ( !subscription.get( 'can_be_resumed' ) ) {
				alert( config.i18n.cannotResume );

				return;
			}

			if ( $this.data( 'processing' ) ) {
				return;
			}

			var resuming = config.i18n.resuming;

			$this.attr( 'disabled', true );
			$this.data( 'processing', true );

			var original_text = $this.text();
			$this.text( resuming );

			var i = 0, interval = setInterval( function () {
				i = ++i % 4;
				var arr = new Array( i + 1 );
				$this.text( resuming + arr.join( '.' ) );
			}, 500 );

			subscription.pause().done( function () {
				$this.text( subscription.get( 'status.label' ) );
				clearInterval( interval );
			} ).fail( function () {
				$this.removeAttr( 'disabled' );
				$this.data( 'processing', false );
				$this.text( original_text );
				clearInterval( interval );
			} );
		} );
	} );

	var renewSubscription = window.ITExchangeRenewSubscription = {
		Views     : {},
		cartLoaded: false,

		start: function ( options ) {

			api.loadCart().done( function () {
				renewSubscription.cartLoaded = true;
			} );

			for ( var subID in options.subscriptions ) {

				if ( !options.subscriptions.hasOwnProperty( subID ) ) {
					continue;
				}

				var subscription = options.subscriptions[subID];

				if ( !subscription.get( 'can_be_manually_renewed' ) ) {
					continue;
				}

				var view = new renewSubscription.Views.Renew( {
					model: subscription
				} );
				view.inject(
					'#it-exchange-renew-subscription-' + subscription.get( 'transaction' ) + '-' + subscription.get( 'product' )
				);
			}
		}
	};

	renewSubscription.Views.Renew = api.View.extend( {

		template: wp.template( 'it-exchange-renew-subscription' ),

		events: {
			'click .it-exchange-renew-subscription'        : 'onRenewClicked',
			'click .it-exchange-renew-subscription--cancel': 'onCancelClicked',
		},

		checkout: null,

		onRenewClicked: function () {

			if ( renewSubscription.cartLoaded ) {
				this.renew();
			} else {
				var i = setInterval( (function () {
					if ( renewSubscription.cartLoaded ) {
						clearInterval( i );
						this.renew();
					}
				}).bind( this ), 50 );
			}
		},

		renew: function () {

			if ( this.checkout ) {
				this.checkout.$el.show();
				this.$( '.it-exchange-renew-subscription' ).hide();
				this.$( '.it-exchange-renew-subscription--cancel' ).show();

				return;
			}

			this.model.renew().done( (function ( cart ) {
				this.$( '.it-exchange-renew-subscription' ).hide();
				this.$( '.it-exchange-renew-subscription--cancel' ).show();

				this.checkout = new api.Views.Checkout( {
					model             : cart,
					showLineItemImages: false,
					showCouponEntry   : true,
				} );

				this.views.add( '.it-exchange-renew-subscription-checkout', this.checkout );
			}).bind( this ) );
		},

		onCancelClicked: function () {
			this.checkout.$el.hide();
			this.$( '.it-exchange-renew-subscription' ).show();
			this.$( '.it-exchange-renew-subscription--cancel' ).hide();
		},

		render: function () {

			var attr = {
				i18n: config.i18n
			};

			if ( this.model.get( 'auto_renewing' ) ) {
				attr.i18n.actionLabel = attr.i18n.reactivate;
			} else {
				attr.i18n.actionLabel = attr.i18n.renew;
			}

			this.$el.html( this.template( attr ) );
		}
	} );

	var updatePaymentMethod = window.ITExchangeUpdateSubscriptionPaymentMethod = {

		Views: {},

		start: function ( options ) {
			for ( var subID in options.subscriptions ) {

				if ( !options.subscriptions.hasOwnProperty( subID ) ) {
					continue;
				}

				var subscription = options.subscriptions[subID];

				if ( !subscription.get( 'payment_method.editable' ) ) {
					continue;
				}

				var view = new updatePaymentMethod.Views.UpdatePaymentMethod( {
					model: subscription
				} );
				view.inject(
					'#it-exchange-update-subscription-payment-method-container-' + subscription.get( 'transaction' )
				);
			}
		}
	};

	updatePaymentMethod.Views.UpdatePaymentMethod = api.View.extend( {
		template: wp.template( 'it-exchange-update-subscription-payment-method' ),

		initialize: function () {
			this.views.add(
				'.it-exchange-current-subscription-payment-method-container',
				new updatePaymentMethod.Views.CurrentPaymentMethod( { model: this.model } )
			);

			this.views.add(
				'.it-exchange-update-subscription-payment-method-source-container',
				new updatePaymentMethod.Views.PaymentMethodSource( { model: this.model } )
			);
		},

		render: function () {
			this.$el.html( this.template() );
			this.views.render();
		}
	} );

	updatePaymentMethod.Views.CurrentPaymentMethod = api.View.extend( {
		template: wp.template( 'it-exchange-current-subscription-payment-method' ),

		initialize: function () {
			this.listenTo( this.model, 'change:payment_method.source.identifier', this.render );
		},

		render: function () {

			var attr = this.model.get( 'payment_method' );
			attr.i18n = config.i18n;

			this.$el.html( this.template( attr ) );
		}
	} );

	updatePaymentMethod.Views.PaymentMethodSource = api.View.extend( {

		template: wp.template( 'it-exchange-update-subscription-payment-method-source' ),
		events  : {
			'click .it-exchange-update-subscription-payment-method-source-trigger': 'onTrigger',
			'click .it-exchange-update-subscription-payment-method-source-save'   : 'onSave',
			'click .it-exchange-update-subscription-payment-method-source-cancel' : 'onCancel',
		},

		visualCC     : null,
		tokenSelector: null,

		initialize: function () {

			if ( this.model.get( 'payment_method.card' ) ) {
				this.visualCC = new api.Views.VisualCC();
				this.views.add( '.it-exchange-update-subscription-payment-method-source-form-container', this.visualCC );
			} else if ( this.model.get( 'payment_method.token' ) ) {
				var tokens = this.model.customer().tokens();

				if ( !tokens.models.length ) {
					// figure out how to share tokens
					tokens.doFilter( { gateway: this.model.get( 'payment_method.method.slug' ) } );
				}

				this.tokenSelector = new api.Views.PaymentTokenSelector( {
					collection: tokens
				} );
				this.views.add( '.it-exchange-update-subscription-payment-method-source-form-container', this.tokenSelector );
			}
		},

		render: function () {
			this.$el.html( this.template( { i18n: config.i18n } ) );
			this.views.render();
		},

		onTrigger: function ( e ) {

			this.$( e.target ).hide();
			this.$( '.it-exchange-update-subscription-payment-method-source-form-container' ).show();
			this.$( '.it-exchange-update-subscription-payment-method-source-save' ).show();
			this.$( '.it-exchange-update-subscription-payment-method-source-cancel' ).show();
		},

		onCancel: function () {

			this.$( '.it-exchange-update-subscription-payment-method-source-form-container' ).hide();
			this.$( '.it-exchange-update-subscription-payment-method-source-save' ).hide();
			this.$( '.it-exchange-update-subscription-payment-method-source-cancel' ).hide();
			this.$( '.it-exchange-update-subscription-payment-method-source-trigger' ).show();
		},

		onSave: function ( e ) {

			var $target = this.$( e.target );

			$target.attr( 'disabled', true );

			if ( this.visualCC ) {
				this.model.set( 'payment_method.card', this.visualCC.getCard() );
			} else if ( this.tokenSelector ) {
				var tokenOrCard = this.tokenSelector.selected();
				var gateway = this.model.get( 'payment_method.method' );

				if ( _.isObject( tokenOrCard ) ) {

					if ( api.canTokenize( gateway ) ) {
						api.tokenize( gateway, 'card', tokenOrCard ).done( (function ( tokenize ) {
							this.createToken( tokenize );
						}).bind( this ) );
					} else {
						this.createToken( tokenOrCard );
					}

				} else {
					this.model.set( 'payment_method.token', tokenOrCard );
				}
			}

			this.doSave();
		},

		createToken: function ( tokenize ) {
			this.model.customer().tokens().create( {
				gateway : this.model.get( 'payment_method.method' ),
				tokenize: tokenize,
			}, {
				wait   : true,
				success: (function ( token ) {
					this.model.set( 'payment_method.token', token.id );
					this.doSave();
				}).bind( this )
			} );
		},

		doSave: function () {

			this.model.save().done( (function () {

				this.$( '.it-exchange-update-subscription-payment-method-source-form-container' ).hide();
				this.$( '.it-exchange-update-subscription-payment-method-source-save' ).hide();
				this.$( '.it-exchange-update-subscription-payment-method-source-cancel' ).hide();
				this.$( '.it-exchange-update-subscription-payment-method-source-trigger' ).show();

			}).bind( this ) ).fail( (function ( xhr ) {

				this.$( '.it-exchange-update-subscription-payment-method-source-save' ).removeAttr( 'disabled' );

				var data = $.parseJSON( xhr.responseText );

				alert( data.message || 'Error' );
			}).bind( this ) );
		}
	} );

})( jQuery, window.ITExchangeAPI, window.ExchangeCommon, window.ITExchangeRecurringPayments );