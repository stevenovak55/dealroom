<?php
/**
 * Email Template Service
 *
 * Handles rendering of HTML email templates with variable replacement
 *
 * @package MADealRoom\Services
 * @since 1.0.0
 */

namespace MADealRoom\Services;

/**
 * Service for rendering email templates with variables
 */
class EmailTemplateService {
	/**
	 * Templates directory path
	 *
	 * @var string
	 */
	protected $templates_dir;

	/**
	 * Cache of rendered templates
	 *
	 * @var array
	 */
	protected $cache = [];

	/**
	 * Whether to use caching
	 *
	 * @var bool
	 */
	protected $use_cache = false;

	/**
	 * Constructor
	 *
	 * @param string|null $templates_dir Custom templates directory (optional)
	 */
	public function __construct(?string $templates_dir = null) {
		$this->templates_dir = $templates_dir ?? dirname(__DIR__) . '/Templates/emails';
	}

	/**
	 * Render an email template
	 *
	 * @param string $template Template name (without .php extension)
	 * @param array $variables Variables to pass to the template
	 * @return string Rendered HTML
	 * @throws \RuntimeException If template file doesn't exist
	 */
	public function render(string $template, array $variables = []): string {
		$cache_key = $this->getCacheKey($template, $variables);

		// Check cache if enabled
		if ($this->use_cache && isset($this->cache[$cache_key])) {
			return $this->cache[$cache_key];
		}

		$template_file = $this->getTemplatePath($template);

		if (!file_exists($template_file)) {
			throw new \RuntimeException(sprintf('Email template "%s" not found at: %s', $template, $template_file));
		}

		// Render template
		$html = $this->renderTemplate($template_file, $variables);

		// Cache if enabled
		if ($this->use_cache) {
			$this->cache[$cache_key] = $html;
		}

		return $html;
	}

	/**
	 * Render a template file with variables
	 *
	 * @param string $template_file Path to template file
	 * @param array $variables Variables to extract into template scope
	 * @return string Rendered HTML
	 */
	protected function renderTemplate(string $template_file, array $variables): string {
		// Start output buffering
		ob_start();

		// Extract variables into current scope
		extract($variables, EXTR_SKIP);

		// Include template file
		include $template_file;

		// Get buffer contents and clean
		return ob_get_clean();
	}

	/**
	 * Get full path to a template file
	 *
	 * @param string $template Template name
	 * @return string Full path to template file
	 */
	protected function getTemplatePath(string $template): string {
		// Remove .php extension if provided
		$template = str_replace('.php', '', $template);

		return $this->templates_dir . '/' . $template . '.php';
	}

	/**
	 * Get cache key for a template and variables
	 *
	 * @param string $template Template name
	 * @param array $variables Template variables
	 * @return string Cache key
	 */
	protected function getCacheKey(string $template, array $variables): string {
		return md5($template . serialize($variables));
	}

	/**
	 * Clear template cache
	 *
	 * @param string|null $template Specific template to clear (null for all)
	 * @return void
	 */
	public function clearCache(?string $template = null): void {
		if ($template === null) {
			$this->cache = [];
		} else {
			// Remove all cache entries for this template
			foreach ($this->cache as $key => $value) {
				if (strpos($key, md5($template)) === 0) {
					unset($this->cache[$key]);
				}
			}
		}
	}

	/**
	 * Enable or disable template caching
	 *
	 * @param bool $enabled Whether caching is enabled
	 * @return void
	 */
	public function setCaching(bool $enabled): void {
		$this->use_cache = $enabled;

		if (!$enabled) {
			$this->clearCache();
		}
	}

	/**
	 * Check if a template exists
	 *
	 * @param string $template Template name
	 * @return bool Whether template exists
	 */
	public function templateExists(string $template): bool {
		return file_exists($this->getTemplatePath($template));
	}

	/**
	 * Get list of available templates
	 *
	 * @return array List of template names
	 */
	public function getAvailableTemplates(): array {
		$templates = [];

		if (!is_dir($this->templates_dir)) {
			return $templates;
		}

		$files = scandir($this->templates_dir);

		foreach ($files as $file) {
			if (pathinfo($file, PATHINFO_EXTENSION) === 'php' && $file !== 'base.php') {
				$templates[] = pathinfo($file, PATHINFO_FILENAME);
			}
		}

		return $templates;
	}

	/**
	 * Render inline template (useful for testing or simple emails)
	 *
	 * @param string $template_content HTML template content
	 * @param array $variables Variables for replacement
	 * @return string Rendered HTML
	 */
	public function renderInline(string $template_content, array $variables = []): string {
		// Create temporary file
		$temp_file = tempnam(sys_get_temp_dir(), 'email_template_');
		file_put_contents($temp_file, $template_content);

		try {
			$html = $this->renderTemplate($temp_file, $variables);
		} finally {
			// Clean up temp file
			if (file_exists($temp_file)) {
				unlink($temp_file);
			}
		}

		return $html;
	}

	/**
	 * Helper method to escape HTML for email display
	 *
	 * @param string $text Text to escape
	 * @return string Escaped text
	 */
	public static function escape(string $text): string {
		return htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
	}

	/**
	 * Helper method to format currency for email display
	 *
	 * @param float $amount Amount to format
	 * @param string $currency Currency code (default: USD)
	 * @return string Formatted currency
	 */
	public static function formatCurrency(float $amount, string $currency = 'USD'): string {
		return '$' . number_format($amount, 2);
	}

	/**
	 * Helper method to format date for email display
	 *
	 * @param string|\DateTime $date Date to format
	 * @param string $format Date format (default: 'F j, Y')
	 * @return string Formatted date
	 */
	public static function formatDate($date, string $format = 'F j, Y'): string {
		if ($date instanceof \DateTime) {
			return $date->format($format);
		}

		if (is_string($date)) {
			$timestamp = strtotime($date);
			return $timestamp ? date($format, $timestamp) : '';
		}

		return '';
	}

	/**
	 * Inline CSS for email compatibility
	 *
	 * Note: For production use, consider using a library like Pelago\Emogrifier
	 * to properly inline CSS from <style> tags
	 *
	 * @param string $html HTML content
	 * @return string HTML with inlined CSS
	 */
	public function inlineCSS(string $html): string {
		// Basic implementation - for production, use a library like Emogrifier
		// This is a placeholder that returns HTML as-is since our templates
		// already use inline styles

		return $html;
	}

	/**
	 * Test email rendering (useful for development)
	 *
	 * @param string $template Template name
	 * @param array $variables Template variables
	 * @return array Test results with HTML and metadata
	 */
	public function test(string $template, array $variables = []): array {
		$start_time = microtime(true);

		try {
			$html = $this->render($template, $variables);
			$render_time = (microtime(true) - $start_time) * 1000; // Convert to ms

			return [
				'success' => true,
				'html' => $html,
				'template' => $template,
				'render_time_ms' => round($render_time, 2),
				'html_size_kb' => round(strlen($html) / 1024, 2),
				'variables' => array_keys($variables),
			];
		} catch (\Exception $e) {
			return [
				'success' => false,
				'error' => $e->getMessage(),
				'template' => $template,
			];
		}
	}
}
