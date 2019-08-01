<?php


namespace Drupal\fg_rest_api\Normalizer\Entity;

use Drupal\serialization\Normalizer\NormalizerBase;

/**
 * Base class for normalizer.
 *
 * Provides a function to remove null entries.
 */
abstract class FGNormalizerBase extends NormalizerBase
{

    /**
     * Remove empty field.
     *
     * @param array $data
     *   Data array.
     *
     * @return array
     *   Cleaned data array.
     */
    protected function cleanDatas(array $data)
    {
        $output = [];
        foreach ($data as $key => $value) {
            if (is_array($value) && !empty($value)) {
                $output[$key] = $this->cleanDatas($value);
            } elseif ($value !== NULL && $value !== "" && $value !== [] && $value !== FALSE) {
                $output[$key] = $value;
            }
        }
        return $output;
    }

}
