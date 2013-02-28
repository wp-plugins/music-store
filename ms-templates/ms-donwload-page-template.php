<?php
get_header(); 
global $download_links_str;
?>

	<div id="primary" class="site-content">
		<div id="content" role="main">
			<div class="entry-header">
				<h1 class="entry-title"><?PHP _e('Download the purchased products', MS_TEXT_DOMAIN);	?></h1>
			</div>
			<h2>
			<?php
				_e('Products List:', MS_TEXT_DOMAIN);
			?>
			</h2>
			<div class="entry-content">
				<?php print $download_links_str; ?>
			</div>
		</div><!-- #content -->
	</div><!-- #primary -->

<?php get_footer(); ?>