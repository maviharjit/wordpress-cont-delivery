<?php
/** Enable W3 Total Cache */
define('WP_CACHE', true); // Added by W3 Total Cache

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
define('DB_NAME', 'savethepeasant');

/** MySQL database username */
define('DB_USER', 'stpuser');

/** MySQL database password */
define('DB_PASSWORD', 'Kukd00K@du');

/** MySQL hostname */
define('DB_HOST', 'localhost');

// hmavi
// define('FORCE_SSL_ADMIN', true);
define('WP_ALLOW_REPAIR', true);

/** Database Charset to use in creating database tables. */
define('DB_CHARSET', 'utf8');

/** The Database Collate type. Don't change this if in doubt. */
define('DB_COLLATE', '');

#define('WP_HOME','http://stage2.dayseas.com');
#define('WP_SITEURL','http://stage2.dayseas.com');
define('WP_HOME','http://www.savethepeasant.org');
define('WP_SITEURL','http://www.savethepeasant.org');

/**#@+
 * Authentication Unique Keys and Salts.
 *
 * Change these to different unique phrases!
 * You can generate these using the {@link https://api.wordpress.org/secret-key/1.1/salt/ WordPress.org secret-key service}
 * You can change these at any point in time to invalidate all existing cookies. This will force all users to have to log in again.
 *
 * @since 2.6.0
 */
define('AUTH_KEY',         '/)-f*Gj&#_ZSXIe76Ovlc3s?|~6p?#-@V#GPIcv-9}!=(Yf0TyU/|1SDOJU]A-_A');
define('SECURE_AUTH_KEY',  '6|Wp]2Tm8djhlMA?=KOy9]XPFh$}q%tVj=5N|Y?9(v/1V>kgy>h.`Yt0c*9dk+lM');
define('LOGGED_IN_KEY',    'Kn9!&Xh@BhFp0ckF3`mi`,/Zp7Q#K{q$ F{dra9f&i/oxWvIo**2hQuS[p84p}A0');
define('NONCE_KEY',        '=-ICV!l=I9n&>#a]S1du4Wy(n+$78L|5}-VI^DbL6gK#-A5`wf]1UpO}NZ~qJCoW');
define('AUTH_SALT',        'V*>&uR/oB-9xy~qGb$)nM(D_$&kkFTP>myxE7@mmJC>+hi)z_B+e&+nwi_Prhkmu');
define('SECURE_AUTH_SALT', 'Z4LiC@CNqp2@y*R2kQN#H#{P~g<_l7J1`|J:s||1.|Nb3/fG]3=>Z^?sSDGtWKV-');
define('LOGGED_IN_SALT',   '+Pum;h:oZl#/N;d7>A`u/7#;J[o]n%5,Wx*7GWDS9pP7{oKxds]!ZkhC0*uEW],X');
define('NONCE_SALT',       'N-$a1tGGgMF0CuYvJv~psII=Y4Ir_pzH_+|$>+Z{,|-rQ{G[x-FA1==7FD(Il^LM');

/**#@-*/

/**
 * WordPress Database Table prefix.
 *
 * You can have multiple installations in one database if you give each
 * a unique prefix. Only numbers, letters, and underscores please!
 */
$table_prefix  = 'wp_';

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
define('WP_DEBUG', false);

/* That's all, stop editing! Happy blogging. */

/** Absolute path to the WordPress directory. */
if ( !defined('ABSPATH') )
	define('ABSPATH', dirname(__FILE__) . '/');

/** Sets up WordPress vars and included files. */
require_once(ABSPATH . 'wp-settings.php');
