<?php

namespace LinkToArchive;

use BadMethodCallException;
use MediaWiki\Html\Html;

class LinkToArchive
{
  // =======
  // PRIVATE

  // Private Helper Fn for generating icon link data

  private static function singleIconLinkDataGenerator($linkVariant, $url, $altTitle = null) {
    global $wgExtensionAssetsPath;
    $basePath = "$wgExtensionAssetsPath/LinkToArchive/resources/images";

    if ($linkVariant == 'onion') {
      return [
        'title' => 'This is an .onion link',
        'href' => $url,
        'src' => "$basePath/tor-onion-logo.svg",
        'width' => '16',
        'alt' => 'onion icon',
      ];
    } else if ($linkVariant == 'archive') {
      return [
        'title' => ($altTitle ? $altTitle : 'This is a web.archive.org link'),
        'href' => $url,
        'src' => "$basePath/internet-archive-logo.svg",
        'width' => '14',
        'alt' => 'archive.org icon',
      ];
    } else if ($linkVariant == 'archivetoday') {
      return [
        'title' => ($altTitle ? $altTitle : 'This is an archive.today link'),
        'href' => $url,
        'src' => "$basePath/archive-today-logo.svg",
        'width' => '12',
        'alt' => 'archive.today icon',
      ];
    }

    return [];
  }

  // ======
  // PUBLIC

  /**
   * @param $url
   * @param $text
   * @param $link
   * @param array $attribs
   * @param $linktype
   * @return bool|null
   */
  public static function onLinkerMakeExternalLink($url, $text, &$link, array &$attribs, $linktype): ?bool
  {
    if ($linktype && in_array(parse_url($url, PHP_URL_SCHEME), ['http', 'https'])) {
      // Check if the URL ends with "action=edit"
      if (str_contains($url, 'action=edit')) {
          return null;
      }

      // -----------------
      // Identify use case

      // Check if it's already an .onion link
      if (preg_match('/^https?:\/\/[^\.\/]+\.?[^\.\/]+\.onion(\/|$)/', $url)) $linkVariant = 'onion';
      // Check if it's a web.archive.org link
      else if (preg_match('/^https:\/\/web\.archive\.org\/web/', $url)) $linkVariant = 'archive';
      // Check if it's an archive.today link. archive.today has many mirrors that are all essentially the same site
      else if (preg_match('/^https:\/\/archive\.(today|fo|is|li|md|ph|vn)/', $url)) $linkVariant = 'archivetoday';
      // Normal link, neither onion nor archives
      else $linkVariant = 'normal';

      // -------------------------
      // Generating icon link data

      $iconLinks = [];

      if ($linkVariant == 'onion') $iconLinks[] = self::singleIconLinkDataGenerator('onion', $url);
      else if ($linkVariant == 'archive') $iconLinks[] = self::singleIconLinkDataGenerator('archive', $url);
      else if ($linkVariant == 'archivetoday') $iconLinks[] = self::singleIconLinkDataGenerator('archivetoday', $url);
      else {
        $iconLinks[] = self::singleIconLinkDataGenerator('archive', "https://web.archive.org/web/$url", 'Link to archived version on web.archive.org');
        $iconLinks[] = self::singleIconLinkDataGenerator('archivetoday', "https://archive.today/$url", 'Link to archived version on archive.today');
      }

      // ------------------------
      // Rendering link construct

      // Create new link construct, starting with a normal link to the given external url
      $link = Html::rawElement('a', array_merge($attribs, ['href' => $url]), $text);

      // Default link attributes, href added later individually
      $linkAttributes = [];
      if (isset($attribs['rel'])) $linkAttributes['rel'] = $attribs['rel'];
      if (isset($attribs['target'])) $linkAttributes['target'] = $attribs['target'];

      // Add one or more icon links according to link variant
      $iconCount = count($iconLinks);
      for ($i = 0; $i < $iconCount; $i++) {
        $linkData = $iconLinks[$i];

        try {
          $label = wfMessage('archive')->parse();
        } catch (BadMethodCallException) {
          $label = 'archive';
        }

        $link .= '<sup class="ext-link-to-archive' . ($i < $iconCount - 1 ? ' has-sibling' : '') . '" title="' . $linkData['title'] . '">'
          . Html::rawElement('a', array_merge($linkAttributes, ['href' => $linkData['href']]),
              '<img src="' . $linkData['src'] . '" alt="' . $linkData['alt'] . '" width="' . $linkData['width'] . '" height="16" decoding="async" loading="lazy">')
          . '</sup>';
      }

      // We need to return false if we want to modify the HTML of external links
      return false;
    }

    return null;
  }
}

