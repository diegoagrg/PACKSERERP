<?php

namespace Drupal\printable;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Extension\ThemeExtensionList;

/**
 * Helper class for the printable module.
 */
class PrintableCssInclude implements PrintableCssIncludeInterface {

  /**
   * The config factory service.
   *
   * @var \Drupal\Core\Config\ConfigFactory
   */
  protected $configFactory;

  /**
   * The theme extension list service.
   *
   * @var Drupal\Core\Extension\ThemeExtensionList
   */
  protected $themeExtensionList;

  /**
   * Constructs a new PrintableCssInclude object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The configuration factory service.
   * @param \Drupal\Core\Extension\ThemeExtensionList $themeExtensionList
   *   The theme handler service.
   */
  public function __construct(ConfigFactoryInterface $config_factory, ThemeExtensionList $themeExtensionList) {
    $this->configFactory = $config_factory;
    $this->themeExtensionList = $themeExtensionList;
  }

  /**
   * {@inheritdoc}
   */
  public function getCssIncludePath() {
    if ($include_path = $this->configFactory->get('printable.settings')->get('css_include')) {
      if ($token = $this->extractCssIncludeToken($include_path)) {
        [, $theme] = explode(':', trim($token, '[]'));
        $themePath = $this->themeExtensionList->getPath($theme);
        $include_path = str_replace($token, $themePath, $include_path);
      }
      return $include_path;
    }

    return '';
  }

  /**
   * Extract the theme token from a CSS include path.
   *
   * @param string $path
   *   An include path (optionally) with a taken to extract in the form:
   *   "[theme:theme_machine_name]".
   *
   * @return string|null
   *   The extracted token in the form "[theme:theme_machine_name]" or NULL if
   *   no token exists in the string.
   */
  protected function extractCssIncludeToken($path) {
    preg_match('/\w*\[theme:\w+]\w*/', $path, $matches);
    return $matches[0] ?? NULL;
  }

}
