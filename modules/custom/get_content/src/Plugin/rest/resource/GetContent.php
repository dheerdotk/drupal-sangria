<?php

namespace Drupal\get_content\Plugin\rest\resource;
 
use Drupal\rest\Plugin\ResourceBase;
use Drupal\rest\ResourceResponse;
use Psr\Log\LoggerInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;

// error_reporting(E_ALL);
// ini_set('display_errors', TRUE);
// ini_set('display_startup_errors', TRUE);


/**
 * Provides a Get Content Resource
 *
 * @RestResource(
 *   id = "get_content_list",
 *   label = @Translation("Get Content Resource"),
 *   uri_paths = {
 *     "canonical" = "/v1/getContent/{list_type}/{param}"
 *   }
 * )
 */
 
class GetContent extends ResourceBase {
    /**
   * Responds to entity GET requests.
   * @return \Drupal\rest\ResourceResponse
   */

  public function get($list_type,$param) {
   
    echo "list_type = ".$list_type;
    echo ", param = ".$param;
    $database = \Drupal::database();
    /*v1/getcontent/listrecipes/0851aaff-857a-4399-964b-3b0f9d5f4b19?_format=json*/
    if($list_type=="listrecipes"){///detail page
      $uuid = $param;
      $query = $database->query("Call sp_GetRecipeDetails('".$uuid."')");
      $result = $query->fetchAll();
    }
    /*v1/getcontent/listrecipesmini/1461?_format=json*/
    elseif($list_type=="listrecipesmini"){//default (0,10) page1
      $id = $param;
      $query = $database->query("Call sp_GetRecipeMiniList('".$id."',0,10)");
      $result = $query->fetchAll();
    }
    /*v1/getcontent/listrecipesmini_paged/16?_format=json&page=0*/
    elseif($list_type=="listrecipesmini_paged"){//default (16) page2
      $id = $param;
      $page = \Drupal::request()->query->get('page')*3;
      $query = $database->query("Call sp_GetRecipeMiniList('".$id."',".$page.",3)");
      $result = $query->fetchAll();
    }
    /*v1/getcontent/listblogs/0851aaff-857a-4399-964b-3b0f9d5f4b19?_format=json*/
    elseif($list_type=="listblogs"){///detail page
      $uuid = $param;
      $query = $database->query("Call GetBlogDetails('".$uuid."')");
      $result = $query->fetchAll();
    }
    /*v1/getcontent/listblogsmini/1461?_format=json*/
    elseif($list_type=="listblogsmini"){//default (0,10) page1
      $id = $param;
      $query = $database->query("Call GetBlogMiniList('".$id."',0,10)");
      $result = $query->fetchAll();
    }
    /*v1/getcontent/listblogsmini_paged/16?_format=json&page=0*/
    elseif($list_type=="listblogsmini_paged"){//default (16) page2
      $id = $param;
      $page = \Drupal::request()->query->get('page')*3;
      $query = $database->query("Call GetBlogMiniList('".$id."',".$page.",3)");
      $result = $query->fetchAll();
    }
    return new JsonResponse($result);
  }

}