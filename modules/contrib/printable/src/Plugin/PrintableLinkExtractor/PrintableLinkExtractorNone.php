<?php

namespace Drupal\printable\Plugin\PrintableLinkExtractor;

/**
 * Link extractor.
 *
 * @PrintableLinkExtractor(
 *      id = "none",
 *      module = "printable",
 *      title = @Translation("Unchanged"),
 *      description = @Translation("Leave the reference unmodified"),
 *      weight = 100,
 *    )
 */
class PrintableLinkExtractorNone extends PrintableLinkExtractorBase implements PrintableLinkExtractorInterface {

  /**
   * {@inheritdoc}
   */
  public function process($anchor, $index) {}

}
