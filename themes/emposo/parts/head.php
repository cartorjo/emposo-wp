<?php
/**
 * Everything from <!DOCTYPE> to <body>.
 *
 * Hand-authored rather than generated, because position matters: wp_head() is
 * printed LAST (where the static build's {{SCRIPTS}} token sat), so anything
 * that must come earlier — charset, viewport, title, description, robots,
 * favicon, font preloads — has to be literal here.
 *
 * In particular the font preloads must precede the render-blocking CSS. The
 * wp_preload_resources filter prints at wp_head priority 1, which in this
 * layout lands AFTER the stylesheets and would delay font discovery enough to
 * threaten the mobile LCP bar. Same reason add_theme_support( 'title-tag' ) is
 * not used: it would move <title> below the stylesheets.
 *
 * Phase 5 fills this in.
 *
 * @package Emposo
 */

?>
<!DOCTYPE html>
<html lang="de">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>
<?php
/*
 * wp_body_open() fires AFTER the skip link, never before: anything injected
 * ahead of <a class="skip-link"> would become the first focusable element and
 * cost the accessibility score.
 */
?>
