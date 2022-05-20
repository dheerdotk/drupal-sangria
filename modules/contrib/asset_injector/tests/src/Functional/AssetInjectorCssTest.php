<?php

namespace Drupal\Tests\asset_injector\Functional;

use Drupal\Tests\BrowserTestBase;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Class AssetInjectorCssTest.
 *
 * @package Drupal\Tests\asset_injector\Functional
 *
 * @group asset_injector
 */
class AssetInjectorCssTest extends BrowserTestBase {

  use StringTranslationTrait;

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * Modules to install.
   *
   * @var array
   */
  protected static $modules = ['asset_injector', 'toolbar', 'block'];

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();

    $this->drupalPlaceBlock('local_tasks_block');
    $this->drupalPlaceBlock('page_title_block');
    $this->drupalPlaceBlock('system_messages_block');
  }

  /**
   * Tests a user without permissions gets access denied.
   *
   * @throws \Exception
   */
  public function testCssPermissionDenied() {
    $account = $this->drupalCreateUser();
    $this->drupalLogin($account);
    $this->drupalGet('admin/config/development/asset-injector/css');
    $this->assertSession()->statusCodeEquals(403);
  }

  /**
   * Tests a user WITH permission has access.
   *
   * @throws \Exception
   */
  public function testCssPermissionGranted() {
    $account = $this->drupalCreateUser(['administer css assets injector']);
    $this->drupalLogin($account);
    $this->drupalGet('admin/config/development/asset-injector/css');
    $this->assertSession()->statusCodeEquals(200);
  }

  /**
   * Test a created css injector is added to the page and the css file exists.
   *
   * @throws \Exception
   */
  public function testCssInjector() {
    $this->testCssPermissionGranted();
    $this->drupalGet('admin/config/development/asset-injector/css/add');
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->pageTextContains($this->t('Code'));
    $this->submitForm([
      'label' => $this->t('Blocks'),
      'id' => $this->t('blocks'),
      'code' => '.block {border:1px solid black;}',
    ], $this->t('Save'));

    $this->getSession()->getPage()->hasContent('asset_injector/css/blocks');
    /** @var \Drupal\asset_injector\Entity\AssetInjectorCss $asset */
    foreach (asset_injector_get_assets(NULL, ['asset_injector_css']) as $asset) {
      $path = parse_url(file_create_url($asset->internalFileUri()), PHP_URL_PATH);
      $path = str_replace(base_path(), '/', $path);

      $this->drupalGet($path);
      $this->assertSession()->statusCodeEquals(200);
    }
  }

  /**
   * Tests if the save and continue button works accurately.
   *
   * @throws \Exception
   */
  public function testSaveContinue() {
    $page = $this->getSession()->getPage();
    $this->testCssPermissionGranted();
    $this->drupalGet('admin/config/development/asset-injector/css/add');
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->pageTextContains($this->t('Code'));
    $page->fillField('Label', 'test save continue');
    $page->fillField('Machine-readable name', 'test_save_continue');
    $page->fillField('Code', '.block{}');
    $page->pressButton('Save and Continue Editing');
    $this->assertSession()
      ->pageTextContains('Created the test save continue Asset Injector');
    $this->assertSession()
      ->addressEquals('admin/config/development/asset-injector/css/test_save_continue');
  }

}
