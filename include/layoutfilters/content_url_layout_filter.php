<?php

namespace Arembi\Xfw\Inc\Filter;

use Arembi\Xfw\Core\Router;

class ContentUrlLayoutFilter implements LayoutFilter {

	public function filter(string $html): string
	{
		// Regex pattern:
		// (href)          - Capture Group 1: Matches 'href' (case-insensitive)
		// (\s*=\s*)       - Capture Group 2: Matches ' = ' with optional spaces
		// (["'])          - Capture Group 3: Matches the opening quote (either " or ')
		// (.*?)           - Capture Group 4: Non-greedily captures the URL (the part we want to uppercase)
		// \3              - Matches the closing quote (must be a backreference to Group 3 to ensure quotes match)
		// /i              - Makes the entire pattern case-insensitive (so it matches 'href', 'HREF', 'Href', etc.)
		$pattern = '/(href|src)(\s*=\s*)(["\'])(.*?)\3/i';

		// preg_replace_callback finds all matches and passes them to the anonymous function.
		// The function's return value is used as the replacement.
		$replacedHtml = preg_replace_callback(
			$pattern,
			function ($matches) {
				// $matches[0] is the full match (e.g., 'href="path/to/page.html"')
				// $matches[1] is the attribute name (e.g., 'HREF')
				// $matches[2] is the separator (e.g., ' = ')
				// $matches[3] is the quote (e.g., '"')
				// $matches[4] is the URL (e.g., 'path/to/page.html')

				$attributeName = $matches[1]; // e.g., 'HREF'
				$separator = $matches[2];     // e.g., ' = '
				$quote = $matches[3];         // e.g., '"'
				$url = $matches[4];           // e.g., 'path/to/page.html'
				
				// Convert only the URL part to uppercase
				$fixedUrl = Router::url($url);

				// Reconstruct the full attribute, preserving the original attribute name and spacing
				return $attributeName . $separator . $quote . $fixedUrl . $quote;
			},
			$html
		);

		// preg_replace_callback can return null on error.
		if ($replacedHtml === null) {
			return $html;
		}

		return $replacedHtml;
	}
}