<?php

namespace Drupal\printable\Plugin\PrintableLinkExtractor;

/**
 * Link extractor.
 *
 * @PrintableLinkExtractor(
 *     id = "remove",
 *     module = "printable",
 *     title = @Translation("Remove (delete the href=)"),
 *     description = @Translation("Delete the href attribute"),
 *     weight = 0,
 *   )
 */
class PrintableLinkExtractorRemove extends PrintableLinkExtractorBase implements PrintableLinkExtractorInterface {

  /**
   * {@inheritdoc}
   */
  public function process($anchor, $index) {
    $anchor->removeAttribute('href');
  }

}
