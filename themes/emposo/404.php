<?php
/**
 * 404 template.
 *
 * @package Emposo
 */

get_header();
// The 404 route of the request's language (English under /en/).
get_template_part( 'en' === emposo_lang() ? 'parts/pages/en/404' : 'parts/pages/404' );
get_footer();
