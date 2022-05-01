<?php

namespace Drupal\Tests\publication_date\Functional;

use Drupal\Core\Field\Entity\BaseFieldOverride;
use Drupal\Core\Url;
use Drupal\field\Entity\FieldConfig;
use Drupal\node\Entity\Node;
use Drupal\node\Entity\NodeType;
use Drupal\Tests\BrowserTestBase;

/**
 * Tests the integration on node forms.
 *
 * @group publication_date
 */
class PublicationDatePreExistingContentTest extends BrowserTestBase {

  use \Drupal\Tests\node\Traits\ContentTypeCreationTrait;

  /**
   * Modules to enable.
   */
  protected static $modules = [
    'node',
  ];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  protected function setUp() {
    parent::setUp();

    // Unpublished by default.
    $nodeType = NodeType::create([
      'type' => 'test1',
      'name' => 'Test Unpublished'
    ]);
    $nodeType->save();
    $entity = BaseFieldOverride::create([
      'field_name' => 'status',
      'entity_type' => 'node',
      'bundle' => 'test1',
    ]);
    $entity->setDefaultValue(FALSE)->save();

    $account = $this->drupalCreateUser([
      'create test1 content',
      'edit own test1 content',
      'administer nodes',
    ]);
    $this->drupalLogin($account);
  }

  public function testWithExistingContent() {
    // Unpublished by default
    /** @var \Drupal\node\NodeInterface $node */
    $node = Node::create(['type' => 'test1', 'title' => $this->randomString()]);
    $node->save();

    \Drupal::service('module_installer')->install(['publication_date']);

    // Load node from database again after installation.
    $node = Node::load($node->id());
    $node->save();

    $this->assertEqual($node->published_at->value, PUBLICATION_DATE_DEFAULT);
    $node->setPublished()->save();

    $this->assertNotNull($node->published_at->value);
    $this->assertNotEqual($node->published_at->value, PUBLICATION_DATE_DEFAULT);
  }

}
