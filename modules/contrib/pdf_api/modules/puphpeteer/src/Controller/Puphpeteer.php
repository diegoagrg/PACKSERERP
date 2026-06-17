<?php

namespace Drupal\puphpeteer\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use NigelCunningham\Puphpeteer\Puppeteer;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a service for invoking Puppeteer.
 */
class Puphpeteer extends ControllerBase implements ContainerInjectionInterface {

  /**
   * The instance.
   *
   * @var \NigelCunningham\Puphpeteer\Puppeteer|null
   */
  static private $instance = NULL;

  /**
   * Constructor.
   */
  public function __construct() {
    if (!self::$instance) {
      $config = $this->config('puphpeteer')->get();
      // @phpstan-ignore-next-line
      self::$instance = new Puppeteer([
        'executable_path' => $config['executable_path'],
        'idle_timeout' => $config['idle_timeout'],
        'read_timeout' => $config['read_timeout'],
        'stop_timeout' => $config['stop_timeout'],
        'logger' => $config['logger'],
        'log_node_console' => $config['log_node_console'],
        'debug' => $config['debug'],
        'log_browser_console' => $config['log_browser_console'],
      ]);
    }
  }

  /**
   * Named constructor.
   */
  public static function construct(ContainerInterface $container) {
    $instance = parent::create($container);
    return $instance;
  }

}
