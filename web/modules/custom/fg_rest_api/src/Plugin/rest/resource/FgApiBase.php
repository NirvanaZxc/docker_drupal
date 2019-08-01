<?php

use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;


/**
 * Class ApiBase.
 *
 * @package Drupal\snpm_api\Controller
 */
abstract class ApiBase implements ContainerInjectionInterface
{

    /**
     * API version.
     */
    const VERSION = '0.1.1';
    /**
     * Serializer.
     *
     * @var \Symfony\Component\Serializer\Serializer
     *
     */
    protected $serializer;

    /**
     * Constructs a Drupal\rest\Plugin\ResourceBase object.
     *
     * @param array $configuration
     *   A configuration array containing information about the plugin instance.
     * @param string $plugin_id
     *   The plugin_id for the plugin instance.
     * @param mixed $plugin_definition
     *   The plugin implementation definition.
     * @param array $serializer_formats
     *   The available serialization formats.
     * @param \Psr\Log\LoggerInterface $logger
     *   A logger instance.
     * @param \Drupal\Core\Session\AccountProxyInterface $current_user
     *   A current user instance.
     */
    public function __construct(
        array $configuration,
        $plugin_id,
        $plugin_definition,
        array $serializer_formats,
        LoggerInterface $logger)
    {
        parent::__construct($configuration, $plugin_id, $plugin_definition, $serializer_formats, $logger);
    }

    /**
     * {@inheritdoc}
     */
    public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition)
    {
        return new static(
            $configuration,
            $plugin_id,
            $plugin_definition,
            $container->getParameter('serializer.formats'),
            $container->get('logger.factory')->get('fg_rest_api')
        );
    }


    /**
     * Add content to the response.
     *
     * @param array $data
     *   Array of datas.
     */
    public function addContent(array $data)
    {
        $this->data = array_merge_recursive($this->data, $data);
    }

}