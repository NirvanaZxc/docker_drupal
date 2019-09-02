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
 * id = "book_processor",
 * title = "Json Upload Queue Worker",
 * cron = {"time" = 30}
 * )
 */
class JsonBookUploadEventBase extends QueueWorkerBase implements ContainerFactoryPluginInterface {

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
        foreach ($data as $key => $value) {
          // Create file object from remote URL.
          $mainImage = drupal_get_path('module', 'node_import_form_json') . '/jsondata/book/' . $value['SkuId'] . '/' . $value['MainImage'];
          $data = file_get_contents($mainImage);
          $file = file_save_data($data, 'public://' . basename($mainImage), FILE_EXISTS_REPLACE);

          if (!empty($value['Category'])) {
            $existeTax = taxonomy_term_load_multiple_by_name($value['Category']);

            if (empty($existeTax)) {
              $term = Term::create([
                'name' => trim($value['Category']),
                'vid' => 'book',
              ])->save();
              $existeTax = taxonomy_term_load_multiple_by_name($value['Category']);
              $termx = reset($existeTax);
            } else {
              $termx = reset($existeTax);
            }
          }

          $dir = drupal_get_path('module', 'node_import_form_json') . '/jsondata/book/' . $value['SkuId'];
          $handler = opendir($dir);
          while (($filename = readdir($handler)) !== false) {//务必使用!==，防止目录下出现类似文件名“0”等情况
            if ($filename != "." && $filename != "..") {
              //一般我们用数组，但是这里我们只有一个文件，
              $ext = pathinfo($filename, PATHINFO_EXTENSION);
              if($ext == 'epub'){
                $url = $dir . '/' . $filename;
              }
            }
          }
          closedir($handler);

          $nodes = \Drupal::entityTypeManager()
            ->getStorage('node')
            ->loadByProperties(['field_sku' => $value['SkuId']]);

          if ($node = reset($nodes)) {
            $node = Node::load($node->id());
            $node->set("title",$value['Name']);
            $node->set("body",strip_tags($value['Body']));
            $node->set("field_author",$value['Author']);
            $node->set("field_authorintroduction",$value['AuthorIntroduction']);
            $node->set("field_main_image",$file->id());
            $node->set("field_format",$value['Format']);
            $node->set("field_rate",$value['Rate']);
            $node->set("field_url",$url);
            $node->set("field_sku",$value['SkuId']);
            $node->save();
          }
          else{
            /** @var TYPE_NAME $cat */
            /** @var TYPE_NAME $slides */
            /** @var TYPE_NAME $term */
            /** @var TYPE_NAME $url */
            $node = Node::create(array(
                'type' => 'book',
                'langcode' => 'en',
                'uid' => '1',
                'status' => 1,
                'title' => $value['Name'],
                'body' => strip_tags($value['Body']),
                'field_author' => $value['Author'],
                'field_authorintroduction' => $value['AuthorIntroduction'],
                'field_main_image' => $file->id(),
                'field_format' => $value['Format'],
                'field_rate' => $value['Rate'],
                'field_sku' => $value['SkuId'],
                'field_url' => $url,
                'field_category_book' => array(
                  array('target_id' => $termx->id()),
                ),
              )
            );

            $node->save();
          }
        }
    }

}
