<?php

/**
 * Contains \Drupal\media_entity_youtube\Plugin\MediaEntity\Type\YouTube.
 */

namespace Drupal\media_entity_youtube\Plugin\MediaEntity\Type;

use Drupal\Component\Plugin\PluginBase;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\media_entity\MediaBundleInterface;
use Drupal\media_entity\MediaInterface;
use Drupal\media_entity\MediaTypeException;
use Drupal\media_entity\MediaTypeInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides media type plugin for YouTube videos.
 *
 * @MediaType(
 *   id = "youtube",
 *   label = @Translation("YouTube video"),
 *   description = @Translation("Provides bussiness logic and metadata for YouTube videos..")
 * )
 */
class YouTube extends PluginBase implements MediaTypeInterface {
  use StringTranslationTrait;

  /**
   * Metadata information fetched from YouTube.
   *
   * @var \SimpleXMLElement|FALSE
   */
  protected $metadata;

  /**
   * List of validation regural expressions.
   *
   * @var array
   */
  protected $validationRegexp = array(
    '@(http|https)://www\.youtube(-nocookie)?\.com/embed/(?<id>[a-z0-9_-]+)@i',
    '@(http|https)://www\.youtube(-nocookie)?\.com/v/(?<id>[a-z0-9_-]+)@i',
    '@//www\.youtube(-nocookie)?\.com/embed/(?<id>[a-z0-9_-]+)@i',
    '@//www\.youtube(-nocookie)?\.com/v/(?<id>[a-z0-9_-]+)@i',
    '@(http|https)://www\.youtube\.com/watch\?v=(?<id>[a-z0-9_-]+)@i',
  );

  /**
   * Plugin label.
   *
   * @var string
   */
  protected $label;

  /**
   * Config factory service.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('config.factory')
    );
  }

  /**
   * Constructs a new class instance.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   Config factory service.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, ConfigFactoryInterface $config_factory) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->configFactory = $config_factory;
  }

  /**
   * {@inheritdoc}
   */
  public function label() {
    return $this->label;
  }

  /**
   * {@inheritdoc}
   */
  public function providedFields() {
    return array(
      'video_id' => $this->t('Video ID.'),
      'image_local' => $this->t('Copies video thumbnail to the local filesystem and returns the URI.'),
      'image_local_uri' => $this->t('Gets URI of the locally saved thumbnail.'),
      'remote_thumbnail' => $this->t('Link to remotely hosted video thumbnail.'),
      'width' => $this->t('Video width (extracted from embed code).'),
      'height' => $this->t('Video height (extracted from embed code).'),
      'autoplay' => $this->t('Autoplay status (extracted from embed code).'),
      'privacy_mode' => $this->t('Privacy mode status (extracted from embed code).'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getField(MediaInterface $media, $name) {
    if ($matches = $this->matchRegexp($media)) {
      switch ($name) {
        case 'video_id':
          return $matches['id'];

        case 'image_local':
          $local_uri = $this->configFactory->get('media_entity_twitter.settings')->get('local_images') . '/' . $matches['id'] . '.jpg';

          if (!file_exists($local_uri)) {
            file_prepare_directory($local_uri, FILE_CREATE_DIRECTORY | FILE_MODIFY_PERMISSIONS);

            $maxres_thumb = 'http://img.youtube.com/vi/' . $matches['id'] . '/maxresdefault.jpg';
            if (!($data = file_get_contents($maxres_thumb))) {
              $size = 0;
              $xml = $this->getMetadata($matches['id']);
              foreach ($xml->children('media', TRUE)->group->thumbnail as $thumb) {
                if ($size < (int) $thumb->attributes()->width) {
                  $size = (int) $thumb->attributes()->width;
                  $maxres_thumb = (string) $thumb->attributes()->url;
                }
              }
              $data = file_get_contents($maxres_thumb)
            }

            file_unmanaged_save_data($data, $local_uri, FILE_EXISTS_REPLACE);

            return $local_uri;
          }
          return FALSE;

        case 'image_local_uri':
           return $this->configFactory->get('media_entity_twitter.settings')->get('local_images') . '/' . $matches['id'] . '.jpg';

        case 'remote_thumbnail':
          if ($data = $this->getMetadata($matches['id'])) {
            // TODO - should we do the same maxres magic as above?
            return $data->children('media', TRUE)->group->thumbnail[0]->attributes()->url;
          }
          return FALSE;

        case 'width':
          // @TODO: Needs implementation.
          return FALSE;

        case 'height':
          // @TODO: Needs implementation.
          return FALSE;

        case 'autoplay':
          // @TODO: Needs implementation.
          return FALSE;

        case 'privacy_mode':
          // @TODO: Needs implementation.
          return FALSE;

        default:
          return FALSE;

      }
    }

    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(MediaBundleInterface $bundle) {
    $form = array();
    $options = array();
    $allowed_field_types = array('string', 'string_long', 'link');
    foreach (\Drupal::entityManager()->getFieldDefinitions('media', $bundle->id()) as $field_name => $field) {
      if (in_array($field->getType(), $allowed_field_types)) {
        $options[$field_name] = $field->getLabel();
      }
    }

    $form['source_field'] = array(
      '#type' => 'select',
      '#title' => t('Field with source information'),
      '#description' => t('Field on media entity that stores YouTube embed code or URL.'),
      '#default_value' => empty($this->configuration['source_field']) ? NULL : $this->configuration['source_field'],
      '#options' => $options,
    );

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validate(MediaInterface $media) {
    if ($this->matchRegexp($media)) {
      return;
    }

    throw new MediaTypeException($this->configuration['source_field'], 'Not valid URL/embed code.');
  }

  /**
   * {@inheritdoc}
   */
  public function thumbnail(MediaInterface $media) {
    if ($local_image = $this->getField($media, 'local_image')) {
      return $local_image;
    }
    return $this->configFactory->get('media_entity.settings')->get('icon_base') . '/youtube.png';
  }

  /**
   * Runs preg_match on embed code/URL.
   *
   * @param MediaInterface $media
   *   Media object.
   * @return array|bool
   *   Array of preg matches or FALSE if no match.
   *
   * @see preg_match()
   */
  protected function matchRegexp(MediaInterface $media) {
    $matches = array();
    $source_field = $this->configuration['source_field'];
    foreach ($this->validationRegexp as $regexp) {
      $property_name = $media->{$source_field}->first()->mainPropertyName();
      if (preg_match($regexp, $media->{$source_field}->{$property_name}, $matches)) {
        return $matches;
      }
    }

    return FALSE;
  }

  /**
   * @param $id
   *   YouTube video ID.
   * @return \SimpleXMLElement|false
   *   XML object or FALSE in case of a failure.
   */
  protected function getMetadata($id) {
    if (!isset($this->metadata)) {
      $this->metadata = simplexml_load_file('http://gdata.youtube.com/feeds/api/videos/' . $id);
    }

    return $this->metadata;
  }
}
