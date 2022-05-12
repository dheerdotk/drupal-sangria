<?php

namespace Drupal\searchapi_nodeview_count\Plugin\search_api\processor;

use Drupal\search_api\Datasource\DatasourceInterface;
use Drupal\search_api\Item\ItemInterface;
use Drupal\search_api\Processor\ProcessorPluginBase;
use Drupal\search_api\Processor\ProcessorProperty;

/**
 * Adds the item's URL to the indexed data.
 *
 * @SearchApiProcessor(
 *   id = "node_viewscount",
 *   label = @Translation("node's views count field"),
 *   description = @Translation("Adds the node views count to the indexed data."),
 *   stages = {
 *     "add_properties" = 0,
 *   },
 *   locked = true,
 *   hidden = true,
 * )
 */
class NodeViewsCount extends ProcessorPluginBase {

  /**
   * {@inheritdoc}
   */
  public function getPropertyDefinitions(DatasourceInterface $datasource = NULL) {
    $properties = [];

    if (!$datasource) {
      $definition = [
        'label' => $this->t('Node Views Count'),
        'description' => $this->t('Field used to count the number of Node Views'),
        'type' => 'string',
        'processor_id' => $this->getPluginId(),
      ];
      $properties['node_viewscount'] = new ProcessorProperty($definition);
    }

    return $properties;
  }

  /**
   * {@inheritdoc}
   */
  public function addFieldValues(ItemInterface $item) {
    
    
  	$entity = $item->getOriginalObject()->getValue();

    // Only run for node items.
    if (!$entity->getEntityType()->id() == 'node') {
      return;
    }
    //get node's user views count using statistics module
    $result = \Drupal::service('statistics.storage.node')->fetchView($entity->id());
    if($result) {
      $views_count = $result->getTotalCount();
    }
    $fields = $item->getFields(FALSE);
    $fields = $this->getFieldsHelper()
      ->filterForPropertyPath($fields, NULL, 'node_viewscount');
    foreach ($fields as $field) {
      $field->addValue($views_count);
    }
  }

}
