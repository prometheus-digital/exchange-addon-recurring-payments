(function ( ExchangeCommon, $, _, api, Config ) {
	"use strict";

	api.Models.Subscription = api.Model.extend( {
		urlRoot     : ExchangeCommon.getRestUrl( 'subscriptions', {}, false ),
		_customer   : 'ITExchangeAPI.Models.Customer',
		_beneficiary: 'ITExchangeAPI.Models.Customer',
		_transaction: 'ITExchangeAPI.Models.Transaction',

		/**
		 * Get the customer who is responsible for paying this subscription.
		 *
		 * @since 2.0.0
		 *
		 * @returns {api.Models.Customer}
		 */
		customer: function () {

			if ( !this.get( 'customer' ) ) {
				return null;
			}

			if ( this._customer instanceof api.Models.Customer == false ) {
				this._customer = new api.Models.Customer( {
					id: this.get( 'customer' )
				} );
			}

			return this._customer;
		},

		/**
		 * Get the user who is receiving the benefits of this subscription.
		 *
		 * @since 2.0.0
		 *
		 * @returns {api.Models.Customer}
		 */
		beneficiary: function () {

			if ( !this.get( 'beneficiary' ) ) {
				return null;
			}

			if ( this._beneficiary instanceof api.Models.Customer == false ) {
				this._beneficiary = new api.Models.Customer( {
					id: this.get( 'beneficiary' )
				} );
			}

			return this._beneficiary;
		},

		/**
		 * Get the transaction used to purchase this subscription.
		 *
		 * @since 2.0.0
		 *
		 * @returns {api.Models.Transaction}
		 */
		transaction: function () {

			if ( !this.get( 'transaction' ) ) {
				return null;
			}

			if ( this._transaction instanceof api.Models.Transaction == false ) {
				this._transaction = new api.Models.Transaction( {
					id: this.get( 'transaction' )
				} );
			}

			return this._transaction;
		},

		/**
		 * Pause this subscription.
		 *
		 * @param [options] {*}
		 * @param [options.paused_by] {int|api.Models.Customer} Specify who paused the subscription.
		 *
		 * @returns {Promise}
		 */
		pause: function ( options ) {
			options = options || {};

			var postData = {};

			if ( options.paused_by ) {

				if ( options.paused_by instanceof api.Models.Customer ) {
					postData.paused_by = options.paused_by.id;
				} else {
					postData.paused_by = options.paused_by;
				}
			}

			var deferred = $.Deferred();

			$.ajax( {
				method    : 'POST',
				url       : _.result( this, 'url' ) + '/pause',
				data      : postData,
				beforeSend: function ( xhr ) {
					xhr.setRequestHeader( 'X-WP-Nonce', ExchangeCommon.config.restNonce );
				},

				success: (function ( data ) {

					this.set( data );

					deferred.resolve( this );
				}).bind( this ),

				error: function ( xhr ) {
					alert( ExchangeCommon.getErrorFromXhr( xhr ) );
				}
			} );

			return deferred.promise();
		},

		/**
		 * Cancel this subscription.
		 *
		 * @param [options] {*}
		 * @param [options.cancelled_by] {int|api.Models.Customer} Specify who cancelled the subscription.
		 * @param [options.reason] {string} The reason the subscription was cancelled.
		 *
		 * @returns {Promise}
		 */
		cancel: function ( options ) {
			options = options || {};

			var postData = {};

			if ( options.reason ) {
				postData.reason = options.reason;
			}

			if ( options.cancelled_by ) {

				if ( options.cancelled_by instanceof api.Models.Customer ) {
					postData.cancelled_by = options.cancelled_by.id;
				} else {
					postData.cancelled_by = options.cancelled_by;
				}
			}

			var deferred = $.Deferred();

			$.ajax( {
				method    : 'POST',
				url       : _.result( this, 'url' ) + '/cancel',
				data      : postData,
				beforeSend: function ( xhr ) {
					xhr.setRequestHeader( 'X-WP-Nonce', ExchangeCommon.config.restNonce );
				},

				success: (function ( data ) {

					this.set( data );

					deferred.resolve( this );
				}).bind( this ),

				error: function ( xhr ) {
					alert( ExchangeCommon.getErrorFromXhr( xhr ) );
				}
			} );

			return deferred.promise();
		},

		/**
		 * Resume this subscription.
		 *
		 * @param [options] {*}
		 * @param [options.resumed_by] {int|api.Models.Customer} Specify who resumed the subscription.
		 *
		 * @returns {Promise}
		 */
		resume: function ( options ) {
			options = options || {};

			var postData = {};

			if ( options.resumed_by ) {

				if ( options.resumed_by instanceof api.Models.Customer ) {
					postData.resumed_by = options.resumed_by.id;
				} else {
					postData.resumed_by = options.resumed_by;
				}
			}

			var deferred = $.Deferred();

			$.ajax( {
				method    : 'POST',
				url       : _.result( this, 'url' ) + '/resume',
				data      : postData,
				beforeSend: function ( xhr ) {
					xhr.setRequestHeader( 'X-WP-Nonce', ExchangeCommon.config.restNonce );
				},

				success: (function ( data ) {

					this.set( data );

					deferred.resolve( this );
				}).bind( this ),

				error: function ( xhr ) {
					alert( ExchangeCommon.getErrorFromXhr( xhr ) );
				}
			} );

			return deferred.promise();
		},

		/**
		 * Manually renew a non-auto-renewing subscription.
		 *
		 * @param [cart] {api.Models.Cart} Optional. If empty, will create a cart transparently.
		 * @param [options] {*}
		 *
		 * @returns {Promise} Promise that resolves to a cart with the subscription product item added.
		 */
		renew: function ( cart, options ) {

			if ( !api.Models.Cart ) {
				throw new Error( 'Cart API not loaded.' );
			}

			var data = {};
			options = options || {};

			if ( cart ) {
				data.cart_id = cart.id;
			}

			var deferred = $.Deferred();

			$.ajax( {
				method    : 'POST',
				url       : _.result( this, 'url' ) + '/renew',
				data      : data,
				beforeSend: function ( xhr ) {
					xhr.setRequestHeader( 'X-WP-Nonce', ExchangeCommon.config.restNonce );
				},

				success: function ( cart_item ) {

					if ( cart ) {
						cart.products().add( cart_item );
						cart.allItems().add( cart_item );
						cart.fetch( options ); // Update total lines

						deferred.resolve( cart );
					} else {

						var links = cart_item._links;
						var cartLink = links['cart'][0]['href'];
						var cartId = cartLink.substr( cartLink.lastIndexOf( '/' ) + 1 );

						cart = new api.Models.Cart( {
							id: cartId,
						} );

						var success = options.success;
						options.success = function ( model ) {

							var r;

							if ( success ) {
								r = success.apily( options.context, arguments );
							}

							deferred.resolve( model );

							return r;
						};
						cart.fetch( options );
					}
				},

				error: function ( xhr ) {
					alert( ExchangeCommon.getErrorFromXhr( xhr ) );
				}
			} );

			return deferred.promise();
		},

		/**
		 * Get all available upgrades.
		 *
		 * @since 2.0.0
		 *
		 * @returns {api.Collections.ProrateOffers}
		 */
		upgrades: function () {

			if ( !this._upgrades ) {
				this._upgrades = new api.Collections.ProrateOffers( [], {
					type  : 'upgrade',
					parent: this,
				} );
			}

			return this._upgrades;
		},

		/**
		 * Get all available downgrades.
		 *
		 * @since 2.0.0
		 *
		 * @returns {api.Collections.ProrateOffers}
		 */
		downgrades: function () {

			if ( !this._downgrades ) {
				this._downgrades = new api.Collections.ProrateOffers( [], {
					type  : 'downgrade',
					parent: this,
				} );
			}

			return this._downgrades;
		},
	} );

	api.Models.ProrateOffer = api.Model.extend( {
		idAttribute: 'product',
		parentModel: null,

		/**
		 * Accept this prorate offer.
		 *
		 * @since 2.0.0
		 *
		 * @param {api.Models.Cart} cart
		 *
		 * @returns {*} A promise that resolves to the cart item.
		 */
		accept: function ( cart ) {

			var url = _.result( this.collection, 'url' ),
				deferred = $.Deferred();

			if ( !url ) {
				return deferred.promise();
			}

			var data = { product: this.id, cart_id: cart.id };

			$.ajax( {
				method     : 'POST',
				url        : url,
				data       : JSON.stringify( data ),
				contentType: 'application/json',

				beforeSend: function ( xhr ) {
					xhr.setRequestHeader( 'X-WP-Nonce', ExchangeCommon.config.restNonce );
				},

				success: function ( cart_item ) {

					var model = cart.products().get( cart_item.id );

					if ( !model ) {
						model = new api.Models.ProductCartItem( cart_item );
						cart.products().add( model );
						cart.allItems().add( model );
						cart.fetch();
					}

					deferred.resolve( model );
				},

				error: function ( xhr ) {
					alert( ExchangeCommon.getErrorFromXhr( xhr ) );
				}
			} );

			return deferred.promise();
		},
	} );

	api.Collections.ProrateOffers = api.Collection.extend( {
		model   : api.Models.ProrateOffer,
		type    : '',
		schemaID: 'prorate-request',

		initialize: function ( models, opts ) {
			opts = opts || {};

			if ( !opts.type ) {
				throw new Error( 'type option must be available for ProrateOffers.' );
			}

			this.type = opts.type;
			this.route = opts.type + 's';

			api.Collection.prototype.initialize.apply( this, arguments );
		}
	} );

	/**
	 * Get all available upgrades.
	 *
	 * @since 2.0.0
	 *
	 * @returns {api.Collections.ProrateOffers}
	 */
	api.Models.Membership.prototype.upgrades = function () {

		if ( !this._upgrades ) {
			this._upgrades = new api.Collections.ProrateOffers( [], {
				type  : 'upgrade',
				parent: this,
			} );
		}

		return this._upgrades;
	};

	/**
	 * Get all available downgrades.
	 *
	 * @since 2.0.0
	 *
	 * @returns {api.Collections.ProrateOffers}
	 */
	api.Models.Membership.prototype.downgrades = function () {

		if ( !this._downgrades ) {
			this._downgrades = new api.Collections.ProrateOffers( [], {
				type  : 'downgrade',
				parent: this,
			} );
		}

		return this._downgrades;
	};

})( window.ExchangeCommon, jQuery, window._, window.ITExchangeAPI, window.ITExchangeRESTConfig );