<?php

namespace Drupal\user_points_api\Plugin\rest\resource;
 
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
 * Provides a User Points Resource
 *
 * @RestResource(
 *   id = "user_points_resource",
 *   label = @Translation("User Points Resource"),
 *   uri_paths = {
 *     "canonical" = "/user_points_api/user_points_resource",
 *     "create" = "/user_points_api/user_points_resource"
 *   }
 * )
 */
 
class UserPointsResource extends ResourceBase {
    /**
   * Responds to entity GET requests.
   * @return \Drupal\rest\ResourceResponse
   */
//   public function post() {
//     $response = ['message' => 'Hello, this is a rest service'];
//     return new ResourceResponse($response);
// }

  public function get() {
   
    $database = \Drupal::database();
    $query = $database->query("SELECT * from flagging");
    $result = $query->fetchAll();
    return new JsonResponse($result);
  }

  public function post(Request $request) {
   
    $json_string = \Drupal::request()->getContent();
    $decoded = \Drupal\Component\Serialization\Json::decode($json_string);
    $success ="";
    $database = \Drupal::database();
    $uid = \Drupal::currentUser()->id();
    switch($decoded["entityType"]){
      case "recipes" : 
        $entity_type = "Publish text recipe";
        break;
      case "health-journeys" : 
        $entity_type = "Publish health journey";
        break;
      case "self-cure-blogs" : 
        $entity_type = "Publish Blog";
        break;
      case "articles" : 
        $entity_type = "Publish articles";
        break;
      case "question-answers" : 
        $entity_type = "Publish question";
        break;
      case "answers": 
        $entity_type = "Publish answer";
        break;
      default :
        $entity_type = "";
        break;
    }
    $query = $database->query("SELECT * from {action_lookup} where name='".$entity_type."' and active='1'");
    $res_lookup = $query->fetchAll();

    
    if($res_lookup)
    {
      $query = $database->query("SELECT * from {user_points} where uid='".$uid."' and entity_id='".$decoded["entity_id"]."' and actionid='".$res_lookup[0]->id."'");
      $res_points = $query->fetchAll();
      if($decoded['isPublished']==1)
      {
        $points = $res_lookup[0]->points;
      }
      elseif($decoded['isPublished']==0){
        $points = 0;
      }
      if($res_points)
      {
        $result = $database->update('user_points')
        ->fields([
          'points' => $points,
        ])
        ->condition('id', $res_points[0]->id, '=')
        ->execute();
        if($result)
          $success= "User points updated successfully";
      }
      else
      {
        if($decoded['isPublished']==1)
        {  
          //UnLike give,Recipe,Dheer,Avitha,-5,date,0
          //echo "action id=".$result[0]->id;
          // $fields = ['actionid' => $result[0]->id, 'entity_type' => '0', 'entity_id' => '0', 'uid' => '2', 'points' => '1500' , 'created' => '2022-05-17 062:00:00'];
          // $id = $database->insert('user_points')  ->fields($fields)  ->execute();
      
            $result = $database->insert('user_points')
            ->fields(['actionid', 'entity_id', 'uid', 'points', 'created', 'owner'])
            ->values([
              'actionid' => $res_lookup[0]->id,
              'entity_id' => $decoded["entity_id"],
              'uid' => $uid ,
              'points' => $res_lookup[0]->points,
              'created' => $decoded["createdDate"]." ".$decoded["createdTime"],
              'owner' => 1  ])
            ->execute();
              if($result)
                $success= "User points added successfully";
        }
      }
    }
    return new JsonResponse(['message' => $success]);
  }

}