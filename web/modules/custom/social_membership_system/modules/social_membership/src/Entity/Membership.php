<?php

namespace Drupal\social_membership\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\social_member_id\Entity\MemberID;

/**
 * Defines the Membership entity.
 *
 * @ContentEntityType(
 *   id = "membership",
 *   label = @Translation("Membership"),
 *   base_table = "membership",
 *   entity_keys = {
 *     "id" = "id",
 *     "uuid" = "uuid",
 *   },
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\social_membership\MembershipListBuilder",
 *     "views_data" = "Drupal\views\EntityViewsData",
 *     "form" = {
 *       "default" = "Drupal\Core\Entity\ContentEntityForm",
 *       "add" = "Drupal\Core\Entity\ContentEntityForm",
 *       "edit" = "Drupal\Core\Entity\ContentEntityForm",
 *       "delete" = "Drupal\Core\Entity\ContentEntityDeleteForm",
 *     },
 *     "access" = "Drupal\Core\Entity\EntityAccessControlHandler",
 *   },
 *   links = {
 *     "canonical" = "/membership/{membership}",
 *     "add-form" = "/membership/add",
 *     "edit-form" = "/membership/{membership}/edit",
 *     "delete-form" = "/membership/{membership}/delete",
 *   },
 *   admin_permission = "administer memberships",
 * )
 */
class Membership extends ContentEntityBase implements ContentEntityInterface {

  /**
   * Gets the member ID reference.
   *
   * @return int
   *   The member ID entity ID.
   */
  public function getMemberIDReference() {
    return $this->get('member_id_ref')->target_id;
  }

  /**
   * Sets the member ID reference.
   *
   * @param int $member_id_entity_id
   *   The member ID entity ID.
   *
   * @return \Drupal\social_membership\Entity\Membership
   *   The called Membership entity.
   */
  public function setMemberIDReference($member_id_entity_id) {
    $this->set('member_id_ref', $member_id_entity_id);
    return $this;
  }

  /**
   * Gets the member ID entity.
   *
   * @return \Drupal\social_member_id\Entity\MemberID|null
   *   The member ID entity or null.
   */
  public function getMemberIDEntity() {
    return $this->get('member_id_ref')->entity;
  }

  /**
   * Sets the member ID entity.
   *
   * @param \Drupal\social_member_id\Entity\MemberID $member_id
   *   The member ID entity.
   *
   * @return \Drupal\social_membership\Entity\Membership
   *   The called Membership entity.
   */
  public function setMemberIDEntity(MemberID $member_id) {
    $this->set('member_id_ref', $member_id->id());
    return $this;
  }

  /**
   * Gets the membership start date.
   *
   * @return string
   *   The start date in Y-m-d format.
   */
  public function getStartDate() {
    return $this->get('start_date')->value;
  }

  /**
   * Sets the membership start date.
   *
   * @param string $date
   *   The start date in Y-m-d format.
   *
   * @return \Drupal\social_membership\Entity\Membership
   *   The called Membership entity.
   */
  public function setStartDate($date) {
    $this->set('start_date', $date);
    return $this;
  }

  /**
   * Gets the membership end date.
   *
   * @return string
   *   The end date in Y-m-d format.
   */
  public function getEndDate() {
    return $this->get('end_date')->value;
  }

  /**
   * Sets the membership end date.
   *
   * @param string $date
   *   The end date in Y-m-d format.
   *
   * @return \Drupal\social_membership\Entity\Membership
   *   The called Membership entity.
   */
  public function setEndDate($date) {
    $this->set('end_date', $date);
    return $this;
  }

  /**
   * Gets the creation timestamp.
   *
   * @return int
   *   The creation timestamp.
   */
  public function getCreatedTime() {
    return $this->get('created')->value;
  }

  /**
   * Sets the creation timestamp.
   *
   * @param int $timestamp
   *   The creation timestamp.
   *
   * @return \Drupal\social_membership\Entity\Membership
   *   The called Membership entity.
   */
  public function setCreatedTime($timestamp) {
    $this->set('created', $timestamp);
    return $this;
  }

  /**
   * Checks if the membership is currently active.
   *
   * @return bool
   *   TRUE if the membership is active, FALSE otherwise.
   */
  public function isActive() {
    $now = date('Y-m-d');
    $start = $this->getStartDate();
    $end = $this->getEndDate();
    
    return $start <= $now && $now <= $end;
  }

  /**
   * Checks if the membership has expired.
   *
   * @return bool
   *   TRUE if the membership has expired, FALSE otherwise.
   */
  public function isExpired() {
    $now = date('Y-m-d');
    $end = $this->getEndDate();
    
    return $now > $end;
  }

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields = parent::baseFieldDefinitions($entity_type);

    $fields['member_id_ref'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Member ID'))
      ->setDescription(t('Reference to the member ID this membership belongs to.'))
      ->setSetting('target_type', 'member_id')
      ->setSetting('handler', 'default')
      ->setRequired(TRUE)
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'entity_reference_label',
        'weight' => 0,
      ])
      ->setDisplayConfigurable('view', TRUE)
      ->setDisplayOptions('form', [
        'type' => 'entity_reference_autocomplete',
        'weight' => 0,
        'settings' => [
          'match_operator' => 'CONTAINS',
          'size' => '20',
          'placeholder' => 'Type Member ID (e.g. M001)',
        ],
      ])
      ->setDisplayConfigurable('form', TRUE);

    $fields['start_date'] = BaseFieldDefinition::create('datetime')
      ->setLabel(t('Start Date'))
      ->setDescription(t('The start date of the membership period.'))
      ->setSetting('datetime_type', 'date')
      ->setRequired(TRUE)
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'datetime_default',
        'weight' => 1,
      ])
      ->setDisplayConfigurable('view', TRUE)
      ->setDisplayOptions('form', [
        'type' => 'datetime_default',
        'weight' => 1,
      ])
      ->setDisplayConfigurable('form', TRUE);

    $fields['end_date'] = BaseFieldDefinition::create('datetime')
      ->setLabel(t('End Date'))
      ->setDescription(t('The end date of the membership period.'))
      ->setSetting('datetime_type', 'date')
      ->setRequired(TRUE)
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'datetime_default',
        'weight' => 2,
      ])
      ->setDisplayConfigurable('view', TRUE)
      ->setDisplayOptions('form', [
        'type' => 'datetime_default',
        'weight' => 2,
      ])
      ->setDisplayConfigurable('form', TRUE);

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Created'))
      ->setDescription(t('The time that the membership was created.'))
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'timestamp',
        'weight' => 3,
      ])
      ->setDisplayConfigurable('view', TRUE);

    return $fields;
  }

}