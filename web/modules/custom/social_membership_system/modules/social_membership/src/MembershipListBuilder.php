<?php

namespace Drupal\social_membership;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;

/**
 * Defines a class to build a listing of Membership entities.
 */
class MembershipListBuilder extends EntityListBuilder {

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['member_id'] = $this->t('Member ID');
    $header['user'] = $this->t('User');
    $header['period'] = $this->t('Period');
    $header['status'] = $this->t('Status');
    $header['created'] = $this->t('Created');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    /** @var \Drupal\social_membership\Entity\Membership $entity */
    $member_id_entity = $entity->getMemberIDEntity();
    
    if ($member_id_entity) {
      $row['member_id'] = $member_id_entity->getMemberID();
      $user = $member_id_entity->getUser();
      $row['user'] = $user ? $user->getDisplayName() : $this->t('No user');
    } else {
      $row['member_id'] = $this->t('Missing');
      $row['user'] = $this->t('Missing');
    }
    
    $row['period'] = $entity->getStartDate() . ' - ' . $entity->getEndDate();
    
    if ($entity->isActive()) {
      $row['status'] = $this->t('Active');
    } elseif ($entity->isExpired()) {
      $row['status'] = $this->t('Expired');
    } else {
      $row['status'] = $this->t('Future');
    }
    
    $row['created'] = \Drupal::service('date.formatter')->format($entity->getCreatedTime(), 'short');
    return $row + parent::buildRow($entity);
  }

}