<?php

/**
 * Contains \Drupal\media_entity_youtube\Plugin\MediaEntity\Type\YouTube.
 */

namespace Drupal\media_entity_youtube\Plugin\MediaEntity\Type;

use Drupal\Component\Plugin\PluginBase;
use Drupal\Core\StringTranslation\StringTranslationTrait;
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
  public function getField($name) {
    // @TODO: Implement.
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm() {
    // @TODO: Implement.
  }

  /**
   * {@inheritdoc}
   */
  public function validate() {
    // @TODO: Implement.
  }

}
