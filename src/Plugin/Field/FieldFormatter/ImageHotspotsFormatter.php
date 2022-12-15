<?php

namespace Drupal\image_hotspots\Plugin\Field\FieldFormatter;

use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\image\Plugin\Field\FieldFormatter\ImageFormatter;
use Drupal\image_hotspots\Entity\ImageHotspot;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Plugin implementation of the 'image_with_hotspots' formatter.
 *
 * @FieldFormatter(
 *   id = "image_with_hotspots",
 *   label = @Translation("Image with Hotspots"),
 *   field_types = {
 *     "image"
 *   }
 * )
 */
class ImageHotspotsFormatter extends ImageFormatter implements ContainerFactoryPluginInterface {

  /**
   * The language manager.
   *
   * @var \Drupal\Core\Language\LanguageManagerInterface
   */
  protected $languageManager;

  /**
   * Constructs an ImageFormatter object.
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
   * @param \Drupal\Core\Session\AccountInterface $current_user
   *   The current user.
   * @param \Drupal\Core\Entity\EntityStorageInterface $image_style_storage
   *   The image style storage.
   * @param \Drupal\Core\Language\LanguageManagerInterface $language_manager
   *   The language manager.
   */
  public function __construct($plugin_id, $plugin_definition, FieldDefinitionInterface $field_definition, array $settings, $label, $view_mode, array $third_party_settings, AccountInterface $current_user, EntityStorageInterface $image_style_storage, LanguageManagerInterface $language_manager) {
    parent::__construct($plugin_id, $plugin_definition, $field_definition, $settings, $label, $view_mode, $third_party_settings, $current_user, $image_style_storage);
    $this->languageManager = $language_manager;
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
      $container->get('current_user'),
      $container->get('entity_type.manager')->getStorage('image_style'),
      $container->get('language_manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
      'image_hotspots_style' => 'tootip',
    ] + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $element['image_hotspots_style'] = [
      '#title' => $this->t('Hotspot style'),
      '#type' => 'select',
      '#options' => [
        'tooltip' => $this->t('Tooltip (hover)'),
        'modal' => $this->t('Modal (click)'),
      ],
      '#default_value' => $this->getSetting('image_hotspots_style'),
    ];

    return $element + parent::settingsForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = parent::viewElements($items, $langcode);
    $image_hotspots_style = $this->getSetting('image_hotspots_style');

    $image_style = !empty($this->getSetting('image_style')) ? $this->getSetting('image_style') : 'none';
    $field_name = $items->getName();
    $files = $this->getEntitiesToView($items, $langcode);

    $info = [
      'field_name' => $field_name,
      'image_style' => $image_style,
    ];

    $default_lang = $this->languageManager->getDefaultLanguage()->getId();

    /** @var \Drupal\file\FileInterface $file */
    foreach ($files as $delta => $file) {
      $info['fid'] = $file->id();
      $hotspots = ImageHotspot::loadByTarget($info);

      // Get translations if they exist.
      if ($langcode != $default_lang) {
        foreach ($hotspots as $hid => $hotspot) {
          if ($hotspot->hasTranslation($langcode)) {
            $hotspots[$hid] = $hotspot->getTranslation($langcode);
          }
        }
      }

      $editable = FALSE;
      $translatable = FALSE;
      // Load library for edit hotspots if user in permission.
      if ($this->currentUser->hasPermission('edit image hotspots')) {
        $editable = TRUE;
        // Only allow translation if the editor is not viewing the default lang.
        if ($langcode != $default_lang) {
          $translatable = TRUE;
          $elements[$delta]['#attached']['library'][] = 'image_hotspots/translate';
        }
        else {
          $elements[$delta]['#attached']['library'][] = 'image_hotspots/edit';
        }
      }

      // Attach hotspots data to js settings.
      /** @var \Drupal\image_hotspots\Entity\ImageHotspot $hotspot */
      $hotspots_to_show = [];
      foreach ($hotspots as $hid => $hotspot) {
        $title = $hotspot->getTitle();
        $description = $hotspot->getDescription();
        $link = $hotspot->getLink();
        $target = $hotspot->getTargetLink();
        $value = [
          'title' => $title,
          'description' => !is_null($description) ? $description : '',
          'link' => !is_null($link) ? $link : '',
          'target' => !is_null($target) ? $target : '',
        ];
        foreach ($hotspot->getCoordinates() as $coordinate => $val) {
          $value[$coordinate] = $val;
        }
        $hotspots_to_show[$hid] = $value;
      }

      // Add cache tag 'hotspots:field_name:fid:image_style'.
      $elements[$delta]['#cache']['tags'][] = 'hotspots:' . $info['field_name'] . ':' . $info['fid'] . ':' . $info['image_style'];
      // Attache libraries.
      $elements[$delta]['#attached']['drupalSettings']['image_hotspots'][$field_name][$file->id()][$image_style][$image_hotspots_style][$langcode]['hotspots'] = $hotspots_to_show;
      $elements[$delta]['#attached']['library'][] = 'image_hotspots/view';
      $elements[$delta]['#attached']['library'][] = 'core/drupal.dialog.ajax';

      // Change element theme from 'image_formatter'.
      $elements[$delta]['#theme'] = 'image_formatter_with_hotspots';
      // Add additional info for render.
      $elements[$delta]['#info'] = $info;
      $elements[$delta]['#info']['hotspots_style'] = $image_hotspots_style;
      $elements[$delta]['#info']['langcode'] = $langcode;
      $elements[$delta]['#info']['editable'] = $editable;
      $elements[$delta]['#info']['translatable'] = $translatable;
    }

    return $elements;
  }

}
