<?php

namespace Drupal\bht_location\Controller;

use Drupal\bht_location\LocationEntityHelper;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Routing\UrlGeneratorInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * Acts on specified location routes to manage the users of a location.
 */
class LocationUsers extends ControllerBase {

  /**
   * @var \Drupal\bht_location\LocationEntityHelper
   */
  protected $locationEntityHelper;

  /**
   * @var \Drupal\Core\Language\LanguageManagerInterface
   */
  protected $languageManager;

  /**
   * @var \Drupal\Core\Routing\UrlGeneratorInterface
   */
  protected $urlGenerator;

  /**
   * @inheritDoc
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('bht_location.entity_helper'),
      $container->get('language_manager'),
      $container->get('url_generator'),
    );
  }

  /**
   * @inheritDoc
   */
  public function __construct(LocationEntityHelper $location_entity_helper, LanguageManagerInterface $language_manager, UrlGeneratorInterface $url_generator) {
    $this->locationEntityHelper = $location_entity_helper;
    $this->languageManager = $language_manager;
    $this->urlGenerator = $url_generator;
  }

  /**
   * Removes the current user as a therapist from the given location.
   *
   * @param $node
   */
  public function removeUserFromLocation($node) {
    $this->locationEntityHelper->setEntity($node);
    $this->locationEntityHelper->removeUser();

    // Redirect user to the user page.
    $language = $this->languageManager->getCurrentLanguage();
    $url = $this->urlGenerator->generateFromRoute('user.page', [], ['language' => $language]);
    return new RedirectResponse($url);
  }
}