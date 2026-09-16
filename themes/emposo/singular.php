<?php
/**
 * Singular template.
 *
 * A shim, like every root template: the route contract says which body belongs
 * to this URL, so all of them dispatch through emposo_the_body() rather than
 * each re-deriving it. Markup lives in parts/ so Tailwind's @source list stays
 * a closed set; tools/check-sources.mjs fails the build if a root template
 * gains a class attribute.
 *
 * @package Emposo
 */

get_header();
emposo_the_body();
get_footer();
