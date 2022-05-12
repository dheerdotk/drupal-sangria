<?php

namespace Drupal\search_history_api\Plugin\rest\resource;

use Drupal\rest\Plugin\ResourceBase;
use Drupal\rest\ResourceResponse;
use Psr\Log\LoggerInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * Provides a Search History Resource
 *
 * @RestResource(
 *   id = "search_history_resource",
 *   label = @Translation("Search History Resource"),
 *   uri_paths = {
 *     "canonical" = "/search_history_api/search_history_resource"
 *   }
 * )
 */

class SearchHistoryResource extends ResourceBase {
    /**
   * Responds to entity GET requests.
   * @return \Drupal\rest\ResourceResponse
   */
	public function get() {
           $database = \Drupal::database();
           $query = $database->query("SELECT keywords as SearchTerm, COUNT(1) as Occurances FROM search_api_stats group by keywords order by count(1) desc LIMIT 10");
           $result = $query->fetchAll();
          return new JsonResponse($result);
  }

}
