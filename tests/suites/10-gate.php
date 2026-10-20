<?php
/**
 * Who opens the space.
 *
 * @package CinqWpCentral
 */

declare( strict_types = 1 );

use CinqWpCentral\Access\Gate;

section( 'Administrators by default, nobody else' );

check( 'an administrator opens it', Gate::can( new WP_User( 1, 'lea@boutique.fr', 'Léa', 'lea', array( 'administrator' ) ) ) );
check( 'an editor does not, by default', ! Gate::can( new WP_User( 2, 'ed@boutique.fr', 'Ed', 'ed', array( 'editor' ) ) ) );
check( 'a customer does not', ! Gate::can( new WP_User( 3, 'client@gmail.com', 'C', 'c', array( 'customer' ) ) ) );
check( 'a logged-out visitor does not', ! Gate::can( new WP_User() ) );
check( 'an account without e-mail does not — Central has no one to name', ! Gate::can( new WP_User( 4, '', 'X', 'x', array( 'administrator' ) ) ) );

section( 'The settings widen it' );

update_option( Gate::OPTION, array( 'administrator', 'editor' ) );
check( 'an editor opens it once ticked', Gate::can( new WP_User( 2, 'ed@boutique.fr', 'Ed', 'ed', array( 'editor' ) ) ) );

update_option( Gate::OPTION, array() );
check( 'an empty choice falls back to administrators', array( 'administrator' ) === Gate::roles() );

section( 'A filter has the last word' );

add_filter( 'cinq_wp_central_user_can', static fn ( bool $allowed, WP_User $u ) => $allowed && 'bloque@boutique.fr' !== $u->user_email, 10, 2 );
check( 'a filter can refuse an administrator', ! Gate::can( new WP_User( 5, 'bloque@boutique.fr', 'B', 'b', array( 'administrator' ) ) ) );
