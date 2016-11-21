(function ( $, api, common, config ) {
	"use strict";

	var subscriptions = {}, subscription;

	for ( var i = 0; i < config.subscriptions.length; i++ ) {
		subscription = config.subscriptions[i];

		if ( !subscription.payment_method || !subscription.payment_method.editable ) {
			continue;
		}

		subscriptions[subscription.id] = new api.Models.Subscription( subscription );
	}

	$( document ).ready( function () {
		app.start( { subscriptions: subscriptions } );
	} );

	var app = window.ITExchangeUpdateSubscriptionPaymentMethod = {

		Views            : {},
		subscriptionViews: {},

		start: function ( options ) {
			for ( var subID in options.subscriptions ) {

				if ( !options.subscriptions.hasOwnProperty( subID ) ) {
					continue;
				}

				var subscription = options.subscriptions[subID];
				var view = new app.Views.UpdatePaymentMethod( {
					model: subscription
				} );
				view.inject(
					'#it-exchange-update-subscription-payment-method-container-' + subscription.get( 'transaction' )
				);
			}
		}
	};

	app.Views.UpdatePaymentMethod = api.View.extend( {
		template: wp.template( 'it-exchange-update-subscription-payment-method' ),

		initialize: function () {
			this.views.add(
				'.it-exchange-current-subscription-payment-method-container',
				new app.Views.CurrentPaymentMethod( { model: this.model } )
			);

			this.views.add(
				'.it-exchange-update-subscription-payment-method-source-container',
				new app.Views.PaymentMethodSource( { model: this.model } )
			);
		},

		render: function () {
			this.$el.html( this.template() );
			this.views.render();
		}
	} );

	app.Views.CurrentPaymentMethod = api.View.extend( {
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

	app.Views.PaymentMethodSource = api.View.extend( {

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
					tokens.filter( { gateway: this.model.get( 'payment_method.method.slug' ) } );
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

					var tokenize;

					if ( api.canTokenize( gateway ) ) {
						tokenize = api.tokenize( gateway, 'card', tokenOrCard );
					} else {
						tokenize = tokenOrCard;
					}

					var token = this.model.customer().tokens().create( {
						gateway : gateway,
						tokenize: tokenize,
					}, {
						wait   : true,
						success: (function () {
							this.model.set( 'payment_method.token', token.id );
							this.doSave();
						}).bind( this )
					} );

					return;
				} else {
					this.model.set( 'payment_method.token', tokenOrCard );
				}
			}

			this.doSave();
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