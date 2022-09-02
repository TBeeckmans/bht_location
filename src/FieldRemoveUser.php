<?php

namespace Drupal\bht_location;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Field\FieldItemList;
use Drupal\Core\Link;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\TypedData\ComputedItemListTrait;
use Drupal\Core\Url;

/**
 * A computed property for the locations node to remove a referenced user.
 */
class FieldRemoveUser extends FieldItemList {

  use ComputedItemListTrait;

  public function access($operation = 'view', AccountInterface $account = NULL, $return_as_object = FALSE) {
    if (!$account) {
      $account = \Drupal::currentUser();
    }

    /* @var $entity \Drupal\node\Entity\Node */
    $entity = $this->getEntity();
    // Only active members have permissions to update location node entities.
    if ($entity->access('update', $account)) {
      // Ensure the user is referenced from the therapist field.
      $userReferences = $entity->get('field_therapist')->getValue();
      if (!empty($userReferences)) {
        foreach ($userReferences as $userReference) {
          if ($userReference['target_id'] == $account->id()) {
            return AccessResult::allowed();
          }
        }
      }
    }

    return AccessResult::forbidden();
  }

  /**
   * {@inheritdoc}
   */
  protected function computeValue() {
    $entity = $this->getEntity();
    if ($entity->getEntityTypeId() !== 'node' || $entity->bundle() !== 'location' || $entity->isNew()) {
      return;
    }
    $this->ensurePopulated();
  }

  /**
   * Computes the calculated value for this item list as link.
   */
  protected function ensurePopulated() {
    if (!isset($this->list[0])) {
      /* @var $languageManager \Drupal\Core\Language\LanguageManagerInterface */
      $languageManager = \Drupal::service('language_manager');
      /* @var $renderer \Drupal\Core\Render\Renderer */
      $renderer = \Drupal::service('renderer');
      /* @var $entity \Drupal\node\Entity\Node */
      $entity = $this->getEntity();

      // Create render array to the location remove user route.
      $language = $languageManager->getCurrentLanguage();
      $url = Url::fromRoute(
        'bht_location.remove.user',
        ['node' => $entity->id()],
        ['language' => $language]
      );
      $link = Link::fromTextAndUrl(
        $this->t('I don\'t work here anymore.'),
        $url
      )->toRenderable();
      $link['#attributes']['class'] = ['button', 'button-action'];
      $this->list[0] = $this->createItem(0, $renderer->render($link));
    }
  }
}
