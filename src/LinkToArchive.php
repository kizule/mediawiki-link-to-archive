<?php
namespace LinkToArchive;

use MediaWiki\Html\Html;
use MediaWiki\MediaWikiServices;
use MediaWiki\Output\OutputPage;

class LinkToArchive {
	// Define constants for link types for clarity and to avoid magic strings.
	private const LINK_TYPE_REGULAR = 'regular';
	private const LINK_TYPE_WEB_ARCHIVE = 'web.archive';
	private const LINK_TYPE_ARCHIVE_TODAY = 'archive.today';
	private const LINK_TYPE_ONION = 'onion';

	/**
	 * Hook handler for BeforePageDisplay.
	 * Adds the necessary modules to the output page.
	 *
	 * @param OutputPage $out The output page object.
	 */
	public static function onBeforePageDisplay( OutputPage $out ) {
		$out->addModules( [ 'ext.linkToArchive' ] );
		$out->addModuleStyles( [ 'ext.linkToArchive.styles' ] );
	}

	/**
	 * Hook handler for LinkerMakeExternalLink.
	 * Adds archive.org and archive.today icons/links to external URLs.
	 *
	 * @param string $url URL being linked to
	 * @param string $text Link text
	 * @param string &$link Generated HTML of the link (output)
	 * @param array &$attribs Array of HTML attributes for the link
	 * @param string|null $linktype Type of external link (optional)
	 * @return bool|null Returns false to override default link generation, null to use default.
	 */
	public static function onLinkerMakeExternalLink( $url, $text, &$link, array &$attribs, $linktype ): ?bool {
		// Only process http/https links with a valid linktype.
		if ( !$linktype || !in_array( parse_url( $url, PHP_URL_SCHEME ), [ 'http', 'https' ] ) ) {
			return null;
		}

		// Skip links that should not have archive links.
		if ( self::shouldSkipLink( $url ) ) {
			return null;
		}

		// Add a class to the original link's attributes to signal it has been processed by PHP.
		// This helps the corresponding JS file avoid reprocessing the same link.
		$originalLinkAttribs = $attribs;
		$originalLinkAttribs['class'] = trim( ( $attribs['class'] ?? '' ) . ' php-archive-processed' );

		// Create the original link with the new class.
		$originalLink = Html::rawElement( 'a', array_merge( $originalLinkAttribs, [ 'href' => $url ] ), $text );

		// Base attributes for the separate archive links.
		$archiveLinkAttribs = [
			'class' => 'mw-archive-link',
			'rel' => $attribs['rel'] ?? 'noopener noreferrer',
			'target' => $attribs['target'] ?? '_blank'
		];

		// Determine the link type to avoid redundant regex checks.
		$urlType = self::getUrlType( $url );

		// Build the output HTML with the original link followed by archive links.
		$link = $originalLink;
		self::addArchiveLinks( $url, $urlType, $archiveLinkAttribs, $link );

		// Return false to override MediaWiki's default link generation.
		return false;
	}

	/**
	 * Determines if a URL should be skipped entirely.
	 *
	 * @param string $url URL to check.
	 * @return bool True if the URL should be skipped.
	 */
	private static function shouldSkipLink( string $url ): bool {
		// Skip internal actions like editing.
		if ( str_contains( $url, 'action=edit' ) ) {
			return true;
		}

		// Future conditions for skipping links can be added here.
		return false;
	}

	/**
	 * Determines the type of a given URL.
	 *
	 * @param string $url The URL to classify.
	 * @return string The type of the URL.
	 */
	private static function getUrlType( string $url ): string {
		if ( preg_match( '/^https?:\/\/[^\.\/]+\.?[^\.\/]+\.onion(\/|$)/', $url ) ) {
			return self::LINK_TYPE_ONION;
		}
		if ( preg_match( '/^https:\/\/web\.archive\.org\/web/', $url ) ) {
			return self::LINK_TYPE_WEB_ARCHIVE;
		}
		if ( preg_match( '/^https:\/\/archive\.(today|fo|is|li|md|ph|vn)/', $url ) ) {
			return self::LINK_TYPE_ARCHIVE_TODAY;
		}
		return self::LINK_TYPE_REGULAR;
	}

	/**
	 * Appends the appropriate archive links to the HTML based on URL type.
	 *
	 * @param string $url URL being linked to.
	 * @param string $urlType The classified type of the URL.
	 * @param array $baseAttribs Base HTML attributes for archive links.
	 * @param string &$link Link HTML to modify.
	 */
	private static function addArchiveLinks( string $url, string $urlType, array $baseAttribs, string &$link ): void {
		// Add space after the original link.
		$link .= ' ';

		switch ( $urlType ) {
			case self::LINK_TYPE_ONION:
				$link .= self::createArchiveLink( $url, 'linktoarchive-onion-label', 'linktoarchive-onion-link', 'archive-onion', $baseAttribs );
				break;

			case self::LINK_TYPE_WEB_ARCHIVE:
				$link .= self::createArchiveLink( $url, 'linktoarchive-archive-label', 'linktoarchive-archive-link', 'archive-web', $baseAttribs );
				break;

			case self::LINK_TYPE_ARCHIVE_TODAY:
				$link .= self::createArchiveLink( $url, 'linktoarchive-archivetoday-label', 'linktoarchive-archivetoday-link', 'archive-today', $baseAttribs );
				break;

			case self::LINK_TYPE_REGULAR:
			default:
				// For regular links, add both archive options.
				$link .= self::createArchiveLink( "https://web.archive.org/web/$url", 'linktoarchive-archive-label', 'linktoarchive-archive-link-desc', 'archive-web', $baseAttribs );
				$link .= ' ';
				$link .= self::createArchiveLink( "https://archive.today/$url", 'linktoarchive-archivetoday-label', 'linktoarchive-archivetoday-link-desc', 'archive-today', $baseAttribs );
				break;
		}
	}

	/**
	 * Creates the HTML for a single archive link.
	 *
	 * @param string $href The URL for the link.
	 * @param string $labelKey The i18n message key for the link text.
	 * @param string $titleKey The i18n message key for the link title attribute.
	 * @param string $extraClass An additional CSS class to add to the link.
	 * @param array $baseAttribs The base HTML attributes for the link.
	 * @return string The generated HTML for the anchor tag.
	 */
	private static function createArchiveLink( string $href, string $labelKey, string $titleKey, string $extraClass, array $baseAttribs ): string {
		$lang = MediaWikiServices::getInstance()->getContentLanguage();
		$labelMsg = wfMessage( $labelKey )->inLanguage( $lang );
		// Use text() and manually escape. If the message doesn't exist, text() will
		// return the key, which is the desired fallback in this situation.
		$labelText = '[' . htmlspecialchars( $labelMsg->text() ) . ']';

		$titleMsg = wfMessage( $titleKey )->inLanguage( $lang );
		$titleText = htmlspecialchars( $titleMsg->text() );

		$attribs = array_merge( $baseAttribs, [
			'href' => $href,
			'title' => $titleText,
			'class' => trim( $baseAttribs['class'] . ' ' . $extraClass )
		] );
		return Html::rawElement( 'a', $attribs, $labelText );
	}
}
