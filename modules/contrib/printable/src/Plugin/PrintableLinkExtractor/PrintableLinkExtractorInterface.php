<?php

namespace Drupal\printable\Plugin\PrintableLinkExtractor;

use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Wa72\HtmlPageDom\HtmlPageCrawler;

/**
 * Defines an interface for extracting links from a string of HTMl.
 */
interface PrintableLinkExtractorInterface extends ContainerFactoryPluginInterface {

  /**
   * Highlight hrefs from links in the given HTML string.
   *
   * @param \Wa72\HtmlPageDom\HtmlPageCrawler $anchor
   *   An anchor that needs processing.
   * @param int $index
   *   The index of this link.
   */
  public function process(HtmlPageCrawler $anchor, $index);

  /**
   * List the links at the bottom of page.
   *
   * @param string $content
   *   The HTML string which has links present.
   *
   * @return string
   *   The HTML string, containing links.
   */
  public function listAttribute($content);

}
