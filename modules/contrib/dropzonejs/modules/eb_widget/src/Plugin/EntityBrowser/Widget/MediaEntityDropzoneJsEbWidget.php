<?php

/**
 * Contains \Drupal\dropzonejs_eb_widget\Plugin\EntityBrowser\Widget\MediaEntityDropzoneJsEbWidget.
 */

namespace Drupal\dropzonejs_eb_widget\Plugin\EntityBrowser\Widget;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Link;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\dropzonejs\DropzoneJsUploadSaveInterface;
use Drupal\dropzonejs\Events\DropzoneMediaEntityCreateEvent;
use Drupal\dropzonejs\Events\Events;
use Drupal\entity_browser\WidgetValidationManager;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * Provides an Entity Browser widget that uploads new files and saves media
 * entities.
 *
 * @EntityBrowserWidget(
 *   id = "dropzonejs_media_entity",
 *   label = @Translation("Media Entity DropzoneJS"),
 *   description = @Translation("Adds DropzoneJS upload integration that saves Media entities.")
 * )
 */
class MediaEntityDropzoneJsEbWidget extends DropzoneJsEbWidget {

  /**
   * Module handler service.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * Media bundle.
   *
   * @var \Drupal\media_entity\MediaBundleInterface
   */
  protected $bundle;

  /**
   * Constructs widget plugin.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Symfony\Component\EventDispatcher\EventDispatcherInterface $event_dispatcher
   *   Event dispatcher service.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager service.
   * @param \Drupal\entity_browser\WidgetValidationManager $validation_manager
   *   The Widget Validation Manager service.
   * @param \Drupal\dropzonejs\DropzoneJsUploadSaveInterface $dropzonejs_upload_save
   *   The upload saving dropzonejs service.
   * @param \Drupal\Core\Session\AccountProxyInterface $current_user
   *   The current user service.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler service.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EventDispatcherInterface $event_dispatcher, EntityTypeManagerInterface $entity_type_manager, WidgetValidationManager $validation_manager, DropzoneJsUploadSaveInterface $dropzonejs_upload_save, AccountProxyInterface $current_user, ModuleHandlerInterface $module_handler) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $event_dispatcher, $entity_type_manager, $validation_manager, $dropzonejs_upload_save, $current_user);
    $this->moduleHandler = $module_handler;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('event_dispatcher'),
      $container->get('entity_type.manager'),
      $container->get('plugin.manager.entity_browser.widget_validation'),
      $container->get('dropzonejs.upload_save'),
      $container->get('current_user'),
      $container->get('module_handler')
    );
  }


  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'media_entity_bundle' => '',
    ] + parent::defaultConfiguration();
  }

  /**
   * Returns the media bundle that this widget creates.
   *
   * @return \Drupal\media_entity\MediaBundleInterface
   */
  protected function getBundle() {
    if (!$this->bundle) {
      $this->bundle = $this->entityTypeManager
        ->getStorage('media_bundle')
        ->load($this->configuration['media_entity_bundle']);
    }

    return $this->bundle;
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildConfigurationForm($form, $form_state);

    $form['media_entity_bundle'] = [
      '#type' => 'select',
      '#title' => $this->t('Media type'),
      '#required' => TRUE,
      '#description' => $this->t('The type of media entity to create from the uploaded file(s).'),
    ];

    $bundle = $this->getBundle();
    if ($bundle) {
      $form['media_entity_bundle']['#default_value'] = $bundle->id();
    }

    $bundles = $this->entityTypeManager->getStorage('media_bundle')->loadMultiple();

    if (!empty($bundles)) {
      foreach ($bundles as $bundle) {
        $form['media_entity_bundle']['#options'][$bundle->id()] = $bundle->label();
      }
    }
    else {
      $form['media_entity_bundle']['#disabled'] = TRUE;
      $form['media_entity_bundle']['#description'] = $this->t('You must @create_bundle before using this widget.', [
        '@create_bundle' => Link::createFromRoute($this->t('create a media bundle'), 'media.bundle_add')->toString()
      ]);
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function calculateDependencies() {
    $dependencies = parent::calculateDependencies();

    // Depend on the media bundle this widget creates.
    $bundle = $this->getBundle();
    $dependencies[$bundle->getConfigDependencyKey()][] = $bundle->getConfigDependencyName();
    $dependencies['module'][] = 'media_entity';

    return $dependencies;
  }

  /**
   * {@inheritdoc}
   */
  public function prepareEntities(array $form, FormStateInterface $form_state) {
    $entities = [];

    foreach (parent::prepareEntities($form, $form_state) as $file) {
      $entities[] = $this->entityTypeManager->getStorage('media')->create([
        'bundle' => $this->getBundle()->id(),
        $this->getBundle()->getTypeConfiguration()['source_field'] => $file,
        'uid' => $this->currentUser->id(),
        'status' => TRUE,
        'type' => $this->getBundle()->getType()->getPluginId(),
      ]);
    }

    return $entities;
  }

  /**
   * {@inheritdoc}
   */
  public function submit(array &$element, array &$form, FormStateInterface $form_state) {
    $media_entities = $this->prepareEntities($form, $form_state);
    $source_field = $this->getBundle()->getTypeConfiguration()['source_field'];

    foreach ($media_entities as &$media_entity) {
      $file = $media_entity->$source_field->entity;
      // This saves the file.
      $file = file_move($file, $this->getUploadLocation(), FILE_EXISTS_RENAME);
      $file = $media_entity->$source_field->entity = $file;
      $event = $this->eventDispatcher->dispatch(Events::MEDIA_ENTITY_CREATE, new DropzoneMediaEntityCreateEvent($media_entity, $file, $form, $form_state, $element));
      $media_entity = $event->getMediaEntity();
      $media_entity->save();
    }

    if (!empty(array_filter($media_entities))) {
      $this->selectEntities($media_entities, $form_state);
      $this->clearFormValues($element, $form_state);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function __sleep() {
    return array_diff(parent::__sleep(), ['bundle']);
  }
}
