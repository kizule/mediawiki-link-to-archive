<?php

namespace LinkToArchive;

use Html;

class LinkToArchive
{
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
      $attribs['href'] = $url;

      // Analysing if it's already an archive link or .onion link
      if( preg_match( '/^https?:\/\/[^\.\/]+\.?[^\.\/]+\.onion(\/|$)/', $url ) ) $linkVariant = 'onion';
      else if( preg_match( '/^https:\/\/web\.archive\.org\/web/', $url ) ) $linkVariant = 'archive';
      else $linkVariant = 'normal';

      // Collection attributes for new link, url unchanged if archive link or .onion-link
      $archiveAttribs = [
        'rel' => $attribs['rel'],
        'href' => ( $linkVariant == 'normal' ? 'https://web.archive.org/web/' : '' ) . $url,
      ];
      if (isset($attribs['target'])) {
        $archiveAttribs['target'] = $attribs['target'];
      }

      $link = "";

      // Create new link construct, starting with normal link
      $link .= Html::rawElement('a', $attribs, $text) . '<sup class="ext-link-to-archive" title=';

      // title attribute according to link variant
      if( $linkVariant == 'onion' ) $link .= '"This is an .onion link">';
      else if( $linkVariant == 'archive' ) $link .= '"This is a web.archive.org link">';
      else $link .= '"Link to archived version">';

      // image and url of the small link according to link variant
      if( $linkVariant == 'onion' ) $link .= Html::rawElement('a', $archiveAttribs, '<img decoding="async" loading="lazy" src="/w/images/thumb/a/a8/Iconfinder_tor_386502.png/40px-Iconfinder_tor_386502.png" width="15" height="15" alt="onion" />');
      else                          $link .= Html::rawElement('a', $archiveAttribs, '<img decoding="async" loading="lazy" src="/w/images/7/73/Internet_Archive_logo.png" width="13" height="15" alt="archive.org" />');

      $link .= '</sup>';

      // We need to return false if we want to modify the HTML of external links
      return false;
    }

    return null;
  }
}
