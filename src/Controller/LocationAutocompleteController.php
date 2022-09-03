<?php

namespace Drupal\bht_location\Controller;

use Drupal\Component\Utility\Xss;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\Element\EntityAutocomplete;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class LocationAutocompleteController extends ControllerBase {

  /**
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * @var \Drupal\Core\Database\Connection
   */
  protected $database;

  /**
   * @inheritDoc
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('database')
    );
  }

  /**
   * @inheritDoc
   */
  public function __construct(EntityTypeManagerInterface $entitytype_manager, Connection $database) {
    $this->entityTypeManager = $entitytype_manager;
    $this->database = $database;
  }

  /**
   * Defines a route controller for locations autocomplete form elements.
   */
  public function handleAutocomplete(Request $request) {
    $results = [];

    // Get the typed string from the URL, if it exists.
    $input = $request->query->get('q');
    if (!$input) {
      return new JsonResponse($results);
    }

    $input = Xss::filter($input);

    /* @var $query \Drupal\Core\Database\Query */
    $query = $this->database->select('node', 'n');
    $query->join('node_field_data', 'nd', 'n.nid = nd.nid');
    $query->addField('n', 'nid');
    $condition = $query->orConditionGroup()
      ->condition('nd.title', "%$input%", 'LIKE')
      ->condition('nd.address__address_line1', "%$input%", 'LIKE')
      ->condition('nd.address__locality', "%$input%", 'LIKE')
      ->condition('nd.address__postal_code', "%$input%", 'LIKE');
    $query->condition('n.type', 'location')
      ->condition($condition);
    $query->orderBy('nd.title', 'ASC');
    $query->range(0, 10);
    $nids = $query->execute()->fetchCol();

    $nodes = $nids ? $this->entityTypeManager->getStorage('node')
      ->loadMultiple($nids) : [];

    foreach ($nodes as $node) {
      $address = $node->get('address')->get(0)->getValue();
      $label = [
        $node->getTitle(),
        $address['address_line1'],
        $address['postal_code'] . ' ' . $address['locality'],
      ];

      $results[] = [
        'value' => EntityAutocomplete::getEntityLabels([$node]),
        'label' => implode(', ', $label),
      ];
    }

    return new JsonResponse($results);
  }
}


