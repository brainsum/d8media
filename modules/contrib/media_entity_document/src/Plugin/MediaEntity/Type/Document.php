<?php
namespace Drupal\media_entity_document\Plugin\MediaEntity\Type;

use Drupal\media_entity\MediaInterface;
use Drupal\media_entity\MediaTypeBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Provides media type plugin for Document.
 *
 * @MediaType(
 *   id = "document",
 *   label = @Translation("Document"),
 *   description = @Translation("Provides business logic and metadata for local documents.")
 * )
 */
class Document extends MediaTypeBase {

  /**
   * {@inheritdoc}
   */
  public function providedFields() {
    return [
      'mime' => t('File MIME'),
      'size' => t('Size'),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getField(MediaInterface $media, $name) {
    $source_field = $this->configuration['source_field'];
    $property_name = $media->{$source_field}->first()->mainPropertyName();

    // Get the file document.
    /** @var \Drupal\file\FileInterface $file */
    $file = $this->entityTypeManager->getStorage('file')->load($media->{$source_field}->first()->{$property_name});

    // Return the field.
    switch ($name) {
      case 'mime':
        return !$file->filemime->isEmpty() ? $file->getMimeType() : FALSE;

      case 'size':
        $size = $file->getSize();
        return $size ? $size : FALSE;
    }

    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    /** @var \Drupal\media_entity\MediaBundleInterface $bundle */
    $bundle = $form_state->getFormObject()->getEntity();
    $options = [];
    $allowed_field_types = ['file'];
    foreach ($this->entityFieldManager->getFieldDefinitions('media', $bundle->id()) as $field_name => $field) {
      if (in_array($field->getType(), $allowed_field_types) && !$field->getFieldStorageDefinition()->isBaseField()) {
        $options[$field_name] = $field->getLabel();
      }
    }

    $form['source_field'] = [
      '#type' => 'select',
      '#title' => t('Field with source information'),
      '#description' => t('Field on media entity that stores Document file. You can create a bundle without selecting a value for this dropdown initially. This dropdown can be populated after adding fields to the bundle.'),
      '#default_value' => empty($this->configuration['source_field']) ? NULL : $this->configuration['source_field'],
      '#options' => $options,
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function thumbnail(MediaInterface $media) {
    $source_field = $this->configuration['source_field'];
    /** @var \Drupal\file\FileInterface $file */
    $file = $this->entityTypeManager->getStorage('file')->load($media->{$source_field}->target_id);
    $mimetype = $file->getMimeType();
    $mimetype = explode('/', $mimetype);
    $thumbnail = $this->config->get('icon_base') . "/{$mimetype[0]}-{$mimetype[1]}.png";

    if (!is_file($thumbnail)) {
      $thumbnail = $this->config->get('icon_base') . "/{$mimetype[1]}.png";

      if (!is_file($thumbnail)) {
        $thumbnail = $this->config->get('icon_base') . '/document.png';
      }
    }

    return $thumbnail;
  }

}
