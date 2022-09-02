<?php

namespace Drupal\bht_location\Form;

use Drupal\Core\Entity\Element\EntityAutocomplete;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Messenger\Messenger;
use Drupal\Core\Routing\UrlGeneratorInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\bht_location\LocationEntityHelper;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;

class BhtLocationAddUser extends FormBase {

  /**
   * @var AccountProxyInterface
   */
  protected $currentUser;

  /**
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * @var \Drupal\Core\Messenger\MessengerInterface
   */
  protected $messenger;

  /**
   * @var \Drupal\bht_location\LocationEntityHelper
   */
  protected $locationEntityHelper;

  /**
   * @var \Drupal\Core\Language\LanguageManagerInterface
   */
  protected $languageManager;

  /**
   * @inheritDoc
   */
  public function getFormId() {
    return 'bht_location_add_user';
  }

  /**
   * @inheritDoc
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('current_user'),
      $container->get('entity_type.manager'),
      $container->get('messenger'),
      $container->get('bht_location.entity_helper'),
      $container->get('language_manager'),
    );
  }

  /**
   * @inheritDoc
   */
  public function __construct(AccountProxyInterface $current_user, EntityTypeManagerInterface $entity_type_manager, Messenger $messenger, LocationEntityHelper $location_entity_helper, LanguageManagerInterface $language_manager) {
    $this->currentUser = $current_user;
    $this->entityTypeManager = $entity_type_manager;
    $this->messenger = $messenger;
    $this->locationEntityHelper = $location_entity_helper;
    $this->languageManager = $language_manager;
  }

  /**
   * @inheritDoc
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form = [];

    $form['description'] = [
      '#type' => 'html_tag',
      '#tag' => 'p',
      '#value' => $this->t(
        'You can add your workplace to the list of Belgian Hand Therapists locations by searching if the location name or address is already known in our database, select the location and click on the button %existing-button. If it is not listed in the results, you can click on the button %new-button to add your workplace to the list of Belgian Hand Therapists locations.',
        [
          '%existing-button' => $this->t('Add this location to my workplaces'),
          '%new-button' => $this->t('Create a new location'),
        ]
      ),
    ];

    $form['location'] = [
      '#type' => 'textfield',
      '#title' => 'Location',
      '#autocomplete_route_name' => 'bht_location.autocomplete.locations',
    ];

    $form['actions'] = [
      '#type' => 'actions',
    ];
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Add this location to my workplaces'),
    ];
    $form['actions']['create'] = [
      '#type' => 'submit',
      '#value' => $this->t('Create a new location'),
      '#submit' => ['::createLocation'],
      '#limit_validation_errors' => [],
    ];

    return $form;
  }

  /**
   * @inheritDoc
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $locationId = EntityAutocomplete::extractEntityIdFromAutocompleteInput($form_state->getValue('location'));
    $entity = $this->entityTypeManager->getStorage('node')->load($locationId);
    $this->locationEntityHelper->setEntity($entity);
    $this->locationEntityHelper->addUser();
  }

  public function createLocation(array &$form, FormStateInterface $form_state) {
    // Redirect user to the location add page.
    $language = $this->languageManager->getCurrentLanguage();
    $url = Url::fromRoute('node.add', ['node_type' => 'location'], ['language' => $language]);
    $form_state->setRedirectUrl($url);
  }
}