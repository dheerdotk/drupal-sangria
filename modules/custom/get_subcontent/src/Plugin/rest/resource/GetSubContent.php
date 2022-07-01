<?php

namespace Drupal\get_subcontent\Plugin\rest\resource;
 
use Drupal\rest\Plugin\ResourceBase;
use Drupal\rest\ResourceResponse;
use Psr\Log\LoggerInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * Provides a Get Sub Content Resource with two parameters
 *
 * @RestResource(
 *   id = "get_subcontent_list",
 *   label = @Translation("Get Sub Content Resource with two parameters"),
 *   uri_paths = {
 *     "canonical" = "/api/v1/getSubContent/{list_type}/{param}/{param2}"
 *   }
 * )
 */
 
class GetSubContent extends ResourceBase {
    /**
   * Responds to entity GET requests.
   * @return \Drupal\rest\ResourceResponse
   */

  public function get($list_type,$param,$param2) {
   
    $database = \Drupal::database();
    /*/apiv1/getcontent/listrecipes/0851aaff-857a-4399-964b-3b0f9d5f4b19?_format=json*/
    if($list_type=="listrecipes"){///detail page
      $uuid = $param;
      $query = $database->query("Call sp_GetRecipeDetails('".$uuid."')");
      $result = $query->fetchAll();
    }
    /*/apiv1/getcontent/listrecipesmini/1461?_format=json*/
    elseif($list_type=="listrecipesmini"){//default (0,10) page1
      $id = $param;
      $query = $database->query("Call sp_GetRecipeMiniList('".$id."',0,10)");
      $result = $query->fetchAll();
    }
    /*/apiv1/getcontent/listrecipesmini_paged/16?_format=json&page=0*/
    elseif($list_type=="listrecipesmini_paged"){//default (16) page2
      $id = $param;
      $noofrecords = $param2;
      $page = \Drupal::request()->query->get('page');
      $query = $database->query("Call sp_GetRecipeMiniList('".$id."',".$page.",".$noofrecords.")");
      $result = $query->fetchAll();
    }
    /*/api/v1/getcontent/listblogs/0851aaff-857a-4399-964b-3b0f9d5f4b19?_format=json*/
    elseif($list_type=="listblogs" || $list_type=="listjourneys"){///detail page
      $uuid = $param;
      $query = $database->query("Call sp_GetBlogDetails('".$uuid."')");
      $result = $query->fetchAll();
    }
    /*/apiv1/getcontent/listblogsmini/1461?_format=json*/
    elseif($list_type=="listblogsmini" || $list_type=="listjourneysmini"){//default (0,10) page1
      $id = $param;
      $query = $database->query("Call sp_GetBlogMiniList('".$id."',0,10)");
      $result = $query->fetchAll();
    }
    /*/api/v1/getcontent/listblogsmini_paged/1461?_format=json&page=0*/
    elseif($list_type=="listblogsmini_paged" || $list_type=="listjourneysmini_paged"){//default (16) page2
      $id = $param;
      $page = \Drupal::request()->query->get('page');
      $query = $database->query("Call sp_GetBlogMiniList('".$id."',".$page.",16)");
      $result = $query->fetchAll();
    }
    /*/api/v1/getcontent/getquestion/4643?_format=json*/
    elseif($list_type=="getquestion"){///detail page
      $nid = $param;
      $query = $database->query("Call sp_GetQADetails('".$nid."')");
      $result = $query->fetchAll();
    }
    /*/api/v1/getcontent/answers/4643?_format=json*/
    elseif($list_type=="answers"){///detail page
      $nid = $param;
      $query = $database->query("Call sp_GetAnswersforQuestion('".$nid."')");
      $result = $query->fetchAll();
    }
    /*/apiv1/getcontent/listquestionsmini/1500?_format=json*/
    elseif($list_type=="listquestionsmini"){//default (0,10) page1
      $id = $param;
      $query = $database->query("Call sp_GetQuestionsMiniList('".$id."',0,10)");
      $result = $query->fetchAll();
    }
    /*/api/v1/getcontent/listquestionsmini_paged/1500?_format=json&page=0*/
    elseif($list_type=="listquestionsmini_paged"){//default (16) page2
      $id = $param;
      $page = \Drupal::request()->query->get('page');
      $query = $database->query("Call sp_GetQuestionsMiniList('".$id."',".$page.",16)");
      $result = $query->fetchAll();
    }

    return new JsonResponse($result);
  }

}