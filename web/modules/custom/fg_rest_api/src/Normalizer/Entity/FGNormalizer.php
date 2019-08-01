<?php

namespace Drupal\fg_rest_api\Normalizer\Entity;

use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Symfony\Component\Yaml\Yaml;

/**
 * Class FGNormalizer.
 */
abstract class FGNormalizer extends FGNormalizerBase {

    /**
     * The serializer.
     *
     * @var \Symfony\Component\Serializer\Serializer
     */
    protected $serializer;

    /**
     * The interface or class that this Normalizer supports.
     *
     * @var string|array
     */
    protected $supportedInterfaceOrClass = 'Drupal\Core\Entity\EntityInterface';

    /**
     * The format.
     *
     * @var string
     */
    protected $format = 'json';

    /**
     * The normalizer field mapping.
     *
     * @var mixed
     */
    protected $fieldMapping;

    /**
     * {@inheritdoc}
     */
    public function normalize($object, $format = NULL, array $context = []) {
        $this->fieldMapping = $this->getFieldMapping($object->getEntityTypeId(), $object->bundle(), $context);
        if ($this->fieldMapping) {
            $data = [];
            if (isset($this->fieldMapping['mapping'])) {
                foreach ($this->fieldMapping['mapping'] as $field_alias => $field_name) {
                    /** @var \Drupal\Core\Field\FieldItemListInterface $field */
                    /** @var \Drupal\Core\Entity\ContentEntityInterface $object */
                    if ($object->hasField($field_name)) {
                        $field = $object->get($field_name);
                        if (!$field->isEmpty()) {
                            if ($field->count() > 1) {
                                foreach ($field as $field_value) {
                                    $normalized_object = $this->serializer->normalize($field_value, $format, $context);
                                    /** @var \Drupal\Core\Field\FieldItemBase $field_value */
                                    if (!empty($normalized_object)) {
                                        $data[$field_alias][] = $normalized_object;
                                    }
                                }
                            }
                            else {
                                $cardinality = $field->getFieldDefinition()
                                    ->getFieldStorageDefinition()
                                    ->getCardinality();
                                if ($cardinality > 1 || $cardinality == FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED) {
                                    $data[$field_alias][] = $this->serializer->normalize($field, $format, $context);
                                }
                                else {
                                    $data[$field_alias] = $this->serializer->normalize($field, $format, $context);
                                }
                            }
                        }
                    }
                }
            }

            return $this->cleanDatas($data);
        }
        return [];
    }

    /**
     * Helper to retrieve field mapping settings.
     *
     * @param string $entity_type_id
     *   The entity type ID.
     * @param string $bundle
     *   The entity bundle.
     * @param array $context
     *   The context of the normalizing.
     *
     * @return array
     *   The entity mapping array.
     */
    protected function getFieldMapping($entity_type_id, $bundle, array $context) {
        $context['view_mode'] = $context['view_mode'] ?? 'default';
        $config_name = 'fg.' . $entity_type_id . '.' . $bundle . '.' . $context['view_mode'] . '.field_mapping.yml';
        $mapping_file = __DIR__ . '/../../Mapping/' . $config_name;
        if (file_exists($mapping_file)) {
            return Yaml::parse(file_get_contents($mapping_file));
        }
        return NULL;
    }

}

