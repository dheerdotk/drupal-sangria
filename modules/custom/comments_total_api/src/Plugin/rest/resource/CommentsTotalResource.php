<?php

namespace Drupal\comments_total_api\Plugin\rest\resource;
 
use Drupal\rest\Plugin\ResourceBase;
use Drupal\rest\ResourceResponse;
use Psr\Log\LoggerInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;

error_reporting(E_ALL);
ini_set('display_errors', TRUE);
ini_set('display_startup_errors', TRUE);


/**
 * Provides a Total Comments Count Resource
 *
 * @RestResource(
 *   id = "total_comments_count",
 *   label = @Translation("Total Comments Count Resource"),
 *   uri_paths = {
 *     "canonical" = "/total_comments_count/total_comments_count",
 *     "create" = "/total_comments_count/total_comments_count"
 *   }
 * )
 */
 
class CommentsTotalResource extends ResourceBase {
    /**
   * Responds to entity GET requests.
   * @return \Drupal\rest\ResourceResponse
   */

  public function get() {
   
    $database = \Drupal::database();
    $entity_id = \Drupal::request()->query->get('entity_id');
    $query = $database->query("SELECT * from  node__field_total_comments WHERE entity_id='".$entity_id."'");
    $result = $query->fetchAll();
    return new JsonResponse($result);
  }

  public function post($data) {
    
    $entity_id = $data['entity_id'][0]['target_id'];
    $success ="";
    $database = \Drupal::database();

    if($entity_id){
      $query = $database->query("SELECT * from {node__field_total_comments} where entity_id ='".$entity_id."'");
      $res_node__field_total_comments = $query->fetchAll();
      
      if($res_node__field_total_comments){
        $result = $database->update('node__field_total_comments')
        ->expression('field_total_comments_value', 'field_total_comments_value + 1')
        ->condition('entity_id', $entity_id, '=')
        ->execute();
        if($result)
          $success= "Total comments count updated successfully";
      }
    }
    return new JsonResponse(['message' => $success]);
  }

}