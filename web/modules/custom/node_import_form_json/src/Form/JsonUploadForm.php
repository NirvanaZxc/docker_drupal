<?php
namespace Drupal\node_import_form_json\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

/**
* Class UserQueryForm.
*
* @package Drupal\my_module\Form
*/
class JsonUploadForm extends FormBase {

    /**
    * {@inheritdoc}
    */
    public function getFormId() {
    return 'json_upload_form';
    }

    /**
    * {@inheritdoc}
    */
    public function buildForm(array $form, FormStateInterface $form_state) {
        $form['name'] = [
        '#type' => 'textfield',
        '#attributes' => [
        'placeholder' => 'json file name',
        ],
        '#required' => true,
        ];

        $form['type'] = [
          '#type' => 'select',
          '#title' => t('Selected'),
          '#options' => array(
            'restaurant' => t('restaurant'),
            'book' => t('book'),
            'appart' => t('appartement'),
          ),
          '#attributes' => [
            'placeholder' => 'content type',
          ],
          '#required' => true,
        ];

        $form['submit'] = [
        '#type' => 'submit',
        '#value' => 'Send',

        ];
        return $form;
    }

    /**
    * {@inheritdoc}
    */
    public function validateForm(array &$form, FormStateInterface $form_state) {

    }

    /**
    * {@inheritdoc}
    */
    public function submitForm(array &$form, FormStateInterface $form_state) {

        $item = new \stdClass();
        $item->name = $form_state->getValue('name');
        $item->type = $form_state->getValue('type');
        switch ($item->type) {
          case 'restaurant':
              /** @var QueueFactory $queue_factory */
              $queue_factory = \Drupal::service('queue');
              /** @var QueueInterface $queue */
              $queue = $queue_factory->get('json_processor');
            break;
          case 'book':
              /** @var QueueFactory $queue_factory */
              $queue_factory = \Drupal::service('queue');
              /** @var QueueInterface $queue */
              $queue = $queue_factory->get('book_processor');
            break;
          case 'appart':
            /** @var QueueFactory $queue_factory */
            $queue_factory = \Drupal::service('queue');
            /** @var QueueInterface $queue */
            $queue = $queue_factory->get('appart_processor');
            break;
          default;
        }

        $pathFileJson = drupal_get_path('module', 'node_import_form_json') . '/jsondata/' .$item->name;
        $jsondata = file_get_contents($pathFileJson);
        $jsonout = json_decode($jsondata, TRUE);
        $queue->createItem($jsonout);
    }
}
