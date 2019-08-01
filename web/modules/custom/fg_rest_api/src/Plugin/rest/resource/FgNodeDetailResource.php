<?php

namespace Drupal\fg_rest_api\Plugin\rest\resource;

use Drupal\Core\Cache\CacheableResponseInterface;
use Drupal\rest\Plugin\ResourceBase;;

use Drupal\rest\ResourceResponse;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a Demo Resource
 *
 * @RestResource(
 *   id = "fg_resource",
 *   label = @Translation("Fg Resource"),
 *   serialization_class = "Drupal\fg_rest_api\Normalizer\Entity\NodeEntityNormalizer",
 *   uri_paths = {
 *     "canonical" = "/api/v1/node/{id}"
 *   }
 * )
 */
class FgNodeDetailResource extends ResourceBase
{


    /**
     * Responds to GET requests.
     *
     * Returns a list of bundles for specified entity.
     *
     * @param $id
     * @return \Drupal\rest\ResourceResponse
     *   The HTTP response object.
     *
     * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
     * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
     */
    public function get($id)
    {
        if($id) {
            $node = \Drupal::entityTypeManager()->getStorage('node')->load($id);
            $response = new ResourceResponse($node);
            if ($response instanceof CacheableResponseInterface) {
                $response->addCacheableDependency($node);
            }
        }
        return $response;
    }
}