<?php
/**
 * Fallback body.
 *
 * Renders the queried object's own title and content rather than 404 content,
 * so published pages outside the route contract — the legal pages — stay
 * readable instead of presenting as "page not found" on a 200 response.
 *
 * Uses only classes that exist in the committed site.css (the same
 * page-section/legal-copy shell as parts/pages/cookies.php), because the CSS
 * build is not runnable from a fresh clone.
 *
 * @package Emposo
 */

?>
<section class="page-section" aria-labelledby="fallback-title">
	<div class="gutter">
		<div class="container legal-copy">
			<h1 id="fallback-title" class="page-title"><?php echo esc_html( get_the_title() ); ?></h1>
			<?php
			if ( is_singular() ) {
				while ( have_posts() ) {
					the_post();
					the_content();
				}
			}
			?>
		</div>
	</div>
</section>
