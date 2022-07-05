<?php

namespace Drupal\flag\Entity\Storage;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\Sql\SqlContentEntityStorage;
use Drupal\Core\Session\AccountInterface;

/**
 * Default SQL flagging storage.
 */
class FlaggingStorage extends SqlContentEntityStorage implements FlaggingStorageInterface {

  /**
   * Stores loaded flags per user, entity type and IDs.
   *
   * @var array
   */
  protected $flagIdsByEntity = [];

  /**
   * Stores global flags per entity type and IDs.
   * @var array
   */
  protected $globalFlagIdsByEntity = [];

  /**
   * {@inheritdoc}
   */
  public function resetCache(array $ids = NULL) {
    parent::resetCache($ids);
    $this->flagIdsByEntity = [];
    $this->globalFlagIdsByEntity = [];
  }

  /**
   * {@inheritdoc}
   */
  public function loadIsFlagged(EntityInterface $entity, AccountInterface $account, $session_id = NULL) {
    if ($account->isAnonymous() && is_null($session_id)) {
      throw new \LogicException('Anonymous users must be identified by session_id');
    }

    $flag_ids = $this->loadIsFlaggedMultiple([$entity], $account, $session_id);
    return $flag_ids[$entity->id()];
  }

  /**
   * {@inheritdoc}
   */
  public function loadIsFlaggedMultiple(array $entities, AccountInterface $account, $session_id = NULL) {
    if ($account->isAnonymous() && is_null($session_id)) {
      throw new \LogicException('Anonymous users must be identified by session_id');
    }

    // Set a dummy value for $session_id for an authenticated user so that we
    // can use it as a key in the cache array.
    if (!$account->isAnonymous()) {
      $session_id = 0;
    }

    $flag_ids_by_entity = [];

    if (!$entities) {
      return $flag_ids_by_entity;
    }

    // All entities must be of the same type, get the entity type from the
    // first.
    $entity_type_id = reset($entities)->getEntityTypeId();
    $ids_to_load = [];

    // Loop over all requested entities, if they are already in the loaded list,
    // get then from there, merge the global and per-user flags together.
    foreach ($entities as $entity) {
      if (isset($this->flagIdsByEntity[$account->id()][$session_id][$entity_type_id][$entity->id()])) {
        $flag_ids_by_entity[$entity->id()] = array_merge($this->flagIdsByEntity[$account->id()][$session_id][$entity_type_id][$entity->id()], $this->globalFlagIdsByEntity[$entity_type_id][$entity->id()]);
      }
      else {
        $ids_to_load[$entity->id()] = [];
      }
    }

    // If there are no entities that need to be loaded, return the list.
    if (!$ids_to_load) {
      return $flag_ids_by_entity;
    }

    // Initialize the loaded lists with the missing ID's as an empty array.
    if (!isset($this->flagIdsByEntity[$account->id()][$session_id][$entity_type_id])) {
      $this->flagIdsByEntity[$account->id()][$session_id][$entity_type_id] = [];
    }
    if (!isset($this->globalFlagIdsByEntity[$entity_type_id])) {
      $this->globalFlagIdsByEntity[$entity_type_id] = [];
    }
    $this->flagIdsByEntity[$account->id()][$session_id][$entity_type_id] += $ids_to_load;
    $this->globalFlagIdsByEntity[$entity_type_id] += $ids_to_load;
    $flag_ids_by_entity += $ids_to_load;

    // Directly query the table to avoid he overhead of loading the content
    // entities.
    $query = $this->database->select('flagging', 'f')
      ->fields('f', ['entity_id', 'flag_id', 'global'])
      ->condition('entity_type', $entity_type_id)
      ->condition('entity_id', array_keys($ids_to_load), 'IN');

    // The flagging must either match the user or be global.
    $user_or_global_condition = $query->orConditionGroup()
      ->condition('global', 1);
    if ($account->isAnonymous()) {
      $uid_and_session_condition = $query->andConditionGroup()
        ->condition('uid', $account->id())
        ->condition('session_id', $session_id);
      $user_or_global_condition->condition($uid_and_session_condition);
    }
    else {
      $user_or_global_condition->condition('uid', $account->id());
    }

    $result = $query
      ->condition($user_or_global_condition)
      ->execute();

    // Loop over all results, put them in the cached list and the list that will
    // be returned.
    foreach ($result as $row) {
      if ($row->global) {
        $this->globalFlagIdsByEntity[$entity_type_id][$row->entity_id][$row->flag_id] = $row->flag_id;
      }
      else {
        $this->flagIdsByEntity[$account->id()][$session_id][$entity_type_id][$row->entity_id][$row->flag_id] = $row->flag_id;
      }
      $flag_ids_by_entity[$row->entity_id][$row->flag_id] = $row->flag_id;
    }

    return $flag_ids_by_entity;
  }

