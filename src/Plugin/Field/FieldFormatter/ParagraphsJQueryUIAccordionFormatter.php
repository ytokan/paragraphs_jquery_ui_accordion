<?php

/**
 * @file
 * Contains \Drupal\paragraphs_jquery_ui_accordion\Plugin\Field\FieldFormatter\ParagraphsJQueryUIAccordionFormatter.
 */

namespace Drupal\paragraphs_jquery_ui_accordion\Plugin\Field\FieldFormatter;

use Drupal\Component\Utility\Html;
use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Entity\EntityTypeBundleInfoInterface;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Plugin implementation of the 'paragraphs_jquery_ui_accordion_formatter' formatter.
 *
 * @FieldFormatter(
 *   id = "paragraphs_jquery_ui_accordion_formatter",
 *   label = @Translation("Paragraphs jQuery UI Accordion"),
 *   field_types = {
 *     "entity_reference_revisions"
 *   }
 * )
 */
class ParagraphsJQueryUIAccordionFormatter extends FormatterBase implements ContainerFactoryPluginInterface {

  /**
   * The entity type bundle info service.
   *
   * @var \Drupal\Core\Entity\EntityTypeBundleInfoInterface
   */
  protected $bundleInfo;

  /**
   * The entity field manager.
   *
   * @var \Drupal\Core\Entity\EntityFieldManagerInterface
   */
  protected $entityFieldManager;

  /**
   * The entity type id.
   *
   */
  protected $entityTypeId;

  /**
   * ParagraphsJQueryUIAccordionFormatter constructor.
   *
   * @param string $plugin_id
   *   The plugin_id for the formatter.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Field\FieldDefinitionInterface $field_definition
   *   The definition of the field to which the formatter is associated.
   * @param array $settings
   *   The formatter settings.
   * @param string $label
   *   The formatter label display setting.
   * @param string $view_mode
   *   The view mode.
   * @param array $third_party_settings
   *   Any third party settings settings.
   * @param \Drupal\Core\Entity\EntityTypeBundleInfoInterface $bundle_info
   *   Entity type manager service.
   * @param \Drupal\Core\Entity\EntityFieldManagerInterface $entity_field_manager
   *   The entity field manager.
   */
  public function __construct($plugin_id, $plugin_definition, FieldDefinitionInterface $field_definition, array $settings, $label, $view_mode, array $third_party_settings, EntityTypeBundleInfoInterface $bundle_info, EntityFieldManagerInterface $entity_field_manager) {
    parent::__construct($plugin_id, $plugin_definition, $field_definition, $settings, $label, $view_mode, $third_party_settings);
    $this->bundleInfo = $bundle_info;
    $this->entityFieldManager = $entity_field_manager;
    $this->entityTypeId = $this->fieldDefinition->getFieldStorageDefinition()->getSetting('target_type');
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $plugin_id,
      $plugin_definition,
      $configuration['field_definition'],
      $configuration['settings'],
      $configuration['label'],
      $configuration['view_mode'],
      $configuration['third_party_settings'],
      $container->get('entity_type.bundle.info'),
      $container->get('entity_field.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
        'bundle' => '',
        'title' => '',
        'content' => '',
        'autoscroll' => 0,
      ] + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $form = parent::settingsForm($form, $form_state);

    $bundles = $this->getBundles();
    $bundle_fields = $this->getBundleFields();

    $form['bundle'] = [
      '#type' => 'select',
      '#title' => $this->t('Paragraph bundle'),
      '#default_value' =>$this->getSetting('bundle'),
      '#options' => $bundles,
    ];
    $form['title'] = [
      '#type' => 'select',
      '#title' => $this->t('Paragraph title'),
      '#default_value' => $this->getSetting('title'),
      '#options' => $bundle_fields,
    ];
    $form['content'] = [
      '#type' => 'select',
      '#title' => $this->t('Paragraph content'),
      '#default_value' => $this->getSetting('content'),
      '#options' => $bundle_fields,
    ];
    $form['autoscroll'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('AutoScroll'),
      '#description' => $this->t('Scrolls to active accordion item.'),
      '#default_value' => $this->getSetting('autoscroll'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = parent::settingsSummary();

    $settings = $this->getSettings();

    $summary[] = t('Paragraph bundle: %bundle', ['%bundle' => $settings['bundle']]);
    $summary[] = t('Paragraph title: %title', ['%title' => $settings['title']]);
    $summary[] = t('Paragraph content: %content', ['%content' => $settings['content']]);
    $summary[] = t('AutoScroll: %autoscroll', ['%autoscroll' => $settings['autoscroll'] ? t('enabled') : t('disabled')]);

    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = [];

    $accordion_id = $this->getAccordionId($items->getEntity()->id());

    $elements[0]['accordion'] = [
      '#type' => 'container',
      '#attributes' => ['id' => $accordion_id],
      '#attached' => [
        'library' => 'paragraphs_jquery_ui_accordion/accordion',
        'drupalSettings' => [
          'paragraphs_jquery_ui_accordion' => [
            'id' => $accordion_id,
            'autoscroll' => $this->getSetting('autoscroll')
          ]
        ]
      ]
    ];

    foreach ($items as $delta => $item) {
      $title = $item->entity->get($this->getSetting('title'))->value;
      $content = $item->entity->get($this->getSetting('content'))->value;
      $id = Html::getUniqueId($title);

      $elements[0]['accordion'][$delta] = [
        '#theme' => 'paragraphs_jquery_ui_accordion_formatter',
        '#title' => $title,
        '#content' => $content,
        '#id' =>  $id,
      ];
    }

    return $elements;
  }

  /**
   * {@inheritdoc}
   */
  public static function isApplicable(FieldDefinitionInterface $field_definition) {
    $storage = $field_definition->getFieldStorageDefinition();
    return $storage->isMultiple() && $storage->getSetting('target_type') === 'paragraph';
  }

  /**
   * Gets a bundles array suitable for form options.
   *
   * @return array
   *   The bundles array that can be passed to form element of type select.
   */
  protected function getBundles() {
    foreach ($this->bundleInfo->getBundleInfo($this->entityTypeId) as $key => $bundle) {
      $bundles[$key] = $bundle['label'];
    }
    return isset($bundles) ? $bundles : [];
  }

  /**
   * Gets a bundle fields array suitable for form options.
   *
   * @return array
   *   The fields array that can be passed to form element of type select.
   */
  protected function getBundleFields() {
    foreach ($this->getBundles() as $bundle_name => $bundle) {
      $field_definitions = $this->entityFieldManager->getFieldDefinitions($this->entityTypeId, $bundle_name);
      foreach ($field_definitions as $field_name => $field_definition) {
        if (!$field_definition->getFieldStorageDefinition()->isBaseField()) {
          $bundle_fields[$field_name] = $field_definition->getLabel();
        }
      }
    }
    return isset($bundle_fields) ? $bundle_fields : [];
  }

  /**
   * Generates unique accordion identifier for html attribute.
   *
   * @param $id
   *  Unique identifier.
   *
   * @return string
   *   Returns unique accordion id.
   */
  protected function getAccordionId($id) {
    return Html::getUniqueId('accordion-' . $id);
  }

}