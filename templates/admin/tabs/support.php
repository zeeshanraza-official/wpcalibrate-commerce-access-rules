<?php
/**
 * Support & Contact Admin Tab.
 *
 * @package WPCalibrate\CommerceAccessRules
 */

declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>

<div class="wpcal-support-container">
	<div class="wpcal-card">
		<h2><?php esc_html_e( 'WPCalibrate Support & Contact', 'wpcalibrate-commerce-access-rules' ); ?></h2>
		<p class="description">
			<?php esc_html_e( 'Need assistance configuring access rules, integrating with your theme, or extending functionality? Our engineering team is here to help.', 'wpcalibrate-commerce-access-rules' ); ?>
		</p>

		<div class="wpcal-grid-2" style="margin-top: 24px;">
			<div class="wpcal-card" style="background: #fafafa; border: 1px solid #e2e8f0;">
				<h3><?php esc_html_e( 'Direct Support Channels', 'wpcalibrate-commerce-access-rules' ); ?></h3>

				<table class="form-table" role="presentation" style="margin-top: 0;">
					<tbody>
						<tr>
							<th scope="row" style="width: 140px;"><strong><?php esc_html_e( 'Email Support:', 'wpcalibrate-commerce-access-rules' ); ?></strong></th>
							<td>
								<a href="<?php echo esc_url( 'mailto:support@wpcalibrate.com' ); ?>" class="wpcal-support-link">
									support@wpcalibrate.com
								</a>
							</td>
						</tr>
						<tr>
							<th scope="row"><strong><?php esc_html_e( 'Phone / WhatsApp:', 'wpcalibrate-commerce-access-rules' ); ?></strong></th>
							<td>
								<a href="<?php echo esc_url( 'https://wa.me/447474795976' ); ?>" target="_blank" rel="noopener noreferrer" class="wpcal-support-link">
									+447474795976
								</a>
								<span class="description" style="margin-left: 8px;">(<?php esc_html_e( 'Phone/WhatsApp Support', 'wpcalibrate-commerce-access-rules' ); ?>)</span>
							</td>
						</tr>
						<tr>
							<th scope="row"><strong><?php esc_html_e( 'Official Website:', 'wpcalibrate-commerce-access-rules' ); ?></strong></th>
							<td>
								<a href="<?php echo esc_url( 'https://wpcalibrate.com' ); ?>" target="_blank" rel="noopener noreferrer" class="wpcal-support-link">
									https://wpcalibrate.com ↗
								</a>
							</td>
						</tr>
						<tr>
							<th scope="row"><strong><?php esc_html_e( 'Marketplace:', 'wpcalibrate-commerce-access-rules' ); ?></strong></th>
							<td>
								<a href="<?php echo esc_url( 'https://marketplace.wpcalibrate.com/' ); ?>" target="_blank" rel="noopener noreferrer" class="wpcal-support-link">
									https://marketplace.wpcalibrate.com/ ↗
								</a>
							</td>
						</tr>
					</tbody>
				</table>
			</div>

			<div class="wpcal-card" style="background: #fafafa; border: 1px solid #e2e8f0;">
				<h3><?php esc_html_e( 'Shortcode Quick Reference', 'wpcalibrate-commerce-access-rules' ); ?></h3>
				<p>
					<?php esc_html_e( 'Protect specific sections of any page or post using our namespaced shortcode:', 'wpcalibrate-commerce-access-rules' ); ?>
				</p>
				<pre style="background: #fff; padding: 12px; border: 1px solid #ddd; border-radius: 4px; overflow-x: auto; font-size: 13px;">[wpcalibrate_restrict rule="123"]
   Protected VIP content goes here...
[/wpcalibrate_restrict]</pre>

				<h4 style="margin-top: 16px;"><?php esc_html_e( 'Supported Shortcode Attributes:', 'wpcalibrate-commerce-access-rules' ); ?></h4>
				<ul style="list-style-type: disc; margin-left: 20px; font-size: 13px;">
					<li><code>rule="123"</code> — <?php esc_html_e( 'ID of the restriction rule to evaluate.', 'wpcalibrate-commerce-access-rules' ); ?></li>
					<li><code>action="hide"</code> — <?php esc_html_e( 'Optionally override denial action to hide inline content completely.', 'wpcalibrate-commerce-access-rules' ); ?></li>
					<li><code>message="..."</code> — <?php esc_html_e( 'Optionally override the denial message text.', 'wpcalibrate-commerce-access-rules' ); ?></li>
				</ul>
			</div>
		</div>
	</div>
</div>
