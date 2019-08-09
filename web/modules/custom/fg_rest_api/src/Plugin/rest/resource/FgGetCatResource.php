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
 *   id = "fg_get_category",
 *   label = @Translation("Fg Resource Get Category"),
 *   uri_paths = {
 *     "canonical" = "/api/v1/category"
 *   }
 * )
 */
class FgGetCatResource extends ResourceBase
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
        $entitieIds = $query->condition('vid', "tags")
            ->condition('status', 1)  // Only return the newest 10 articles
            ->execute();
        $taxs = Term::loadMultiple($entitieIds);
        if (!empty($taxs)) {
            foreach ($taxs as $key => $tax) {
                $data[] = array(
                    'id' => $tax->tid->value,
                    'name' => $tax->name->value
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