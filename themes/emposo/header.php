<?php
/**
 * Document head, site header and the opening <main>.
 *
 * A shim by design: all markup lives in parts/, so Tailwind's @source list can
 * stay a short closed set pointed at authoring sources. check-sources.mjs fails
 * the build if any root template gains a class attribute.
 *
 * The skip link and <header> render OUTSIDE <main> — a banner landmark must not
 * sit inside main, and the skip link has to actually skip the navigation.
 *
 * @package Emposo
 */

/*
 * Emitted RAW, not trimmed. assemble.mjs concatenates head.html and
 * header.html verbatim and trims only the <!-- partial: --> includes and page
 * bodies, so each of these files' own trailing newline is part of the document.
 */
get_template_part( 'parts/head' );
get_template_part( 'parts/header' );
?>
<main id="main" tabindex="-1">
