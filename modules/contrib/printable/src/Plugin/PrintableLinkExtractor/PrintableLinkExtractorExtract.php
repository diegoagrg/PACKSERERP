<?php

namespace Drupal\printable\Plugin\PrintableLinkExtractor;

use Wa72\HtmlPageDom\HtmlPageCrawler;

/**
 * Link extractor.
 *
 * @PrintableLinkExtractor(
 *    id = "extract",
 *    module = "printable",
 *    title = @Translation("Extract (show the URL in brackets)"),
 *    description = @Translation("Show the URL in brackets"),
 *    weight = 0,
 *  )
 */
class PrintableLinkExtractorExtract extends PrintableLinkExtractorBase implements PrintableLinkExtractorInterface {

  /**
   * {@inheritdoc}
   */
  public function process(HtmlPageCrawler $anchor, $index) {
    $href = $anchor->attr('href');
    $url = $this->urlFromHref($href);
    $anchor->append(' (' . $url->toString() . ')');
  }

}
