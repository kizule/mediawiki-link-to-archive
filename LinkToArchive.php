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
      // archive.today has many mirrors that are all essentially the same site
      else if( preg_match( '/^https:\/\/archive\.today/', $url ) ) $linkVariant = 'archivetoday';
      else if( preg_match( '/^https:\/\/archive\.fo/', $url ) ) $linkVariant = 'archivetoday';
      else if( preg_match( '/^https:\/\/archive\.is/', $url ) ) $linkVariant = 'archivetoday';
      else if( preg_match( '/^https:\/\/archive\.li/', $url ) ) $linkVariant = 'archivetoday';
      else if( preg_match( '/^https:\/\/archive\.md/', $url ) ) $linkVariant = 'archivetoday';
      else if( preg_match( '/^https:\/\/archive\.ph/', $url ) ) $linkVariant = 'archivetoday';
      else if( preg_match( '/^https:\/\/archive\.vn/', $url ) ) $linkVariant = 'archivetoday';
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
      else if( $linkVariant == 'archivetoday' ) $link .= '"This is an archive.today link">';
      else $link .= '"Link to archived version">';

      // image and url of the small link according to link variant
      if( $linkVariant == 'onion' )             $link .= Html::rawElement('a', $archiveAttribs, '<img decoding="async" loading="lazy" src="/w/images/thumb/a/a8/Iconfinder_tor_386502.png/40px-Iconfinder_tor_386502.png" width="15" height="15" alt="onion" />');
      // TODO: The image path specified here will need to be fixed, and
      // archive.today's logo will need to be added to the server. The icon
      // is at https://archive.ph/apple-touch-icon-144x144.png, and I've
      // uploaded it to the Wiki already at
      // https://www.kicksecure.com/wiki/File:Archive-today-favicon.png.
      // This icon may need to be edited to have a transparent background.
      else if( $linkVariant == 'archivetoday' ) $link .= Html::rawElement('a', $archiveAttribs, '<img decoding="async" loading="lazy" src="/w/images/TODO/FIX/THIS/PATH/Archive_Today_logo.png" width="13" height="15" alt="archive.today" />');
      else                                      $link .= Html::rawElement('a', $archiveAttribs, '<img decoding="async" loading="lazy" src="/w/images/7/73/Internet_Archive_logo.png" width="13" height="15" alt="archive.org" />');

      $link .= '</sup>';

      // Only add an additional archive.today link icon for normal links.
      if ( $linkVariant == 'normal' ) {
        $archiveTodayAttribs = [
          'rel' => $attribs['rel'],
          'href' => 'https://archive.today/' . $url,
        ];
        if (isset($attribs['target'])) {
          $archiveTodayAttribs['target'] = $attribs['target'];
        }

        // TODO: The class here may need to change in the event the CSS
        // already used for archive.org links doesn't work here, or there's
        // something beyond the CSS using it.
        $link .= '<sup class="ext-link-to-archive" title="Link to archive.today search">';
        // TODO: This image path needs fixed too.
        $link .= HTML::rawElement('a', $archiveTodayAttribs, '<img decoding="async" loading="lazy" src="/w/images/TODO/FIX/THIS/PATH/Archive_Today_logo.png" width="13" height="15" alt="archive.org" />');
        $link .= '</sup>';
      }

      // We need to return false if we want to modify the HTML of external links
      return false;
    }

    return null;
  }
}
