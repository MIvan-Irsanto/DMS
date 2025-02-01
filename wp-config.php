<?php
/**
 * The base configuration for WordPress
 *
 * The wp-config.php creation script uses this file during the installation.
 * You don't have to use the web site, you can copy this file to "wp-config.php"
 * and fill in the values.
 *
 * This file contains the following configurations:
 *
 * * Database settings
 * * Secret keys
 * * Database table prefix
 * * ABSPATH
 *
 * @link https://wordpress.org/documentation/article/editing-wp-config-php/
 *
 * @package WordPress
 */

// ** Database settings - You can get this info from your web host ** //
/** The name of the database for WordPress */
define( 'DB_NAME', 'dms_ihu' );

/** Database username */
define( 'DB_USER', 'root' );

/** Database password */
define( 'DB_PASSWORD', '' );

/** Database hostname */
define( 'DB_HOST', 'localhost' );

/** Database charset to use in creating database tables. */
define( 'DB_CHARSET', 'utf8mb4' );

/** The database collate type. Don't change this if in doubt. */
define( 'DB_COLLATE', '' );

/**#@+
 * Authentication unique keys and salts.
 *
 * Change these to different unique phrases! You can generate these using
 * the {@link https://api.wordpress.org/secret-key/1.1/salt/ WordPress.org secret-key service}.
 *
 * You can change these at any point in time to invalidate all existing cookies.
 * This will force all users to have to log in again.
 *
 * @since 2.6.0
 */
define( 'AUTH_KEY',         '/!Mh^npWWxLqW=(3RS@$51K2Ue5Um9M#z63!qSC6^+J59VY0?`s)Zs}`==HR?5DZ' );
define( 'SECURE_AUTH_KEY',  'VHu:m7nRPZIx>!0JB|mVk?@: JvAJ>wREJ)kO37+M{aKZxBPeuf@V%15e-@vPc~j' );
define( 'LOGGED_IN_KEY',    '0vd;H%mta^F0vSdsz^x+KHXOnRNwxw4sG7?8?^b*_SpNXKI|[cJiJRL:@P`:=|q=' );
define( 'NONCE_KEY',        '9vN!SVQdBX{,`jnsYkj.wt1j8#|NEmTk9>sly>hihjY<[CZr,?!-TXCB-V4+R?/O' );
define( 'AUTH_SALT',        '}wL3$yScgsp@a/CVj,ss_`8 %:T5;-)!X$lZI+>w0:#Omsma:;[wGr,</3 -#@`7' );
define( 'SECURE_AUTH_SALT', '>S#QsakmvF @qI|8D[h#+90YIj?r]C?Uay+HVTs.eXjG*meRcIE>z_}1OUi!.xTR' );
define( 'LOGGED_IN_SALT',   '@J_J0Ib~$V1|&^mr;_7n`CyIQ?}e B^iOa/LX3?KOh,OLODgFnxhX/4Hd_7<:DkX' );
define( 'NONCE_SALT',       'v9,(E^f@KNF]he@!`N:1:{D_]2v@U0FK;B6AK!ctnvsdnDqV^7Be|9k-w9p8s>?l' );

/**#@-*/

/**
 * WordPress database table prefix.
 *
 * You can have multiple installations in one database if you give each
 * a unique prefix. Only numbers, letters, and underscores please!
 */
$table_prefix = 'dms_';

/**
 * For developers: WordPress debugging mode.
 *
 * Change this to true to enable the display of notices during development.
 * It is strongly recommended that plugin and theme developers use WP_DEBUG
 * in their development environments.
 *
 * For information on other constants that can be used for debugging,
 * visit the documentation.
 *
 * @link https://wordpress.org/documentation/article/debugging-in-wordpress/
 */
define( 'WP_DEBUG', false );

/* Add any custom values between this line and the "stop editing" line. */



/* That's all, stop editing! Happy publishing. */

/** Absolute path to the WordPress directory. */
if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', __DIR__ . '/' );
}

/** Sets up WordPress vars and included files. */
require_once ABSPATH . 'wp-settings.php';
