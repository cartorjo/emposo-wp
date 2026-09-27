<?php
/**
 * Case study.
 *
 * Twenty-three of the 35 German routes; their English twins fall through to
 * single.php, the same shim. The body comes from the project renderer via the route
 * contract, like every other route.
 *
 * @package Emposo
 */

get_header();
emposo_the_body();
get_footer();
