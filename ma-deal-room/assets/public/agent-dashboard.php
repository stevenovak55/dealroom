<?php
/**
 * Template Name: MA Deal Room Agent Dashboard
 * Description: Front-end dashboard for agents to manage transactions, tasks, and notifications.
 * Template Post Type: page
 *
 * @package MADealRoom
 * @since 1.1.0
 */

if (!defined('ABSPATH')) {
	exit;
}

?><!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
	<meta charset="<?php bloginfo('charset'); ?>">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<?php wp_head(); ?>
	<style>
		body.ma-deal-room-standalone {
			margin: 0;
			font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
			background: #f8fafc;
			color: #0f172a;
		}

		.ma-deal-room-frontend-wrapper {
			min-height: 100vh;
		}

		.ma-deal-room-frontend-noscript {
			max-width: 600px;
			margin: 4rem auto;
			padding: 1.5rem;
			border-radius: 12px;
			background: #fee2e2;
			border: 1px solid #ef4444;
			color: #991b1b;
			text-align: center;
			font-size: 1rem;
		}
	</style>
</head>
<body <?php body_class('ma-deal-room-standalone'); ?>>
	<div class="ma-deal-room-frontend-wrapper">
		<div id="ma-deal-room-app"></div>

		<noscript>
			<div class="ma-deal-room-frontend-noscript">
				<?php esc_html_e('JavaScript is required to use the MA Deal Room dashboard.', 'ma-deal-room'); ?>
			</div>
		</noscript>
	</div>

	<?php wp_footer(); ?>
</body>
</html>