  /**
   * {@inheritdoc}
   */
  protected function doPostSave(EntityInterface $entity, $update) {
    parent::doPostSave($entity, $update);
    
    /*Custom code added on 01-Jul-2022*/
    $database = \Drupal::database();
    if($entity->bundle()=="bookmark")
    {
        $query = $database->query("SELECT * from {node__field_total_bookmarks} where entity_id ='".$entity->get('entity_id')->value."'");
        $res_bookmark = $query->fetchAll();
        if($res_bookmark)
        {
          $result = $database->update('node__field_total_bookmarks')
                    ->expression('field_total_bookmarks_value', 'field_total_bookmarks_value + 1')
                    ->condition('entity_id', $entity->get('entity_id')->value, '=')
                    ->execute();
        }
        else
        {
          $result = $database->insert('node__field_total_bookmarks')
                    ->fields(['bundle', 'entity_id', 'revision_id', 'langcode', 'delta', 'field_total_bookmarks_value'])
                    ->values([
                      'bundle' => strtolower(node_get_type_label(\Drupal::entityTypeManager()->getStorage('node')->load($entity->get('entity_id')->value))),
                      'entity_id' => $entity->get('entity_id')->value,
                      'revision_id' =>\Drupal::entityTypeManager()->getStorage('node')->getLatestRevisionId($entity->get('entity_id')->value),
                      'langcode' => \Drupal::languageManager()->getCurrentLanguage()->getId(),
                      'delta' => 0,
                      'field_total_bookmarks_value' => 1  ])
                    ->execute();
        }
    } 
    if($entity->bundle()=="like")
    {
        $query = $database->query("SELECT * from {node__field_total_list_votes} where entity_id ='".$entity->get('entity_id')->value."'");
        $res_like = $query->fetchAll();
        if($res_like)
        {
          $result = $database->update('node__field_total_list_votes')
                    ->expression('field_total_list_votes_value', 'field_total_list_votes_value + 1')
                    ->condition('entity_id', $entity->get('entity_id')->value, '=')
                    ->execute();
        }
        else
        {
          $result = $database->insert('node__field_total_list_votes')
                    ->fields(['bundle', 'entity_id', 'revision_id', 'langcode', 'delta', 'field_total_list_votes_value'])
                    ->values([
                      'bundle' => strtolower(node_get_type_label(\Drupal::entityTypeManager()->getStorage('node')->load($entity->get('entity_id')->value))),
                      'entity_id' => $entity->get('entity_id')->value,
                      'revision_id' =>\Drupal::entityTypeManager()->getStorage('node')->getLatestRevisionId($entity->get('entity_id')->value),
                      'langcode' => \Drupal::languageManager()->getCurrentLanguage()->getId(),
                      'delta' => 0,
                      'field_total_list_votes_value' => 1  ])
                    ->execute();
        }
    } 
    if($entity->bundle()=="share")
    {
        $query = $database->query("SELECT * from {node__field_total_shares} where entity_id ='".$entity->get('entity_id')->value."'");
        $res_share = $query->fetchAll();
        if($res_share)
        {
          $result = $database->update('node__field_total_shares')
                    ->expression('field_total_shares_value', 'field_total_shares_value + 1')
                    ->condition('entity_id', $entity->get('entity_id')->value, '=')
                    ->execute();
        }
        else
        {
          $result = $database->insert('node__field_total_shares')
                    ->fields(['bundle', 'entity_id', 'revision_id', 'langcode', 'delta', 'field_total_shares_value'])
                    ->values([
                      'bundle' => strtolower(node_get_type_label(\Drupal::entityTypeManager()->getStorage('node')->load($entity->get('entity_id')->value))),
                      'entity_id' => $entity->get('entity_id')->value,
                      'revision_id' =>\Drupal::entityTypeManager()->getStorage('node')->getLatestRevisionId($entity->get('entity_id')->value),
                      'langcode' => \Drupal::languageManager()->getCurrentLanguage()->getId(),
                      'delta' => 0,
                      'field_total_shares_value' => 1  ])
                    ->execute();
        }
    }  
    
    if($entity->bundle()=="like_comment")
    {
        $query = $database->query("SELECT * from {comment__field_total_comments_like} where entity_id ='".$entity->get('entity_id')->value."'");
        $res_like = $query->fetchAll();
        if($res_like)
        {
          $result = $database->update('comment__field_total_comments_like')
                    ->expression('field_total_comments_like_value', 'field_total_comments_like_value + 1')
                    ->condition('entity_id', $entity->get('entity_id')->value, '=')
                    ->execute();
        }
        else
        {
          $result = $database->insert('comment__field_total_comments_like')
                    ->fields(['bundle', 'entity_id', 'revision_id', 'langcode', 'delta', 'field_total_comments_like_value'])
                    ->values([
                      'bundle' => strtolower(node_get_type_label(\Drupal::entityTypeManager()->getStorage('node')->load($entity->get('entity_id')->value))),
                      'entity_id' => $entity->get('entity_id')->value,
                      'revision_id' =>\Drupal::entityTypeManager()->getStorage('node')->getLatestRevisionId($entity->get('entity_id')->value),
                      'langcode' => \Drupal::languageManager()->getCurrentLanguage()->getId(),
                      'delta' => 0,
                      'field_total_comments_like_value' => 1  ])
                    ->execute();
        }
    } 
    /*END*/
    
    // After updating or creating a flagging, add it to the cached flagging by entity if already in static cache.
    if ($entity->get('global')->value) {
      // If the global flags by entity for this entity have already been cached, then add the newly created flagging.
      if (isset($this->globalFlagIdsByEntity[$entity->get('entity_type')->value][$entity->get('entity_id')->value])) {
        $this->globalFlagIdsByEntity[$entity->get('entity_type')->value][$entity->get('entity_id')->value][$entity->get('flag_id')->value] = $entity->get('flag_id')->value;
      }
    }
    else {
      // If the flags by entity for this entity/user have already been cached, then add the newly created flagging.
      if (isset($this->flagIdsByEntity[$entity->get('uid')->target_id][$entity->get('entity_type')->value][$entity->get('entity_id')->value])) {
        $this->flagIdsByEntity[$entity->get('uid')->target_id][$entity->get('entity_type')->value][$entity->get('entity_id')->value][$entity->get('flag_id')->value] = $entity->get('flag_id')->value;
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  protected function doDelete($entities) {
    /*Custom code added on 01-Jul-2022*/
    $database = \Drupal::database();
    foreach ($entities as $entity) { 
      if($entity->bundle()=="bookmark")
      {
        $query = $database->query("SELECT * from {node__field_total_bookmarks} where entity_id ='".$entity->get('entity_id')->value."'");
        $res_bookmark = $query->fetchAll();
        if($res_bookmark[0]->field_total_bookmarks_value>0)
        {
          $result = $database->update('node__field_total_bookmarks')
          ->expression('field_total_bookmarks_value', 'field_total_bookmarks_value - 1')
          ->condition('entity_id', $entity->get('entity_id')->value, '=')
          ->execute();
        }
      } 
      if($entity->bundle()=="like")
      {
        $query = $database->query("SELECT * from {node__field_total_list_votes} where entity_id ='".$entity->get('entity_id')->value."'");
        $res_like = $query->fetchAll();
        if($res_like[0]->field_total_list_votes_value>0)
        {
          $result = $database->update('node__field_total_list_votes')
          ->expression('field_total_list_votes_value', 'field_total_list_votes_value - 1')
          ->condition('entity_id', $entity->get('entity_id')->value, '=')
          ->execute();
        }
      } 
      if($entity->bundle()=="like_comment")
      {
        $query = $database->query("SELECT * from {comment__field_total_comments_like} where entity_id ='".$entity->get('entity_id')->value."'");
        $res_like = $query->fetchAll();
        if($res_like[0]->field_total_comments_like_value>0)
        {
          $result = $database->update('comment__field_total_comments_like')
          ->expression('field_total_comments_like_value', 'field_total_comments_like_value - 1')
          ->condition('entity_id', $entity->get('entity_id')->value, '=')
          ->execute();
        }
      } 
    }
    /*END*/
    parent::doDelete($entities);

    /** @var \Drupal\Core\Entity\ContentEntityInterface[] $entities */
    foreach ($entities as $entity) {
      // After deleting a flagging, remove it from the cached flagging by entity if already in static cache.
      if ($entity->get('global')->value) {
        if (isset($this->globalFlagIdsByEntity[$entity->get('entity_type')->value][$entity->get('entity_id')->value][$entity->get('flag_id')->value])) {
          unset($this->globalFlagIdsByEntity[$entity->get('entity_type')->value][$entity->get('entity_id')->value][$entity->get('flag_id')->value]);
        }
      }
      else {
        if (isset($this->flagIdsByEntity[$entity->get('uid')->target_id][$entity->get('entity_type')->value][$entity->get('entity_id')->value][$entity->get('flag_id')->value])) {
          unset($this->flagIdsByEntity[$entity->get('uid')->target_id][$entity->get('entity_type')->value][$entity->get('entity_id')->value][$entity->get('flag_id')->value]);
        }
      }
    }
  }

}
