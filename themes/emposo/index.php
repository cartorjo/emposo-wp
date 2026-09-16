<?php
/**
 * Fallback template.
 *
 * Every route has a more specific template; this exists because the Theme
 * Handbook requires index.php, and to make an unexpected query shape visible
 * rather than fatal.
 *
 * @package Emposo
 */

get_header();
get_template_part( 'parts/pages/404' );
get_footer();
