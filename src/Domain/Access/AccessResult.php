<?php
/**
 * Structured Access Result.
 *
 * @package WPCalibrate\CommerceAccessRules
 */

declare(strict_types=1);

namespace WPCalibrate\CommerceAccessRules\Domain\Access;

/**
 * AccessResult value object.
 */
final class AccessResult {

	/**
	 * Whether access is allowed.
	 */
	private bool $allowed;

	/**
	 * Machine-readable reason code.
	 */
	private string $reason_code;

	/**
	 * Rule ID that triggered the decision, or null.
	 */
	private ?int $rule_id;

	/**
	 * Source that granted access (e.g. 'order_123', 'role_subscriber', 'user_45').
	 */
	private ?string $grant_source;

	/**
	 * Unix timestamp when access expires, or null if permanent.
	 */
	private ?int $expiry;

	/**
	 * Denied action (e.g. 'message', 'login_redirect', 'page_redirect', 'product_redirect', 'purchase_button', 'hide').
	 */
	private ?string $denied_action;

	/**
	 * Denied redirect URL if applicable.
	 */
	private ?string $redirect_url;

	/**
	 * Custom restriction message markup if applicable.
	 */
	private ?string $custom_message;

	/**
	 * Qualifying product ID for purchase button or redirect.
	 */
	private ?int $qualifying_product_id;

	/**
	 * Custom purchase button text.
	 */
	private ?string $purchase_button_text;

	/**
	 * Constructor.
	 *
	 * @param bool        $allowed               Whether access is allowed.
	 * @param string      $reason_code           Machine-readable reason code.
	 * @param int|null    $rule_id               Associated rule ID.
	 * @param string|null $grant_source          Source of grant.
	 * @param int|null    $expiry                Expiry timestamp.
	 * @param string|null $denied_action         Action on denial.
	 * @param string|null $redirect_url          URL to redirect to.
	 * @param string|null $custom_message        Custom message on denial.
	 * @param int|null    $qualifying_product_id Single qualifying product ID.
	 * @param string|null $purchase_button_text  Custom purchase button text.
	 */
	public function __construct(
		bool $allowed,
		string $reason_code,
		?int $rule_id = null,
		?string $grant_source = null,
		?int $expiry = null,
		?string $denied_action = null,
		?string $redirect_url = null,
		?string $custom_message = null,
		?int $qualifying_product_id = null,
		?string $purchase_button_text = null
	) {
		$this->allowed               = $allowed;
		$this->reason_code           = $reason_code;
		$this->rule_id               = $rule_id;
		$this->grant_source          = $grant_source;
		$this->expiry                = $expiry;
		$this->denied_action         = $denied_action;
		$this->redirect_url          = $redirect_url;
		$this->custom_message        = $custom_message;
		$this->qualifying_product_id = $qualifying_product_id;
		$this->purchase_button_text  = $purchase_button_text;
	}

	/**
	 * Create an allowed result.
	 *
	 * @param int|null    $rule_id      Rule ID.
	 * @param string|null $grant_source Grant source.
	 * @param int|null    $expiry       Expiry timestamp.
	 * @return self
	 */
	public static function allow( ?int $rule_id = null, ?string $grant_source = null, ?int $expiry = null ): self {
		return new self(
			true,
			ReasonCode::ALLOWED,
			$rule_id,
			$grant_source,
			$expiry
		);
	}

	/**
	 * Create an admin bypass result.
	 *
	 * @return self
	 */
	public static function admin_bypass(): self {
		return new self(
			true,
			ReasonCode::ADMIN_BYPASS,
			null,
			'admin_capability',
			null
		);
	}

	/**
	 * Create a denied result.
	 *
	 * @param string      $reason_code           Reason code.
	 * @param int|null    $rule_id               Rule ID.
	 * @param string|null $denied_action         Action on denial.
	 * @param string|null $redirect_url          Redirect URL.
	 * @param string|null $custom_message        Custom message.
	 * @param int|null    $qualifying_product_id Qualifying product ID.
	 * @param string|null $purchase_button_text  Button text.
	 * @return self
	 */
	public static function deny(
		string $reason_code,
		?int $rule_id = null,
		?string $denied_action = null,
		?string $redirect_url = null,
		?string $custom_message = null,
		?int $qualifying_product_id = null,
		?string $purchase_button_text = null
	): self {
		return new self(
			false,
			$reason_code,
			$rule_id,
			null,
			null,
			$denied_action,
			$redirect_url,
			$custom_message,
			$qualifying_product_id,
			$purchase_button_text
		);
	}

	public function is_allowed(): bool {
		return $this->allowed;
	}

	public function get_reason_code(): string {
		return $this->reason_code;
	}

	public function get_rule_id(): ?int {
		return $this->rule_id;
	}

	public function get_grant_source(): ?string {
		return $this->grant_source;
	}

	public function get_expiry(): ?int {
		return $this->expiry;
	}

	public function get_denied_action(): ?string {
		return $this->denied_action;
	}

	public function get_redirect_url(): ?string {
		return $this->redirect_url;
	}

	public function get_custom_message(): ?string {
		return $this->custom_message;
	}

	public function get_qualifying_product_id(): ?int {
		return $this->qualifying_product_id;
	}

	public function get_purchase_button_text(): ?string {
		return $this->purchase_button_text;
	}

	/**
	 * Convert to an associative array for diagnostics, REST responses, or inspector.
	 *
	 * @return array<string, mixed>
	 */
	public function to_array(): array {
		return array(
			'allowed'               => $this->allowed,
			'reason_code'           => $this->reason_code,
			'reason_label'          => ReasonCode::get_label( $this->reason_code ),
			'rule_id'               => $this->rule_id,
			'grant_source'          => $this->grant_source,
			'expiry'                => $this->expiry,
			'expiry_formatted'      => $this->expiry ? wp_date( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), $this->expiry ) : null,
			'denied_action'         => $this->denied_action,
			'redirect_url'          => $this->redirect_url,
			'custom_message'        => $this->custom_message,
			'qualifying_product_id' => $this->qualifying_product_id,
			'purchase_button_text'  => $this->purchase_button_text,
		);
	}
}
