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
 *   id = "fg_get_top_slides",
 *   label = @Translation("Fg Get Top Slides"),
 *   uri_paths = {
 *     "canonical" = "/api/v1/getslides"
 *   }
 * )
 */
class FgGetTopSlides extends ResourceBase
{
  /**
   * Responds to GET requests.
   *
   * Returns a list of bundles for specified entity.
   * @param $bundle
   * @return \Drupal\rest\ResourceResponse
   *   The HTTP response object.
   */
  public function get()
  {
      $query = \Drupal::entityQuery('node');
      $entitieIds = $query->condition('status', 1)
        ->condition('sticky', 1)
        ->sort('created', 'DESC')
        ->execute();

      $entities = Node::loadMultiple($entitieIds);
      if (!empty($entities)) {
        $data = [];
        foreach ($entities as $key => $node) {
          if (!empty($node)) {
            $objImage = File::load($node->field_main_image->target_id);
            $data[$key]['image'] = ImageStyle::load('moblie_list')->buildUrl($objImage->getFileUri());
          }
        }

        $new = array();
        foreach ($data as $key => $value){
          $new[] = $value;
        }

        $response = new ResourceResponse($new);
        if ($response instanceof CacheableResponseInterface) {
          $response->addCacheableDependency($new);
        }
      }

    return $response;
  }
}
