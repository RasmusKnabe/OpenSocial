<?php

namespace Drupal\social_member_id;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;

/**
 * Defines a class to build a listing of Member ID entities.
 */
class MemberIDListBuilder extends EntityListBuilder {

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['member_id'] = $this->t('Member ID');
    $header['user'] = $this->t('User');
    $header['created'] = $this->t('Created');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    /** @var \Drupal\social_member_id\Entity\MemberID $entity */
    $row['member_id'] = $entity->getMemberID();
    $user = $entity->getUser();
    $row['user'] = $user ? $user->getDisplayName() : $this->t('No user');
    $row['created'] = \Drupal::service('date.formatter')->format($entity->getCreatedTime(), 'short');
    return $row + parent::buildRow($entity);
  }

}