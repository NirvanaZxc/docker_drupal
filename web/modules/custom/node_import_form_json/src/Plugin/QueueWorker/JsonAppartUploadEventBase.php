<?php
namespace Drupal\node_import_form_json\Plugin\QueueWorker;

use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Queue\QueueWorkerBase;
use Drupal\file\Entity\File;
use Drupal\node\Entity\Node;
use Drupal\taxonomy\Entity\Term;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 *
 * @QueueWorker(
 * id = "appart_processor",
 * title = "Json Upload Queue Worker",
 * cron = {"time" = 30}
 * )
 */
class JsonAppartUploadEventBase extends QueueWorkerBase implements ContainerFactoryPluginInterface {

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
            $mainImage =  drupal_get_path('module', 'node_import_form_json') . '/jsondata/appart/' .$value['SkuId']. '/' .$value['MainImage'];
            $data = file_get_contents($mainImage);
            $file = file_save_data($data, 'public://'.basename($mainImage), FILE_EXISTS_REPLACE);

            if (!empty($value['SlideImages'])){
                foreach ($value['SlideImages'] as $dishImage) {
                    $itemImage =  drupal_get_path('module', 'node_import_form_json') . '/jsondata/appart/' .$value['SkuId']. '/' .$dishImage['Name'];
                    $dataImage = file_get_contents($itemImage);

                    $fileImage = file_save_data($dataImage, 'public://'.basename($itemImage));

                    if($fileImage->id()){
                        $slides[$key][] =  array('target_id' => $fileImage->id(),'alt' => $dishImage['Description'], 'title' => $dishImage['Price']);
                    }
                }
            }

          if (!empty($value['Address'])) {
            $existeTax = taxonomy_term_load_multiple_by_name($value['Address']);

            if (empty($existeTax)) {
              $term = Term::create([
                'name' => trim($value['Address']),
                'vid' => 'appart',
              ])->save();
              $existeTax = taxonomy_term_load_multiple_by_name($value['Address']);
              $termx = reset($existeTax);
            } else {
              $termx = reset($existeTax);
            }
          }
          $nodes = \Drupal::entityTypeManager()
            ->getStorage('node')
            ->loadByProperties(['field_sku' => $value['SkuId']]);

          if ($node = reset($nodes)) {
            $node = Node::load($node->id());
            $node->set("title",$value['Name']);
            $node->set("body",strip_tags($value['Body']));
            $node->set("field_area",$value['Area']);
            $node->set("field_address",$value['Address']);
            $node->set("field_main_image",$file->id());
            $node->set("field_price",$value['Price']);
            $node->set("field_slide_image",$slides[$key]);
            $node->set("field_tag",$value['Tags']);
            $node->set("field_transport",$value['Transport']);
            $node->set("field_contacter",$value['Contacter']);
            $node->set("field_deposite",$value['Deposite']);
            $node->set("field_topfee",$value['TopFee']);
            $node->set("field_wechat",$value['Wechat']);
            $node->set("field_tel",$value['Tel']);
            $node->set("field_sku",$value['SkuId']);
            $node->save();
          }
          else{
            /** @var TYPE_NAME $cat */
            /** @var TYPE_NAME $slides */
            /** @var TYPE_NAME $termx */
            $node = Node::create(array(
                'type' => 'appart',
                'langcode' => 'en',
                'uid' => '1',
                'status' => 1,
                'title' => $value['Name'],
                'body' => strip_tags($value['Body']),
                'field_area' => $value['Area'],
                'field_address' => $value['Address'],
                'field_main_image' => $file->id(),
                'field_price' => $value['Price'],
                'field_slide_image' => $slides[$key],
                'field_tel' => $value['Tel'],
                'field_sku' => $value['SkuId'],
                'field_transport' => $value['Transport'],
                'field_tag' => $value['Tags'],
                'field_contacter' => $value['Contacter'],
                'field_deposite' => $value['Deposite'],
                'field_topfee' => $value['TopFee'],
                'field_wechat' => $value['Wechat'],
                'field_category' => array(
                  array( 'target_id' => $termx->id() ),
                ),
              )
            );

            $node->save();
          }
        }
    }
}
