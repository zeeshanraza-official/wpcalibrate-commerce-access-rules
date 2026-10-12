<?php
/**
 * Access Reason Codes.
 *
 * @package WPCalibrate\CommerceAccessRules
 */

declare(strict_types=1);

namespace WPCalibrate\CommerceAccessRules\Domain\Access;

/**
 * ReasonCode constants class.
 */
final class ReasonCode {

	public const ALLOWED                = 'allowed';
	public const GUEST                  = 'guest';
	public const ROLE_MISMATCH          = 'role_mismatch';
	public const USER_MISMATCH          = 'user_mismatch';
	public const NO_QUALIFYING_PURCHASE = 'no_qualifying_purchase';
	public const ORDER_STATUS_INVALID   = 'order_status_invalid';
	public const BEFORE_START           = 'before_start';
	public const EXPIRED                = 'expired';
	public const SUBSCRIPTION_INACTIVE  = 'subscription_inactive';
	public const RULE_DISABLED          = 'rule_disabled';
	public const NO_RULES_APPLIED       = 'no_rules_applied';
	public const ADMIN_BYPASS           = 'admin_bypass';

	/**
	 * Get human-readable description for a reason code.
	 *
	 * @param string $code Reason code string.
	 * @return string Human-readable label.
	 */
	public static function get_label( string $code ): string {
		$labels = array(
			self::ALLOWED                => __( 'Access Granted', 'wpcalibrate-commerce-access-rules' ),
			self::GUEST                  => __( 'User is not logged in', 'wpcalibrate-commerce-access-rules' ),
			self::ROLE_MISMATCH          => __( 'User role does not match required role(s)', 'wpcalibrate-commerce-access-rules' ),
			self::USER_MISMATCH          => __( 'User is not in the allowed users list', 'wpcalibrate-commerce-access-rules' ),
			self::NO_QUALIFYING_PURCHASE => __( 'No qualifying WooCommerce purchase found', 'wpcalibrate-commerce-access-rules' ),
			self::ORDER_STATUS_INVALID   => __( 'Purchase exists but order status is not qualifying or has been refunded', 'wpcalibrate-commerce-access-rules' ),
			self::BEFORE_START           => __( 'Restriction start date has not been reached yet', 'wpcalibrate-commerce-access-rules' ),
			self::EXPIRED                => __( 'Access duration or end date has expired', 'wpcalibrate-commerce-access-rules' ),
			self::SUBSCRIPTION_INACTIVE  => __( 'Associated WooCommerce Subscription is inactive or cancelled', 'wpcalibrate-commerce-access-rules' ),
			self::RULE_DISABLED          => __( 'Restriction rule is disabled', 'wpcalibrate-commerce-access-rules' ),
			self::NO_RULES_APPLIED       => __( 'No restriction rules applied to this content', 'wpcalibrate-commerce-access-rules' ),
			self::ADMIN_BYPASS           => __( 'Administrator preview bypass', 'wpcalibrate-commerce-access-rules' ),
		);

		return $labels[ $code ] ?? sprintf(
			/* translators: %s: raw reason code */
			__( 'Unknown reason: %s', 'wpcalibrate-commerce-access-rules' ),
			$code
		);
	}
}
