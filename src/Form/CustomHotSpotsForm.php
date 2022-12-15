<?php

namespace Drupal\image_hotspots\Form;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Custom HotSpots form.
 */
class CustomHotSpotsForm extends FormBase {

  /**
   * The variable containing the request stack.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  protected $requestStack;

  /**
   * Dependency injection through the constructor.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   * @param \Symfony\Component\HttpFoundation\RequestStack $requestStack
   *   The request stack service.
   */
  public function __construct(ConfigFactoryInterface $config_factory, RequestStack $requestStack) {
    $this->configFactory = $config_factory;
    $this->requestStack = $requestStack;
    $this->request = $requestStack->getCurrentRequest();
  }

  /**
   * Dependency injection create.
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('request_stack')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'custom_hotspots_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['form-messages'] = [
      '#type' => 'markup',
      '#markup' => '<div class="form-messages"></div>',
    ];
    $form['hotspots-title'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Title'),
      '#value' => '',
      '#attributes' => [
        'class' => ['hotspots-title'],
      ],
    ];
    $form['hotspots-description'] = [
      '#type' => 'text_format',
      '#title' => $this->t('Description'),
      '#format' => 'hotspots',
      '#attributes' => [
        'class' => ['hotspots-description'],
      ],
    ];
    $form['hotspots-link'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Link'),
      '#value' => '',
      '#attributes' => [
        'class' => ['hotspots-link'],
      ],
    ];
    $form['hotspots-target'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Open in a new window'),
      '#default_value' => '_blank',
      '#options' => [
        '_blank' => $this->t('Open in a new windo'),
      ],
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {}

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Form submit is in javascript for Mulesoft API.
  }

}
