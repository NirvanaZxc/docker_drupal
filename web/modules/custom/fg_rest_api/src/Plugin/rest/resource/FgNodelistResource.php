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
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
    public function get($bundle)
    {
        if($bundle) {
            $offset = \Drupal::request()->get('pageIndex');
            $limit = \Drupal::request()->get('pageSize');

            $cat = \Drupal::request()->get('category');

            if(!$cat){
              if($bundle == 'restaurant'){
                $terms =\Drupal::entityTypeManager()->getStorage('taxonomy_term')->loadTree('tags');
                foreach ($terms as $term) {
                  $cats[] = $term->tid;
                }
              }
              elseif($bundle == 'book'){
                $terms =\Drupal::entityTypeManager()->getStorage('taxonomy_term')->loadTree('book');
                foreach ($terms as $term) {
                  $cats[] = $term->tid;
                }
              }

            }
            else{
              $cats = [$cat];
            }


            if($bundle == 'restaurant') {
              $query = \Drupal::entityQuery('node');
              $entitieIds = $query->condition('type', $bundle)
                ->condition('status', 1)
                ->condition('field_category', $cats, 'IN')
                ->sort('created', 'DESC')
                ->range($offset, $limit)
                ->execute();
              ######pass total#########
              $queryTotal = \Drupal::entityQuery('node');
              $entitieTotal = $queryTotal->condition('type', $bundle)
                ->condition('status', 1)
                ->condition('field_category', $cats, 'IN')
                ->sort('created', 'DESC')
                ->count()
                ->execute();
            }
            elseif($bundle == 'book'){
              $query = \Drupal::entityQuery('node');
              $entitieIds = $query->condition('type', $bundle)
                ->condition('status', 1)
                ->condition('field_category_book', $cats, 'IN')
                ->sort('created', 'DESC')
                ->range($offset, $limit)
                ->execute();
              ######pass total#########
              $queryTotal = \Drupal::entityQuery('node');
              $entitieTotal = $queryTotal->condition('type', $bundle)
                ->condition('status', 1)
                ->condition('field_category_book', $cats, 'IN')
                ->sort('created', 'DESC')
                ->count()
                ->execute();
            }
            if(!empty($entitieIds)){
              $entities = Node::loadMultiple($entitieIds);
              $data = [];
              foreach ($entities as $key => $entite) {
                if (!empty($entite)) {
                  $objImage = File::load($entite->field_main_image->target_id);
                  $data[$key]['id'] = $entite->nid->value;
                  $data[$key]['title'] = $entite->title->value;
                  if($bundle == 'restaurant'){
                    $data[$key]['teaser'] = rtrim($entite->field_tag->value, "," );
                  }
                  elseif($bundle == 'book')
                  {
                    $data[$key]['teaser'] = $entite->field_rate->value;
                  }
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
            else{
              $new = array('message' => '请见谅,目前还没有您想搜索的数据');
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
