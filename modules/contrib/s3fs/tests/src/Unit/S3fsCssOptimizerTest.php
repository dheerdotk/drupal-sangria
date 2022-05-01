<?php

namespace Drupal\Tests\s3fs\Unit;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Tests\UnitTestCase;
use Drupal\s3fs\Asset\S3fsCssOptimizer;

require_once __DIR__ . '/../../fixtures/S3fsCssOptimizerMock.php';

/**
 * Tests the S3fsCssOptimizer.
 *
 * @group s3fs
 */
class S3fsCssOptimizerTest extends UnitTestCase {

  public function setUp() {
    parent::setUp();

    // D9.3 we need to mock the FileUrlGenerator service.
    if (interface_exists('\Drupal\Core\File\FileUrlGeneratorInterface')) {
      \Drupal::unsetContainer();
      $container = new ContainerBuilder();
      $fileUrlGeneratorServiceMock = $this->getMockBuilder('\Drupal\Core\File\FileUrlGeneratorInterface')
        ->disableOriginalConstructor()
        ->getMock();
      $fileUrlGeneratorServiceMock->expects($this->any())
        ->method('generateAbsoluteString')
        ->will(
          $this->returnCallback(function($arg) {
            return $arg;
          })
        );
      $container->set('file_url_generator', $fileUrlGeneratorServiceMock);
      \Drupal::setContainer($container);
    }

  }

  /**
   * Test general asset link re-writing.
   */
  public function testRewriteUri() {

    $configFactory = $this->getConfigFactoryStub([
      's3fs.settings' => [
        'use_https' => FALSE,
        'use_cssjs_host' => FALSE,
        'cssjs_host' => '',
      ],
    ]);

    $cssOptimizer = new S3fsCssOptimizer($configFactory);
    $cssOptimizer->rewriteFileURIBasePath = '';

    $this->assertEquals(
      'url(//www.example.org/test/file.txt)',
      $cssOptimizer->rewriteFileURI(['', '/test/file.txt'])
    );
    $this->assertEquals(
      'url(//www.example.org/test/file.txt)',
      $cssOptimizer->rewriteFileURI(['', '/core/../test/file.txt'])
    );
    $this->assertEquals(
      'url(//www.example.org/test/file.txt)',
      $cssOptimizer->rewriteFileURI(['', '/core/data/../../test/file.txt'])
    );

  }

  /**
   * Test asset links generated using HTTPS://.
   */
  public function testRewriteUriAlwaysHttps() {

    $configFactory = $this->getConfigFactoryStub([
      's3fs.settings' => [
        'use_https' => TRUE,
        'use_cssjs_host' => FALSE,
        'cssjs_host' => '',
      ],
    ]);

    $cssOptimizer = new S3fsCssOptimizer($configFactory);
    $cssOptimizer->rewriteFileURIBasePath = '';

    $this->assertEquals(
      'url(https://www.example.org/test/file.txt)',
      $cssOptimizer->rewriteFileURI(['', '/test/file.txt'])
    );
  }

  /**
   * Test asset links with custom host.
   */
  public function testRewriteUriCustomCssHost() {

    $configFactory = $this->getConfigFactoryStub([
      's3fs.settings' => [
        'use_https' => FALSE,
        'use_cssjs_host' => TRUE,
        'cssjs_host' => 'test.example.org',
      ],
    ]);

    $cssOptimizer = new S3fsCssOptimizer($configFactory);
    $cssOptimizer->rewriteFileURIBasePath = '';

    $this->assertEquals(
      'url(//test.example.org/test/file.txt)',
      $cssOptimizer->rewriteFileURI(['', '/test/file.txt'])
    );
  }

}
