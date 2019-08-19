<?php

namespace Drupal\fg_rest_api\Plugin\rest\resource;

use Drupal\Core\Cache\CacheableResponseInterface;
use Drupal\file\Entity\File;
use Drupal\image\Entity\ImageStyle;
use Drupal\node\Entity\Node;
use Drupal\rest\Plugin\ResourceBase;;
use Drupal\rest\ResourceResponse;

/**
 * Provides a Demo Resource
 *
 * @RestResource(
 *   id = "fg_resource_list",
 *   label = @Translation("Fg Resource List"),
 *   uri_paths = {
 *     "canonical" = "/api/v1/list/{bundle}"
 *   }
 * )
 */
class FgNodelistResource extends ResourceBase
{
  /**
   * Responds to GET requests.
   *
   * Returns a list of bundles for specified entity.
   * @param $bundle
   * @return \Drupal\rest\ResourceResponse
   *   The HTTP response object.
   */
    public function get($bundle)
    {
        if($bundle) {
            $offset = \Drupal::request()->get('pageIndex');
            $limit = \Drupal::request()->get('pageSize');

            $cat = \Drupal::request()->get('category');

            if(empty($cat)){
              $terms =\Drupal::entityTypeManager()->getStorage('taxonomy_term')->loadTree('tags');
              foreach ($terms as $term) {
                $cat[] = $term->tid;
              }
            }
            else{
              $cat = [$cat];
            }
            $query = \Drupal::entityQuery('node');
            $entitieIds = $query->condition('type', $bundle)
                ->condition('status', 1)
                ->condition('field_category', $cat, 'IN')
                ->sort('created', 'DESC')
                ->range($offset, $limit)
                ->execute();

            ######pass total#########
            $queryTotal = \Drupal::entityQuery('node');
            $entitieTotal = $queryTotal->condition('type', $bundle)
              ->condition('status', 1)
              ->condition('field_category', $cat, 'IN')
              ->sort('created', 'DESC')
              ->count()
              ->execute();

            $entities = Node::loadMultiple($entitieIds);
            if (!empty($entities)) {
                $data = [];
                foreach ($entities as $key => $restaurant) {
                    if (!empty($restaurant)) {
                        $objImage = File::load($restaurant->field_main_image->target_id);
                        $data[$key]['id'] = $restaurant->nid->value;
                        $data[$key]['title'] = $restaurant->title->value;
                        $data[$key]['teaser'] = rtrim($restaurant->field_tag->value, "," );
                        $data[$key]['image'] = ImageStyle::load('moblie_list')->buildUrl($objImage->getFileUri());
                    }
                }

                $new = array();
                foreach ($data as $key => $value){
                    $new[] = $value;
                }

                $response = new ResourceResponse($new);
                $response->headers->set('X-Total-Count', $entitieTotal);
                if ($response instanceof CacheableResponseInterface) {
                    $response->addCacheableDependency($new);
                }
            }

        }
        return $response;
    }

}
