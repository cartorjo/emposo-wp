<?php
/**
 * Fallback template.
 *
 * Required by the Theme Handbook, and used until the per-route templates land.
 * A shim, like every root template: markup lives in parts/ so Tailwind's
 *
 * @source list can stay a closed set. tools/check-sources.mjs enforces that.
 *
 * @package Emposo
 */

get_header();
get_template_part( 'parts/fallback' );
get_footer();
