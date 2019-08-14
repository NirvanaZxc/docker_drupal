<?php
namespace Drupal\fg_rest_api\Plugin\rest\resource;

use Drupal\Core\Cache\CacheableResponseInterface;
use Drupal\rest\Plugin\ResourceBase;;
use Drupal\rest\ResourceResponse;
use Drupal\taxonomy\Entity\Term;

/**
 * Provides a Demo Resource
 *
 * @RestResource(
 *   id = "fg_get_theme",
 *   label = @Translation("Fg Resource Get theme"),
 *   uri_paths = {
 *     "canonical" = "/api/v1/theme"
 *   }
 * )
 */
class FgGetThemeResource extends ResourceBase
{
  /**
   * Responds to GET requests.
   *
   * Returns a list of Taxonomy.
   *
   * @return ResourceResponse
   *   The HTTP response object.
   */
  public function get()
  {

    $query = \Drupal::entityQuery('taxonomy_term');
    $entitieIds = $query->condition('vid', "theme")
      ->condition('status', 1)  // Only return the newest 10 articles
      ->execute();
    $taxs = Term::loadMultiple($entitieIds);
    if (!empty($taxs)) {
      foreach ($taxs as $key => $tax) {
        $data[] = array(
          'name' => $tax->name->value,
          'id' => $tax->field_cible->value,
          'icon' => strip_tags($tax->description->value),
        );
      }
      /** @var array $data */
      $response = new ResourceResponse($data);

      if ($response instanceof CacheableResponseInterface) {
        $response->addCacheableDependency($data);
      }
    }

    return $response;
  }

}
