<?php

namespace Drupal\fg_rest_api\Normalizer\Entity;

/**
 * Converts the Drupal entity object structures to a normalized array.
 */
class NodeEntityNormalizer extends FGNormalizer {
    /**
     * The interface or class that this Normalizer supports.
     *
     * @var string
     */
    protected $supportedInterfaceOrClass = 'Drupal\node\NodeInterface';
    /**
     * {@inheritdoc}
     */
    public function normalize($object, $format = NULL, array $context = []) {
        $data = parent::normalize($object, $format, $context);
        /** @var \Drupal\node\NodeInterface $object */
        switch ($object->bundle()) {
            case 'restaurant':
                if (array_search('field_main_image', $this->fieldMapping['mapping'])) {
                    $data['image'] = $this->serializer->normalize($object->get('field_main_image'), $format);
                }

                if (array_search('field_slide_image', $this->fieldMapping['mapping'])) {
                    $data['slide'] = $this->serializer->normalize($object->get('field_slide_image'), $format);
                }

                break;
          case 'book':
                if (array_search('field_main_image', $this->fieldMapping['mapping'])) {
                  $data['image'] = $this->serializer->normalize($object->get('field_main_image'), $format);
                }
                break;
        }

        if (isset($context['view_mode'])) {
            switch ($context['view_mode']) {
            }
        }
        return $this->cleanDatas($data);
    }
}
