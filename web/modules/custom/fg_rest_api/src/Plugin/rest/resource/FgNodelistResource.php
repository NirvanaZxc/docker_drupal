<?php

namespace Drupal\fg_rest_api\Plugin\rest\resource;

use Drupal\Core\Cache\CacheableResponseInterface;
use Drupal\file\Entity\File;
use Drupal\image\Entity\ImageStyle;
use Drupal\node\Entity\Node;
use Drupal\rest\Plugin\ResourceBase;;

use Drupal\rest\ResourceResponse;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

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
     *
     * @param $bundle
     * @return \Drupal\rest\ResourceResponse
     *   The HTTP response object.
     *
     * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
     * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
     */
    public function get($bundle)
    {
        if($bundle) {
            $offset = \Drupal::request()->get('pageIndex');
            $limit = \Drupal::request()->get('pageSize');

            $query = \Drupal::entityQuery('node');
            $entitieIds = $query->condition('type', $bundle)
                ->condition('status', 1)  // Only return the newest 10 articles
                ->sort('created', 'DESC')
                ->range($offset, $limit)
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
                if ($response instanceof CacheableResponseInterface) {
                    $response->addCacheableDependency($new);
                }
            }

        }
        return $response;
    }

}