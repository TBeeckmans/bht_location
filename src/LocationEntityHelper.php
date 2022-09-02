<?php

namespace Drupal\bht_location;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;

class LocationEntityHelper {

  /**
   * @var AccountProxyInterface $account
   */
  protected $account;

  /**
   * @var EntityTypeManagerInterface $entityTypeManager
   */
  protected $entityTypeManager;

  /**
   * @var \Drupal\Core\Messenger\MessengerInterface $messenger
   */
  protected $messenger;

  /**
   * @var \Drupal\node\Entity\Node $entity
   */
  protected $entity;

  /**
   * @inheritDoc
   */
  public function __construct(AccountProxyInterface $account, EntityTypeManagerInterface $entityTypeManager, MessengerInterface $messenger) {
    $this->account = $account;
    $this->entityTypeManager = $entityTypeManager;
    $this->messenger = $messenger;
  }

  public function setEntityId(int $entityId) {
    $this->setEntity(
      $this->entityTypeManager->getStorage('node')
        ->load($entityId)
    );
    return $this->entity->id();
  }

  public function setEntity(EntityInterface $entity) {
    $this->entity = $entity;
    return $this->entity;
  }

  public function getEntity() {
    return $this->entity ?? NULL;
  }

  public function getEntityId() {
    return $this->entity->id() ?? NULL;
  }

  public function addUser() {
    if ($this->getEntity()) {
      // Check if the user is already referenced.
      $therapists = $this->entity->get('field_therapist')->getValue();
      foreach ($therapists as $therapist) {
        if ($therapist['target_id'] == $this->account->id()) {
          $this->messenger->addWarning(
            new TranslatableMarkup(
              'The user %user is already related to %location.',
              [
                '%user' => $this->account->getDisplayName(),
                '%location' => $this->entity->label(),
              ]
            )
          );
          return TRUE;
        }
      }

      // Add the user as an entity reference to the therapist field.
      $this->entity->get('field_therapist')
        ->appendItem(['target_id' => $this->account->id()]);

      $this->entity->save();

      $this->messenger->addStatus(
        new TranslatableMarkup(
          'The user %user is related to %location.',
          [
            '%user' => $this->account->getDisplayName(),
            '%location' => $this->entity->label(),
          ]
        )
      );
    }
  }

  public function removeUser() {
    if ($this->getEntity()) {
      // Get the indexes that reference the user.
      $indexes = [];
      $therapists = $this->entity->get('field_therapist')->getValue();
      foreach ($therapists as $index => $therapist) {
        if ($therapist['target_id'] == $this->account->id()) {
          $indexes[] = $index;
        }
      }

      // Remove the entity reference for the user from the therapist field.
      if (!empty($indexes)) {
        foreach ($indexes as $index) {
          $this->entity->get('field_therapist')
            ->removeItem($index);
        }

        $this->entity->save();
        $this->messenger->addStatus(
          new TranslatableMarkup(
            'The user %user has been removed from %location.',
            [
              '%user' => $this->account->getDisplayName(),
              '%location' => $this->entity->label(),
            ]
          )
        );
        return;
      }

      $this->messenger->addError(
        new TranslatableMarkup(
          'The user %user is not related to %location.',
          [
            '%user' => $this->account->getDisplayName(),
            '%location' => $this->entity->label(),
          ]
        )
      );
    }
  }

}