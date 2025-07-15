<?php

namespace LinkToArchive;

use MediaWiki\Html\Html;
use MediaWiki\Output\OutputPage;

class LinkToArchive {
	public static function onBeforePageDisplay( OutputPage $out ) {
		$out->addModuleStyles( [ 'ext.linkToArchive.styles', 'ext.linkToArchive.icons' ] );
	}

	/**
	 * Generates data array for a single icon link based on the link variant
	 *
	 * @param string $linkVariant Type of link ('onion', 'archive', or 'archivetoday')
	 * @param string $url URL for the link
	 * @param string|null $customTitle Optional custom title for the link
	 * @return array Array containing link attributes (title, href, class, width, height, alt) or empty array if variant not recognized
	 */
	private static function singleIconLinkDataGenerator( $linkVariant, $url, $customTitle = null ) {
		if ( $linkVariant == 'onion' ) {
			return [
				'title' => wfMessage( 'linktoarchive-onion-link' )->text(),
				'href' => $url,
				'class' => 'mw-linktoarchive-tor-onion',
				'width' => '16',
				'height' => '16',
				'alt' => wfMessage( 'linktoarchive-onion-icon-alt' )->text(),
			];
		} elseif ( $linkVariant == 'archive' ) {
			return [
				'title' => ( $customTitle ? $customTitle : wfMessage( 'linktoarchive-archive-link' )->text() ),
				'href' => $url,
				'class' => 'mw-linktoarchive-internet-archive',
				'width' => '14',
				'height' => '14',
				'alt' => wfMessage( 'linktoarchive-archive-icon-alt' )->text(),
			];
		} elseif ( $linkVariant == 'archivetoday' ) {
			return [
				'title' => ( $customTitle ? $customTitle : wfMessage( 'linktoarchive-archivetoday-link' )->text() ),
				'href' => $url,
				'class' => 'mw-linktoarchive-archive-today',
				'width' => '12',
				'height' => '12',
				'alt' => wfMessage( 'linktoarchive-archivetoday-icon-alt' )->text(),
			];
		}

		return [];
	}

	/**
	 * Adds archive.org and archive.today icons/links to external URLs
	 *
	 * @param string $url URL being linked to
	 * @param string $text Link text
	 * @param string &$link Generated HTML of the link
	 * @param array &$attribs Array of HTML attributes for the link
	 * @param string|null $linktype Type of external link (optional)
	 * @return bool|null Returns false to override default link generation, null to use default
	 */
	public static function onLinkerMakeExternalLink( $url, $text, &$link, array &$attribs, $linktype ): ?bool {
		if ( $linktype && in_array( parse_url( $url, PHP_URL_SCHEME ), [ 'http', 'https' ] ) ) {
			// Check if the URL ends with "action=edit"
			if ( str_contains( $url, 'action=edit' ) ) {
				return null;
			}

			// -----------------
			// Identify use case

			// Check if it's already an .onion link
			if ( preg_match( '/^https?:\/\/[^\.\/]+\.?[^\.\/]+\.onion(\/|$)/', $url ) ) {
				$linkVariant = 'onion';
			}

			// Check if it's a web.archive.org link
			elseif ( preg_match( '/^https:\/\/web\.archive\.org\/web/', $url ) ) {
				$linkVariant = 'archive';
			}

			// Check if it's an archive.today link. archive.today has many mirrors that are all essentially the same site
			elseif ( preg_match( '/^https:\/\/archive\.(today|fo|is|li|md|ph|vn)/', $url ) ) {
				$linkVariant = 'archivetoday';
			}

			// Normal link, neither onion nor archives
			else {
				$linkVariant = 'normal';
			}

			// -------------------------
			// Generating icon link data

			$iconLinks = [];

			if ( $linkVariant == 'onion' ) {
				$iconLinks[] = self::singleIconLinkDataGenerator( 'onion', $url );
			} elseif ( $linkVariant == 'archive' ) {
				$iconLinks[] = self::singleIconLinkDataGenerator( 'archive', $url );
			} elseif ( $linkVariant == 'archivetoday' ) {
				$iconLinks[] = self::singleIconLinkDataGenerator( 'archivetoday', $url );
			} else {
				$iconLinks[] = self::singleIconLinkDataGenerator( 'archive', "https://web.archive.org/web/$url", wfMessage( 'linktoarchive-archive-link-desc' )->text() );
				$iconLinks[] = self::singleIconLinkDataGenerator( 'archivetoday', "https://archive.today/$url", wfMessage( 'linktoarchive-archivetoday-link-desc' )->text() );
			}

			// ------------------------
			// Rendering link construct

			// Create new link construct, starting with a normal link to the given external url
			$link = Html::rawElement( 'a', array_merge( $attribs, [ 'href' => $url ] ), $text );

			// Default link attributes, href added later individually
			$linkAttributes = [];
			if ( isset( $attribs['rel'] ) ) {
				$linkAttributes['rel'] = $attribs['rel'];
			}
			if ( isset( $attribs['target'] ) ) {
				$linkAttributes['target'] = $attribs['target'];
			}

			// Add one or more icon links according to link variant
			$iconCount = count( $iconLinks );
			for ( $i = 0; $i < $iconCount; $i++ ) {
				$linkData = $iconLinks[$i];

				$label = wfMessage( 'linktoarchive-archive-label' )->text();

				$link .= '<sup class="ext-link-to-archive' . ( $i < $iconCount - 1 ? ' has-sibling' : '' ) . '" title="' . $linkData['title'] . '">'
					. Html::rawElement( 'a', array_merge( $linkAttributes, [ 'href' => $linkData['href'] ] ),
						Html::element( 'span', [ 'class' => $linkData['class'], 'alt' => $linkData['alt'], 'title' => $linkData['alt'] ] )
					) . '</sup>';
			}

			// We need to return false if we want to modify the HTML of external links
			return false;
		}

		return null;
	}
}
