<?php
/**
 * Plugin Reset Admin Page
 *
 * Provides admin UI for resetting the plugin
 *
 * @package MA_Deal_Room
 * @subpackage Admin
 * @since 1.0.1
 */

namespace MADealRoom\Admin;

use MADealRoom\Services\PluginResetService;

class PluginResetPage {
	/**
	 * Initialize the admin page
	 */
	public function init(): void {
		add_action('admin_menu', [$this, 'addMenuPage'], 100);
		add_action('admin_post_ma_deal_room_reset_plugin', [$this, 'handleReset']);
	}

	/**
	 * Add admin menu page
	 */
	public function addMenuPage(): void {
		add_submenu_page(
			'ma-deal-room',
			'Reset Plugin',
			'<span style="color:#f56e28;">Reset Plugin</span>',
			'manage_options',
			'ma-deal-room-reset',
			[$this, 'renderPage']
		);
	}

	/**
	 * Render the reset page
	 */
	public function renderPage(): void {
		// Check user permissions
		if (!current_user_can('manage_options')) {
			wp_die('You do not have sufficient permissions to access this page.');
		}

		// Check if we just completed a reset
		$show_result = isset($_GET['reset']) && $_GET['reset'] === 'done';
		$result = null;

		if ($show_result) {
			$result = get_transient('ma_deal_room_reset_result');
			delete_transient('ma_deal_room_reset_result');
		}

		?>
		<div class="wrap">
			<h1>Reset MA Deal Room Plugin</h1>

			<?php if ($result): ?>
				<?php if ($result['success']): ?>
					<div class="notice notice-success" style="padding: 20px; margin: 20px 0;">
						<h2 style="margin-top: 0;">✅ <?php echo $result['dry_run'] ? 'Dry-Run Completed Successfully!' : 'Reset Successful!'; ?></h2>
						<p><?php echo $result['dry_run'] ? 'Dry-run completed successfully. No actual changes were made to your database.' : 'Plugin has been completely reset.'; ?></p>

						<?php if ($result['dry_run']): ?>
							<div style="background: #fff3cd; padding: 15px; margin-top: 15px; border-left: 4px solid #ffc107;">
								<strong>Note:</strong> This was a dry-run test. Your database was NOT modified.
								To perform the actual reset, use the "RESET PLUGIN NOW" button below.
							</div>
						<?php endif; ?>

						<?php if (!$result['dry_run'] && isset($result['backup'])): ?>
							<p><strong>Backup created:</strong> <?php echo esc_html($result['backup']['file']); ?></p>
							<p><strong>Backup size:</strong> <?php echo size_format($result['backup']['size']); ?></p>
						<?php endif; ?>
					</div>

					<?php if (isset($result['verification'])): ?>
						<div class="notice notice-info" style="padding: 20px; margin: 20px 0;">
							<h3 style="margin-top: 0;">Verification Results</h3>
							<p><strong>Checks passed:</strong> <?php echo $result['verification']['passed_checks']; ?> /  <?php echo $result['verification']['total_checks']; ?></p>

							<table class="widefat striped" style="margin-top: 15px;">
								<thead>
									<tr>
										<th>Check</th>
										<th>Result</th>
										<th>Details</th>
									</tr>
								</thead>
								<tbody>
									<?php foreach ($result['verification']['checks'] as $check): ?>
										<tr>
											<td><?php echo esc_html($check['name']); ?></td>
											<td><?php echo $check['passed'] ? '✅ Passed' : '❌ Failed'; ?></td>
											<td>
												<?php if (isset($check['actual']) && isset($check['expected'])): ?>
													Expected: <?php echo esc_html($check['expected']); ?>,
													Got: <?php echo esc_html($check['actual']); ?>
												<?php endif; ?>
											</td>
										</tr>
									<?php endforeach; ?>
								</tbody>
							</table>
						</div>
					<?php endif; ?>

					<?php if (isset($result['log'])): ?>
						<div class="notice notice-info" style="padding: 20px; margin: 20px 0;">
							<h3 style="margin-top: 0;">Reset Log</h3>
							<div style="background: #fff; padding: 10px; border: 1px solid #ccc; max-height: 400px; overflow-y: auto; font-family: monospace; font-size: 12px;">
								<?php foreach ($result['log'] as $entry): ?>
									<div style="padding: 3px; <?php echo $entry['level'] === 'error' ? 'color: #dc3232;' : ($entry['level'] === 'success' ? 'color: #46b450;' : ''); ?>">
										[<?php echo esc_html($entry['timestamp']); ?>]
										[<?php echo strtoupper(esc_html($entry['level'])); ?>]
										<?php echo esc_html($entry['message']); ?>
									</div>
								<?php endforeach; ?>
							</div>
						</div>
					<?php endif; ?>
				<?php else: ?>
					<div class="notice notice-error" style="padding: 20px; margin: 20px 0;">
						<h2 style="margin-top: 0;">❌ Reset Failed!</h2>
						<p><?php echo isset($result['error']) ? esc_html($result['error']) : 'Unknown error occurred.'; ?></p>

						<?php if (isset($result['log'])): ?>
							<h3>Error Log:</h3>
							<div style="background: #fff; padding: 10px; border: 1px solid #ccc; max-height: 400px; overflow-y: auto; font-family: monospace; font-size: 12px;">
								<?php foreach ($result['log'] as $entry): ?>
									<div style="padding: 3px; color: <?php echo $entry['level'] === 'error' ? '#dc3232' : '#000'; ?>;">
										[<?php echo esc_html($entry['timestamp']); ?>]
										[<?php echo strtoupper(esc_html($entry['level'])); ?>]
										<?php echo esc_html($entry['message']); ?>
									</div>
								<?php endforeach; ?>
							</div>
						<?php endif; ?>
					</div>
				<?php endif; ?>
			<?php endif; ?>

			<div class="notice notice-error" style="padding: 20px; margin: 20px 0; border-left: 4px solid #dc3232;">
				<h2 style="margin-top: 0;">⚠️ WARNING: Destructive Action</h2>
				<p style="font-size: 14px; line-height: 1.6;">
					This will <strong>completely reset</strong> the MA Deal Room plugin by:
				</p>
				<ul style="margin-left: 20px; line-height: 1.8;">
					<li>Dropping all MA Deal Room database tables</li>
					<li>Deleting all plugin WordPress options</li>
					<li>Recreating all tables from scratch</li>
					<li>Reseeding templates and task definitions</li>
					<li>Creating default account</li>
				</ul>
				<p style="font-size: 14px; line-height: 1.6; margin-top: 15px;">
					<strong>What will be DELETED:</strong>
				</p>
				<ul style="margin-left: 20px; line-height: 1.8; color: #dc3232;">
					<li>All transactions</li>
					<li>All tasks</li>
					<li>All documents</li>
					<li>All users (custom users, not WordPress users)</li>
					<li>All MLS configurations</li>
					<li>All custom templates</li>
					<li>All notifications</li>
					<li><strong>EVERYTHING in the MA Deal Room plugin</strong></li>
				</ul>
				<p style="font-size: 14px; line-height: 1.6; margin-top: 15px;">
					<strong>What will be PRESERVED:</strong>
				</p>
				<ul style="margin-left: 20px; line-height: 1.8; color: #46b450;">
					<li>A backup SQL file will be created before reset</li>
					<li>WordPress users are not affected</li>
					<li>Other WordPress data is not affected</li>
				</ul>
			</div>

			<div class="notice notice-info" style="padding: 20px; margin: 20px 0;">
				<h3 style="margin-top: 0;">When to Use This</h3>
				<p>Use this reset function when:</p>
				<ul style="margin-left: 20px; line-height: 1.8;">
					<li>You've just updated the plugin and want a fresh start</li>
					<li>Your live site isn't working and you want to match the dev site exactly</li>
					<li>Database tables are corrupted or incomplete</li>
					<li>You're troubleshooting issues and want to eliminate database problems</li>
				</ul>

				<h3>What Happens During Reset</h3>
				<ol style="margin-left: 20px; line-height: 1.8;">
					<li><strong>Backup:</strong> Creates SQL backup in <code>wp-content/ma-deal-room-backups/</code></li>
					<li><strong>Wipe:</strong> Drops all MA Deal Room tables and options</li>
					<li><strong>Recreate:</strong> Runs all 21 database migrations</li>
					<li><strong>Seed:</strong> Syncs 7 templates and 276 task definitions</li>
					<li><strong>Initialize:</strong> Creates default account</li>
					<li><strong>Verify:</strong> Checks that everything was created correctly</li>
				</ol>

				<p><strong>Total time:</strong> 5-10 seconds</p>
			</div>

			<div style="background: #fff; padding: 20px; border: 1px solid #ccd0d4; margin: 20px 0;">
				<h3 style="margin-top: 0;">Test Mode (Recommended First)</h3>
				<p>Run a dry-run first to see what will happen without actually making changes:</p>

				<form method="post" action="<?php echo admin_url('admin-post.php'); ?>" style="margin: 20px 0;">
					<?php wp_nonce_field('ma_deal_room_reset_plugin', 'ma_deal_room_reset_nonce'); ?>
					<input type="hidden" name="action" value="ma_deal_room_reset_plugin">
					<input type="hidden" name="dry_run" value="1">

					<button type="submit" class="button button-secondary button-large">
						🔍 Run Dry-Run (Test Mode)
					</button>
					<p class="description">
						This will simulate the reset without making any actual changes.
						Safe to run - nothing will be modified.
					</p>
				</form>
			</div>

			<div style="background: #fff; padding: 20px; border: 1px solid #ccd0d4; margin: 20px 0;">
				<h3 style="margin-top: 0; color: #dc3232;">⚠️ DANGER ZONE - Actual Reset</h3>
				<p style="font-size: 14px; line-height: 1.6;">
					<strong>Before clicking this button:</strong>
				</p>
				<ul style="margin-left: 20px; line-height: 1.8;">
					<li>Make sure you have a recent full site backup</li>
					<li>Run the dry-run test above first</li>
					<li>Inform all users that data will be reset</li>
					<li>Type "RESET" in the confirmation box below</li>
				</ul>

				<form method="post" action="<?php echo admin_url('admin-post.php'); ?>"
				      onsubmit="return confirmReset();" style="margin: 20px 0;">
					<?php wp_nonce_field('ma_deal_room_reset_plugin', 'ma_deal_room_reset_nonce'); ?>
					<input type="hidden" name="action" value="ma_deal_room_reset_plugin">
					<input type="hidden" name="dry_run" value="0">

					<p>
						<label for="confirmation_text" style="font-weight: bold;">
							Type "RESET" to confirm:
						</label><br>
						<input type="text"
						       id="confirmation_text"
						       name="confirmation"
						       style="width: 200px; margin-top: 10px;"
						       placeholder="Type RESET here">
					</p>

					<button type="submit" class="button button-primary button-large"
					        style="background-color: #dc3232; border-color: #dc3232; text-shadow: none;">
						🔥 RESET PLUGIN NOW
					</button>
					<p class="description" style="color: #dc3232; font-weight: bold;">
						This action cannot be undone! All data will be deleted!
					</p>
				</form>
			</div>

			<div style="background: #f0f0f1; padding: 15px; margin: 20px 0;">
				<h4 style="margin-top: 0;">Need Help?</h4>
				<p>
					If you're experiencing issues and not sure if reset is the right solution,
					try these first:
				</p>
				<ul style="margin-left: 20px;">
					<li>Deactivate and reactivate the plugin</li>
					<li>Clear all caches (WordPress, browser, server)</li>
					<li>Check browser console for errors (F12)</li>
					<li>Review error logs in <code>wp-content/debug.log</code></li>
				</ul>
			</div>
		</div>

		<script>
		function confirmReset() {
			const input = document.getElementById('confirmation_text').value;

			if (input !== 'RESET') {
				alert('Please type "RESET" (in capital letters) to confirm.');
				return false;
			}

			return confirm(
				'⚠️ FINAL WARNING ⚠️\n\n' +
				'This will DELETE ALL DATA in the MA Deal Room plugin!\n\n' +
				'Are you absolutely sure you want to proceed?'
			);
		}
		</script>

		<style>
		.wrap h1 {
			color: #dc3232;
		}
		</style>
		<?php
	}

	/**
	 * Handle reset form submission
	 */
	public function handleReset(): void {
		// Check nonce
		if (!isset($_POST['ma_deal_room_reset_nonce']) ||
		    !wp_verify_nonce($_POST['ma_deal_room_reset_nonce'], 'ma_deal_room_reset_plugin')) {
			wp_die('Security check failed');
		}

		// Check permissions
		if (!current_user_can('manage_options')) {
			wp_die('You do not have sufficient permissions to perform this action.');
		}

		$dry_run = isset($_POST['dry_run']) && $_POST['dry_run'] === '1';

		// If not dry run, verify confirmation
		if (!$dry_run) {
			if (!isset($_POST['confirmation']) || $_POST['confirmation'] !== 'RESET') {
				wp_die('Invalid confirmation. Please type "RESET" to confirm.');
			}
		}

		// Run the reset
		$reset_service = new PluginResetService($dry_run);
		$result = $reset_service->reset();

		// Store result in transient for display
		set_transient('ma_deal_room_reset_result', $result, 300); // 5 minutes

		// Redirect back to reset page
		wp_redirect(add_query_arg('reset', 'done', admin_url('admin.php?page=ma-deal-room-reset')));
		exit;
	}
}
