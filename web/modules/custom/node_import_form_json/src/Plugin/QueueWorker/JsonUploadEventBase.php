<?php
namespace Drupal\node_import_form_json\Plugin\QueueWorker;

use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Queue\QueueWorkerBase;
use Drupal\file\Entity\File;
use Drupal\node\Entity\Node;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 *
 * @QueueWorker(
 * id = "json_processor",
 * title = "Json Upload Queue Worker",
 * cron = {"time" = 30}
 * )
 */
class JsonUploadEventBase extends QueueWorkerBase implements ContainerFactoryPluginInterface {

    /**
     * Node storage.
     *
     * @var EntityStorageInterface
     */
    protected $nodeStorage;

    /**
     * {@inheritdoc}
     */
    public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
        return new static(
            $configuration,
            $plugin_id,
            $plugin_definition,
            $container->get('entity_type.manager')->getStorage('node'),
        );
    }

    /**
     * Processes a single item of Queue.
     * @param $data
     * @throws \Drupal\Core\Entity\EntityStorageException
     */
    public function processItem($data) {

        foreach ($data as $key => $value){
            // Create file object from remote URL.
            $mainImage =  drupal_get_path('module', 'node_import_form_json') . '/jsondata/restaurant/' .$value['SkuId']. '/' .$value['MainImage'];
            $data = file_get_contents($mainImage);
            $file = file_save_data($data, 'public://'.basename($mainImage), FILE_EXISTS_REPLACE);

            if (!empty($value['DishImages'])){
                foreach ($value['DishImages'] as $dishImage) {
                    $itemImage =  drupal_get_path('module', 'node_import_form_json') . '/jsondata/restaurant/' .$value['SkuId']. '/' .$dishImage['Name'];
                    $dataImage = file_get_contents($itemImage);

                    $fileImage = file_save_data($dataImage, 'public://'.basename($itemImage));

                    if($fileImage->id()){
                        $slides[$key][] =  array('target_id' => $fileImage->id(),'alt' => $dishImage['Description'], 'title' => $dishImage['Price']);
                    }
                }
            }

            if(!empty($value['Tags'])){
             $cat = $this->switchCategory($value['Tags']);
            }

          $nodes = \Drupal::entityTypeManager()
            ->getStorage('node')
            ->loadByProperties(['field_sku' => $value['SkuId']]);

          if ($node = reset($nodes)) {
            $node = Node::load($node->id());
            $node->set("title",$value['Name']);
            $node->set("body",strip_tags($value['Body']));
            $node->set("field_horaire",$value['Horaire']);
            $node->set("field_address",$value['Address']);
            $node->set("field_main_image",$file->id());
            $node->set("field_price",$value['Price']);
            $node->set("field_slide_image",$slides[$key]);
            $node->set("field_tag",$value['Tags']);
            $node->set("field_tel",$value['Tel']);
            $node->set("field_sku",$value['SkuId']);
            $node->save();
          }
          else{
            /** @var TYPE_NAME $cat */
            /** @var TYPE_NAME $slides */
            $node = Node::create(array(
                'type' => 'restaurant',
                'langcode' => 'en',
                'uid' => '1',
                'status' => 1,
                'title' => $value['Name'],
                'body' => strip_tags($value['Body']),
                'field_horaire' => $value['Horaire'],
                'field_address' => $value['Address'],
                'field_main_image' => $file->id(),
                'field_price' => $value['Price'],
                'field_slide_image' => $slides[$key],
                'field_tel' => $value['Tel'],
                'field_sku' => $value['SkuId'],
                'field_transport' => $value['Transport'],
                'field_tag' => $value['Tags'],
                'field_category' => array(
                  array( 'target_id' => $cat ),
                ),
              )
            );

            $node->save();
          }
        }
    }

    private function switchCategory($string){
      if(strpos(mb_convert_encoding($string, 'utf-8'), mb_convert_encoding('日', 'utf-8'))){
        return 3;
      }
      elseif(strpos(mb_convert_encoding($string, 'utf-8'), mb_convert_encoding('意', 'utf-8'))){
        return 4;
      }
      elseif(strpos(mb_convert_encoding($string, 'utf-8'), mb_convert_encoding('泰', 'utf-8'))){
        return 15;
      }
      elseif(strpos(mb_convert_encoding($string, 'utf-8'), mb_convert_encoding('韩', 'utf-8'))){
        return 5;
      }
      elseif(strpos(mb_convert_encoding($string, 'utf-8'), mb_convert_encoding('法', 'utf-8'))){
        return 2;
      }
      else{
        return 1;
      }
    }
}
