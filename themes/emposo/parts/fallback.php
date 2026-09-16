<?php
/**
 * Fallback body.
 *
 * Renders the queried object's title rather than 404 content, so a 200 response
 * never displays "page not found" — a misleading state that reads as a routing
 * bug when routing is in fact correct.
 *
 * @package Emposo
 */

?>
<section class="page-hero" aria-labelledby="fallback-title">
	<div class="gutter">
		<div class="container">
			<h1 id="fallback-title" class="page-display"><?php echo esc_html( wp_get_document_title() ); ?></h1>
			<?php if ( \Emposo\Core\Environment\is_development() ) : ?>
				<p class="page-hero__intro">
					<?php
					printf(
						/* translators: %s: template file name. */
						esc_html__( 'Fallback template (%s). Route templates land in the template port.', 'emposo' ),
						'index.php'
					);
					?>
				</p>
			<?php endif; ?>
		</div>
	</div>
</section>
