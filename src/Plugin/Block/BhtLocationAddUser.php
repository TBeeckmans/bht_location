<?php

namespace Drupal\bht_location\Plugin\Block;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\Form\FormBuilderInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Session\AccountInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Block rendering the form to add a user to an existing or new location.
 *
 * @Block(
 *   id = "bht_location_add_user",
 *   admin_label = @Translation("Add user to a location")
 * )
 */
class BhtLocationAddUser extends BlockBase implements ContainerFactoryPluginInterface {
  /**
   * @var \Drupal\Core\Form\FormBuilderInterface
   */
  protected $formBuilder;

  /**
   * @inheritDoc
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('form_builder'),
    );
  }

  /**
   * @inheritDoc
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, FormBuilderInterface $form_builder) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    $this->formBuilder = $form_builder;
  }

  /**
   * @inheritDoc
   */
  public function build() {
    $build = [];

    $build['form'] = $this->formBuilder->getForm(
      'Drupal\bht_location\Form\BhtLocationAddUser'
    );

    return $build;
  }

  /**
   * @inheritDoc
   */
  protected function blockAccess(AccountInterface $account) {
    return AccessResult::allowedIf(in_array('member', $account->getRoles()));
  }
}
