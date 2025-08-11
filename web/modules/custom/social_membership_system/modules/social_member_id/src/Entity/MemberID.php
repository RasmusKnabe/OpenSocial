<?php

namespace Drupal\social_member_id\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\user\UserInterface;

/**
 * Defines the MemberID entity.
 *
 * @ContentEntityType(
 *   id = "member_id",
 *   label = @Translation("Member ID"),
 *   base_table = "member_id",
 *   entity_keys = {
 *     "id" = "id",
 *     "uuid" = "uuid",
 *     "label" = "member_id"
 *   },
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\social_member_id\MemberIDListBuilder",
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
 *     "canonical" = "/member-id/{member_id}",
 *     "add-form" = "/member-id/add",
 *     "edit-form" = "/member-id/{member_id}/edit",
 *     "delete-form" = "/member-id/{member_id}/delete",
 *   },
 *   admin_permission = "administer member ids",
 * )
 */
class MemberID extends ContentEntityBase implements ContentEntityInterface {

  /**
   * {@inheritdoc}
   */
  public function label() {
    return $this->getMemberID();
  }

  /**
   * Gets the member ID number.
   *
   * @return string
   *   The unique member ID.
   */
  public function getMemberID() {
    return $this->get('member_id')->value;
  }

  /**
   * Sets the member ID number.
   *
   * @param string $member_id
   *   The unique member ID.
   *
   * @return \Drupal\social_member_id\Entity\MemberID
   *   The called MemberID entity.
   */
  public function setMemberID($member_id) {
    $this->set('member_id', $member_id);
    return $this;
  }

  /**
   * Gets the user ID.
   *
   * @return int
   *   The user ID.
   */
  public function getUserID() {
    return $this->get('user_id')->target_id;
  }

  /**
   * Sets the user ID.
   *
   * @param int $user_id
   *   The user ID.
   *
   * @return \Drupal\social_member_id\Entity\MemberID
   *   The called MemberID entity.
   */
  public function setUserID($user_id) {
    $this->set('user_id', $user_id);
    return $this;
  }

  /**
   * Gets the user entity.
   *
   * @return \Drupal\user\UserInterface|null
   *   The user entity or null.
   */
  public function getUser() {
    return $this->get('user_id')->entity;
  }

  /**
   * Sets the user entity.
   *
   * @param \Drupal\user\UserInterface $user
   *   The user entity.
   *
   * @return \Drupal\social_member_id\Entity\MemberID
   *   The called MemberID entity.
   */
  public function setUser(UserInterface $user) {
    $this->set('user_id', $user->id());
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
   * @return \Drupal\social_member_id\Entity\MemberID
   *   The called MemberID entity.
   */
  public function setCreatedTime($timestamp) {
    $this->set('created', $timestamp);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function preSave(EntityStorageInterface $storage) {
    parent::preSave($storage);
    
    // Auto-generate member ID if not set
    if (empty($this->getMemberID())) {
      $this->setMemberID($this->generateNextMemberID($storage));
    }
  }

  /**
   * Generates the next member ID in sequence for the current year.
   *
   * @param \Drupal\Core\Entity\EntityStorageInterface $storage
   *   The entity storage.
   *
   * @return string
   *   The generated member ID in format YYYYNNN.
   */
  protected function generateNextMemberID(EntityStorageInterface $storage) {
    $current_year = date('Y');
    
    // Find the highest member ID for the current year
    $query = $storage->getQuery()
      ->condition('member_id', $current_year . '%', 'LIKE')
      ->sort('member_id', 'DESC')
      ->range(0, 1)
      ->accessCheck(FALSE);
    
    $result = $query->execute();
    
    if (empty($result)) {
      // First member of the year
      return $current_year . '001';
    }
    
    // Get the last member ID
    $last_id = reset($result);
    $last_entity = $storage->load($last_id);
    $last_member_id = $last_entity->getMemberID();
    
    // Extract the sequence number and increment
    if (preg_match('/^(\d{4})(\d{3})$/', $last_member_id, $matches)) {
      $year = $matches[1];
      $sequence = (int) $matches[2];
      
      // If it's from the current year, increment the sequence
      if ($year == $current_year) {
        $next_sequence = $sequence + 1;
        return $current_year . str_pad($next_sequence, 3, '0', STR_PAD_LEFT);
      }
    }
    
    // Fallback: First member of the current year
    return $current_year . '001';
  }

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields = parent::baseFieldDefinitions($entity_type);

    $fields['member_id'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Member ID'))
      ->setDescription(t('The unique member identification number.'))
      ->setRequired(TRUE)
      ->setSetting('max_length', 50)
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'string',
        'weight' => 0,
      ])
      ->setDisplayConfigurable('view', TRUE)
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => 0,
        'settings' => [
          'size' => 20,
          'placeholder' => '2025001 (auto-generated if empty)',
        ],
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->addConstraint('UniqueField');

    $fields['user_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('User'))
      ->setDescription(t('The user this member ID belongs to.'))
      ->setSetting('target_type', 'user')
      ->setSetting('handler', 'default')
      ->setRequired(TRUE)
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'entity_reference_label',
        'weight' => 1,
      ])
      ->setDisplayConfigurable('view', TRUE)
      ->setDisplayOptions('form', [
        'type' => 'entity_reference_autocomplete',
        'weight' => 1,
        'settings' => [
          'match_operator' => 'CONTAINS',
          'size' => '60',
          'autocomplete_type' => 'tags',
          'placeholder' => '',
        ],
      ])
      ->setDisplayConfigurable('form', TRUE);

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Created'))
      ->setDescription(t('The time that the member ID was created.'))
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'timestamp',
        'weight' => 2,
      ])
      ->setDisplayConfigurable('view', TRUE);

    return $fields;
  }

}