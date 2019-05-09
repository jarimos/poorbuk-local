<?php
/**
 * The base configuration for WordPress
 *
 * The wp-config.php creation script uses this file during the
 * installation. You don't have to use the web site, you can
 * copy this file to "wp-config.php" and fill in the values.
 *
 * This file contains the following configurations:
 *
 * * MySQL settings
 * * Secret keys
 * * Database table prefix
 * * ABSPATH
 *
 * @link https://codex.wordpress.org/Editing_wp-config.php
 *
 * @package WordPress
 */

// ** MySQL settings - You can get this info from your web host ** //
/** The name of the database for WordPress */
define( 'DB_NAME', 'poorbuk-local' );

/** MySQL database username */
define( 'DB_USER', 'jarimos' );

/** MySQL database password */
define( 'DB_PASSWORD', 'sarah' );

/** MySQL hostname */
define( 'DB_HOST', 'localhost' );

/** Database Charset to use in creating database tables. */
define( 'DB_CHARSET', 'utf8mb4' );

/** The Database Collate type. Don't change this if in doubt. */
define( 'DB_COLLATE', '' );

/**#@+
 * Authentication Unique Keys and Salts.
 *
 * Change these to different unique phrases!
 * You can generate these using the {@link https://api.wordpress.org/secret-key/1.1/salt/ WordPress.org secret-key service}
 * You can change these at any point in time to invalidate all existing cookies. This will force all users to have to log in again.
 *
 * @since 2.6.0
 */
define( 'AUTH_KEY',         '!Pwm~C}(O~JyQS`z7DP;mMD&2sD@Olwr&NgU*Mru3/)t14v|F]u!y[B*DtH. %WB' );
define( 'SECURE_AUTH_KEY',  '#v|7P+=JN&tv+P>g.4dGz5j,qVXR9l@-2hUTPrI5U] FtX=rc@L|p/U@fr7[ PY0' );
define( 'LOGGED_IN_KEY',    '@Hs:Cp:L0(3=GK2.gRyl!tH(dKIp6 Gbzqfy7zQn7_D*9>Coug*Iv.sQvm!sHey.' );
define( 'NONCE_KEY',        'SFMIEq&UnfiWQFhkj~]7peBR?d |kfx!2>TqKq.eOkc,Z*>Jz0~Yj0zi;*}Yy3Nf' );
define( 'AUTH_SALT',        'S@94>0Rpk>#-Y%X`$8X)m_cBt*}bKUgn+ mlsyanAa`g_x6{4m4kY,Qb78HO64gX' );
define( 'SECURE_AUTH_SALT', '@V=-ZTV;.%B.J(!*(SMv?g(S)|`Y}5R21QR`~y8eui@`|tH4Px (W6!Z5#@lBSoX' );
define( 'LOGGED_IN_SALT',   '6 =({-2,eIz)}y$Z(Tq(Glvm6Y`!obiouLpPjRO p9peL>D4!wD@i)5PyNK6#DnU' );
define( 'NONCE_SALT',       ')*~y{V)fc*29[Y={Jb$xT3s1i`{ ngTwA+-cU$CFdZ1H}r-hG4rSh(o!`I,O.jZ0' );

/**#@-*/

/**
 * WordPress Database Table prefix.
 *
 * You can have multiple installations in one database if you give each
 * a unique prefix. Only numbers, letters, and underscores please!
 */
$table_prefix = 'wp_';

/**
 * For developers: WordPress debugging mode.
 *
 * Change this to true to enable the display of notices during development.
 * It is strongly recommended that plugin and theme developers use WP_DEBUG
 * in their development environments.
 *
 * For information on other constants that can be used for debugging,
 * visit the Codex.
 *
 * @link https://codex.wordpress.org/Debugging_in_WordPress
 */
define( 'WP_DEBUG', true );

/* That's all, stop editing! Happy publishing. */

/** Absolute path to the WordPress directory. */
if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', dirname( __FILE__ ) . '/' );
}

/** Sets up WordPress vars and included files. */
require_once( ABSPATH . 'wp-settings.php' );
