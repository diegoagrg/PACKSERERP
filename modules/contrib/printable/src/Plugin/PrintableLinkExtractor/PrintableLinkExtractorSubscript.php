<?php

namespace Drupal\printable\Plugin\PrintableLinkExtractor;

/**
 * Link extractor.
 *
 * @PrintableLinkExtractor(
 *      id = "subscript",
 *      module = "printable",
 *      title = @Translation("Subscript"),
 *      description = @Translation("Make the reference a subscript"),
 *      weight = 0
 *    )
 */
class PrintableLinkExtractorSubscript extends PrintableLinkExtractorBase implements PrintableLinkExtractorInterface {

  /**
   * {@inheritdoc}
   */
  public function process($anchor, $index) {
    $anchor->append('<sub>[' . $index . ']</sub>');
  }

}
