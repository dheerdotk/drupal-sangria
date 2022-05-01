<?php

namespace Drupal\publication_date\Plugin\Field\FieldType;

use Drupal\Core\Field\ChangedFieldItemList;

/**
 * Defines a item list class for publication date fields.
 */
class PublicationDateFieldItemList extends ChangedFieldItemList {

  /**
   * {@inheritdoc}
   */
  public function preSave() {
    if ($this->isEmpty()) {
      $this->applyDefaultValue();
    }
    parent::preSave();
  }

}
