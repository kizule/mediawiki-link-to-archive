<?php

namespace LinkToArchive;

use Html;

class LinkToArchive
{
  // =======
  // PRIVATE

  // Private Helper Fn for generating icon link data

  private static function singleIconLinkDataGenerator($linkVariant, $url, $altTitle = null) {
    if ($linkVariant == 'onion') {
      return [
        'title' => 'This is an .onion link',
        'href' => $url,
        'src' => '/w/images/thumb/a/a8/Iconfinder_tor_386502.png/40px-Iconfinder_tor_386502.png',
        'width' => '15',
        'alt' => 'onion icon',
      ];
    } else if ($linkVariant == 'archive') {
      return [
        'title' => ($altTitle ? $altTitle : 'This is a web.archive.org link'),
        'href' => $url,
        'src' => '/w/images/7/73/Internet_Archive_logo.png',
        'width' => '13',
        'alt' => 'archive.org icon',
      ];
    } else if ($linkVariant == 'archivetoday') {
      return [
        'title' => ($altTitle ? $altTitle : 'This is an archive.today link'),
        'href' => $url,
        'src' => '/w/images/8/8f/Archive-today-logo-homage.svg',
        'width' => '11',
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
  * @return bool
  */
  public static function onLinkerMakeExternalLink($url, $text, &$link, array &$attribs, $linktype) {

    if ($linktype && in_array(parse_url($url, PHP_URL_SCHEME), ['http', 'https']) ) {

      // -----------------
      // Identify use case

      // Check if it's already an .onion link
      if( preg_match( '/^https?:\/\/[^\.\/]+\.?[^\.\/]+\.onion(\/|$)/', $url ) ) $linkVariant = 'onion';
      // Check if it's a web.archive.org link
      else if( preg_match( '/^https:\/\/web\.archive\.org\/web/', $url ) ) $linkVariant = 'archive';
      // Check if it's an archive.today link. archive.today has many mirrors that are all essentially the same site
      else if( preg_match( '/^https:\/\/archive\.(today|fo|is|li|md|ph|vn)/', $url ) ) $linkVariant = 'archivetoday';
      // Normal link, neither onion nor archives
      else $linkVariant = 'normal';

      // -------------------------
      // Generating icon link data

      $iconLinks = [];

      if( $linkVariant == 'onion' ) array_push( $iconLinks, self::singleIconLinkDataGenerator( 'onion', $url ) );
      else if( $linkVariant == 'archive' ) array_push( $iconLinks, self::singleIconLinkDataGenerator( 'archive', $url ) );
      else if( $linkVariant == 'archivetoday' ) array_push( $iconLinks, self::singleIconLinkDataGenerator( 'archivetoday', $url ) );
      else {
        array_push( $iconLinks, self::singleIconLinkDataGenerator( 'archive', "https://web.archive.org/web/$url", 'Link to archived version on web.archive.org' ) );
        array_push( $iconLinks, self::singleIconLinkDataGenerator( 'archivetoday', "https://archive.today/$url", 'Link to archived version on archive.today' ) );
      }

      // ------------------------
      // Rendering link construct

      // Create new link construct, starting with a normal link to the given external url
      $link = Html::rawElement('a', array_merge( $attribs, [ 'href' => $url ] ), $text);

      // Default link attributes, href added later individually

      $linkAttributes = [];
      if( isset( $attribs['rel'] ) ) $linkAttributes['rel'] = $attribs['rel'];
      if( isset( $attribs['target'] ) ) $linkAttributes['target'] = $attribs['target'];

      // Add one or more icon links according to link variant

      $iconCount = count( $iconLinks );
      for( $i = 0; $i < $iconCount; $i++ ) {
        $linkData = $iconLinks[$i];
        $link .= '<sup class="ext-link-to-archive'.( $i < $iconCount - 1 ? ' has-sibling' : '' ).'" title="'.$linkData['title'].'">'
          . Html::rawElement( 'a', array_merge( $linkAttributes, [ 'href' => $linkData['href'] ] ), '<img src="'.$linkData['src'].'" alt="'.$linkData['alt'].'" width="'.$linkData['width'].'" height="15" decoding="async" loading="lazy">' )
          . '</sup>';
      }

      // Changes indicated: We need to return false if we want to modify the HTML of external links
      return false;
    }

    // No changes indicated by returning true
    return true;
  }

}
