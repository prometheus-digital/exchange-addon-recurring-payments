<?php

if ( is_admin() ) {
	// Admin only
} else {
	// Frontend only
	include( 'theme.php' );
}

// Transaction Add-ons
include( 'transactions.php' );