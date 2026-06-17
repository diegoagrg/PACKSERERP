<?php

namespace Drupal\printable\Plugin\PrintableLinkExtractor;

use Drupal\Core\Config\ConfigFactory;
use Drupal\Core\Plugin\PluginBase;
use Drupal\Core\Render\MetadataBubblingUrlGenerator;
use Drupal\Core\Url;
use Drupal\path_alias\AliasManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Wa72\HtmlPageDom\HtmlPageCrawler;

/**
 * Link extractor.
 */
abstract class PrintableLinkExtractorBase extends PluginBase implements PrintableLinkExtractorInterface {

  /**
   * Constructs a new PrintableLinkExtractor object.
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    protected HtmlPageCrawler $crawler,
    protected MetadataBubblingUrlGenerator $urlGenerator,
    protected AliasManagerInterface $aliasManager,
    protected ConfigFactory $config,
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration, $plugin_id, $plugin_definition,
      $container->get('printable.dom_crawler'),
      $container->get('url_generator'),
      $container->get('path_alias.manager'),
      $container->get('config.factory'),
    );
  }

  /**
   * Is this link a Printable link?
   *
   * @param string $href
   *   The link target being checked.
   *
   * @todo Make this use routes.
   */
  public function isPrintableLink($href) {
    return preg_match('#/node/[^/]*/(printable)/.*#', $href);
  }

  /**
   * Should include link?
   */
  public function includeLinkInList($href) {
    return (!$this->isPrintableLink($href) ||
      !($this->config->get('printable.settings')
        ->get('exclude_printable_links') ?? TRUE));
  }

  /**
   * {@inheritdoc}
   */
  public function extract($string) {
    $this->crawler->addContent($string);
    $index = 1;
    $this->crawler->filter('a')
      ->each(function (HtmlPageCrawler $anchor) use (&$index) {
        $href = $anchor->attr('href');
        if ($href && $this->includeLinkInList($href)) {
          $this->process($anchor, $index);
          $index++;
        }
      });
    return (string) $this->crawler;
  }

  /**
   * {@inheritdoc}
   */
  public function listAttribute($content) {
    $this->crawler->addContent($content);
    $links = [];
    $this->crawler->filter('a')
      ->each(function (HtmlPageCrawler $anchor) use (&$links) {
        global $base_url;

        $href = $anchor->attr('href');
        if (!$this->includeLinkInList($href)) {
          return;
        }

        try {
          $links[] = $base_url . $this->aliasManager->getAliasByPath($href);
        }
        catch (\Exception $e) {
          try {
            $links[] = $this->urlFromHref($href)->toString();
          }
          catch (\InvalidArgumentException $e) {
            // Document contains invalid URI (eg <a href="javascript:foo()">)
            // & we're not going to add that to printed output.
          }
        }
      });
    $this->crawler->remove();
    return $links;
  }

  /**
   * Generate a URL object given a URL from the href attribute.
   *
   * Tries external URLs first, if that fails it will attempt
   * generation from a relative URL.
   *
   * @param string $href
   *   The URL from the href attribute.
   *
   * @return \Drupal\Core\Url
   *   The created URL object.
   */
  protected function urlFromHref($href) {
    try {
      $url = Url::fromUri($href, ['absolute' => TRUE]);
    }
    catch (\InvalidArgumentException $e) {
      $url = Url::fromUserInput($href, ['absolute' => TRUE]);
    }

    return $url;
  }

  /**
   * Reset the dom crawler.
   */
  public function resetCrawler() {
    $new_instance_name = 'Wa72\HtmlPageDom\HtmlPageCrawler';
    $this->crawler = new $new_instance_name();
  }

}
