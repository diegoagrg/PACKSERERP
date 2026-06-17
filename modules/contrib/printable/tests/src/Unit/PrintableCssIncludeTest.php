<?php

namespace Drupal\Tests\printable\Unit;

use Drupal\Core\Extension\ThemeExtensionList;
use Drupal\Core\Extension\ThemeHandlerInterface;
use Drupal\Tests\UnitTestCase;
use Drupal\printable\PrintableCssInclude;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;

/**
 * Tests the print format plugin.
 */
#[Group('Printable')]
#[CoversClass(PrintableCssInclude::class)]
class PrintableCssIncludeTest extends UnitTestCase {

  /**
   * Tests getting the plugin label from the plugin.
   *
   * @param string $include
   *   The CSS include path to test.
   * @param string $expected
   *   The expected result.
   *
   * @covers \Drupal\printable\PrintableCssInclude::getCssIncludePath
   * @dataProvider providerTestGetCssIncludePath
   */
  #[DataProvider('providerTestGetCssIncludePath')]
  public function testGetCssIncludePath(string $include, string $expected): void {
    $config = $this->getConfigFactoryStub(['printable.settings' => ['css_include' => $include]]);

    $theme_info = [
      'bartik' => (object) ['uri' => 'core/themes/bartik/bartik.info.yml'],
    ];
    $theme_handler = $this->createMock(ThemeHandlerInterface::class);
    $theme_handler->expects($this->any())
      ->method('listInfo')
      ->willReturn($theme_info);

    $themeExtensionList = $this->createMock(ThemeExtensionList::class);
    $themeExtensionList->expects($this->any())
      ->method('getPath')
      ->willReturnMap([
        ['bartik', 'core/themes/bartik'],
        ['foobar', ''],
      ]);
    $css_include = new PrintableCssInclude($config, $themeExtensionList);
    $actual = $css_include->getCssIncludePath();

    $this->assertEquals($expected, $actual);
  }

  /**
   * Data provider for testGetCssIncludePath().
   *
   * @return array
   *   The data for the test.
   */
  public static function providerTestGetCssIncludePath(): array {
    return [
      ['[theme:bartik]/css/test.css', 'core/themes/bartik/css/test.css'],
      ['[theme:foobar]/css/test.css', '/css/test.css'],
      ['foo/bar/css/test.css', 'foo/bar/css/test.css'],
    ];
  }

}
