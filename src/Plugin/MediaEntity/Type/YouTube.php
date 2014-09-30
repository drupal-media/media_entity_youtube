<?php

/**
 * Contains \Drupal\media_entity_youtube\Plugin\MediaEntity\Type\YouTube.
 */

namespace Drupal\media_entity_youtube\Plugin\MediaEntity\Type;

use Drupal\Component\Plugin\PluginBase;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\media_entity\MediaBundleInterface;
use Drupal\media_entity\MediaInterface;
use Drupal\media_entity\MediaTypeException;
use Drupal\media_entity\MediaTypeInterface;

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
      'local_thumbnail' => $this->t('Locally stored video thumbnail (downloaded from YouTube\'s servers).'),
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
    switch ($name) {
      case 'video_id':
        if ($matches = $this->matchRegexp($media)) {
          return $matches['id'];
        }
        return FALSE;

      case 'local_thumbnail':
        // @TODO: Needs implementation.
        return FALSE;

      case 'remote_thumbnail':
        // @TODO: Needs implementation.
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

  /**
   * {@inheritdoc}
   */
  public function settingsForm(MediaBundleInterface $bundle) {
    $form = array();
    $options = array();
    $allowed_field_types = array('text', 'text_long', 'link');
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

    throw new MediaTypeException($this->configuration['youtube']['source_field'], 'Not valid URL/embed code.');
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
    $source_field = $this->configuration['youtube']['source_field'];
    foreach ($this->validationRegexp as $regexp) {
      $property_name = $media->{$source_field}->first()->mainPropertyName();
      if (preg_match($regexp, $media->{$source_field}->{$property_name}, $matches)) {
        return $matches;
      }
    }

    return FALSE;
  }
}
