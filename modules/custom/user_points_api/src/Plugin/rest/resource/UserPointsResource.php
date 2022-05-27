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

  public function get() {
   
    $database = \Drupal::database();
    $userId = \Drupal::request()->query->get('userId');
    $query = $database->query("SELECT * from  user_achievements WHERE userId='".$userId."'");
    $result = $query->fetchAll();
    return new JsonResponse($result);
  }

  public function post(Request $request) {
    $json_string = \Drupal::request()->getContent();
    $decoded = \Drupal\Component\Serialization\Json::decode($json_string);
    $success ="";
    $database = \Drupal::database();

    $uid = \Drupal::currentUser()->id();
    preg_match('#\((.*?)\)#', $decoded['uid'], $match);
    $give_uid = $match[1];
    switch (true) {

      case ($decoded["entityType"] == 'recipes' && $decoded["youtubeLink"]!=''):
        $entity_type = array("'Publish video recipe'");
        break;

      case ($decoded["entityType"] == 'recipes'):
        $entity_type = array("'Publish text recipe'");
        break;

      case ($decoded["entityType"] == 'health-journeys'):
        $entity_type = array("'Publish health journey'");
        break;
    
      case ($decoded["entityType"] == 'self-cure-blogs'):
        $entity_type = array("'Publish Blog'");
        break;

      case ($decoded["entityType"] == "articles") : 
        $entity_type = array("'Publish articles'");
        break;

      case ($decoded["entityType"] == "question-answers") : 
        $entity_type = array("'Publish question'");
        break;

      case ($decoded["entityType"] == "answers"): 
        $entity_type = array("'Publish answer'");
        break;

      // case ($decoded["entityType"] == "comment"): 
      //   $entity_type = array("'Publish comment give'", "'Publish comment receive'");
      //   break;

      default :
        $entity_type = "";
        break;
    
    }
    
    $query = $database->query("SELECT * from {action_lookup} where name IN (".implode(",",$entity_type).") and active='1'");
    $res_lookup = $query->fetchAll();
    
    if($res_lookup)
    {
      // if($decoded["entityType"] == "comment"){                
      //   $user_id = $give_uid;
      // }
      // else{
      //   $user_id = $uid;
      // }
      $user_id = $give_uid;  
      $query = $database->query("SELECT * from {user_points} where uid='".$user_id."' and entity_id='".$decoded["entity_id"]."' and actionid='".$res_lookup[0]->id."'");
      $res_points = $query->fetchAll();
      if($res_points)
      {
        // if($decoded["entityType"] == "comment"){
        //   $publishQuery = $database->query("SELECT field_is_published_value from {node__field_is_published} where entity_id='".$decoded["entity_id"]."'");
        // }
        // else{
             //$publishQuery = $database->query("SELECT status as field_is_published_value from {node__field_is_published} where entity_id='".$decoded["entity_id"]."'");
        //}
        $publishQuery = $database->query("SELECT field_is_published_value from {node__field_is_published} where entity_id='".$decoded["entity_id"]."'");
        $res_publish = $publishQuery->fetchAll();
        if($decoded['isPublished']==1 && $res_publish[0]->field_is_published_value!=1)
        {
            // if($decoded["entityType"] == "comment"){
            //   foreach ($res_lookup as $action){
            //     if($action->name == "Publish comment give"){                
            //       $user_id = $give_uid;
            //       $owner = 0;
                  
            //     }
            //     else{
            //       $user_id = $uid;
            //       $owner = 1;
            //     }
            //     $result = $database->insert('user_points')
            //               ->fields(['actionid', 'entity_id', 'uid', 'points', 'created', 'owner'])
            //               ->values([
            //                 'actionid' => $action->id,
            //                 'entity_id' => $decoded["entity_id"],
            //                 'uid' => $user_id ,
            //                 'points' => $action->points,
            //                 'created' => date("Y-m-d H:i:s", time()),
            //                 'owner' => $owner  ])
            //               ->execute();
            //   }
            // }
            //else{
              $points = $res_lookup[0]->points;
              $created = $decoded["createdDate"]." ".$decoded["createdTime"];
              $result = $database->insert('user_points')
                        ->fields(['actionid', 'entity_id', 'uid', 'points', 'created', 'owner'])
                        ->values([
                          'actionid' => $res_lookup[0]->id,
                          'entity_id' => $decoded["entity_id"],
                          'uid' => $user_id,//$uid ,
                          'points' => $points,
                          'created' => $created,
                          'owner' => 1  ])
                        ->execute();
            //}
            if($result)
              $success= "User points added successfully";
          }
          elseif($res_publish[0]->field_is_published_value==1 && $decoded['isPublished']!=1)  
          {
            // if($decoded["entityType"] == "comment"){
            //   foreach ($res_lookup as $action){
            //     if($action->name =='Publish comment give'){               
            //       $user_id = $give_uid;
            //       $owner = 0;                  
            //     }
            //     else{
            //       $user_id = $uid;
            //       $owner = 1;
            //     }
            //     $result = $database->insert('user_points')
            //               ->fields(['actionid', 'entity_id', 'uid', 'points', 'created', 'owner'])
            //               ->values([
            //                 'actionid' => $action->id,
            //                 'entity_id' => $decoded["entity_id"],
            //                 'uid' => $user_id ,
            //                 'points' => -$action->points,
            //                 'created' => NULL,
            //                 'owner' => $owner  ])
            //               ->execute();
            //   }
            // }
            // else{
              $points = -$res_lookup[0]->points;
              $created = NULL;
              $result = $database->insert('user_points')
                        ->fields(['actionid', 'entity_id', 'uid', 'points', 'created', 'owner'])
                        ->values([
                          'actionid' => $res_lookup[0]->id,
                          'entity_id' => $decoded["entity_id"],
                          'uid' => $user_id,//$uid ,
                          'points' => $points,
                          'created' => $created,
                          'owner' => 1  ])
                        ->execute();
            //}
            if($result)
              $success= "User points added successfully";
          }
      }
      else
      {
        if($decoded['isPublished']==1)
        {  
      
          // if($decoded["entityType"] == "comment"){
          //   foreach ($res_lookup as $action){
          //     if($action->name=="Publish comment give"){                
          //       $user_id = $give_uid;
          //       $owner = 0;
          //     }
          //     else{
          //       $user_id = $uid;
          //       $owner = 1;
          //     }
          //     $result = $database->insert('user_points')
          //               ->fields(['actionid', 'entity_id', 'uid', 'points', 'created', 'owner'])
          //               ->values([
          //                 'actionid' => $action->id,
          //                 'entity_id' => $decoded["entity_id"],
          //                 'uid' => $user_id,
          //                 'points' => $action->points,
          //                 'created' => date("Y-m-d H:i:s", time()),
          //                 'owner' => $owner])
          //               ->execute();
          //   }
          // }
          // else{
            $result = $database->insert('user_points')
            ->fields(['actionid', 'entity_id', 'uid', 'points', 'created', 'owner'])
            ->values([
              'actionid' => $res_lookup[0]->id,
              'entity_id' => $decoded["entity_id"],
              'uid' => $user_id,//$uid ,
              'points' => $res_lookup[0]->points,
              'created' => $decoded["createdDate"]." ".$decoded["createdTime"],
              'owner' => 1  ])
            ->execute();
            }
            if($result)
              $success= "User points added successfully";
        //}
      }
    }
    return new JsonResponse(['message' => $success]);
  }

}