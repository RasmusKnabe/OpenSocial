<?php

namespace Drupal\social_membership_system\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * Controller for user membership overview.
 */
class UserMembershipController extends ControllerBase {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $currentUser;

  /**
   * Constructs a new UserMembershipController object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Session\AccountInterface $current_user
   *   The current user.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, AccountInterface $current_user) {
    $this->entityTypeManager = $entity_type_manager;
    $this->currentUser = $current_user;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('current_user')
    );
  }

  /**
   * Displays the current user's membership overview.
   *
   * @return array
   *   A render array.
   */
  public function overview() {
    $build = [];
    $user_id = $this->currentUser->id();

    // Get the user's Member ID
    $member_id_storage = $this->entityTypeManager->getStorage('member_id');
    $query = $member_id_storage->getQuery()
      ->condition('user_id', $user_id)
      ->accessCheck(TRUE);
    $member_id_ids = $query->execute();

    if (empty($member_id_ids)) {
      $build['no_member_id'] = [
        '#markup' => '<div class="alert alert-info">' . $this->t('You do not have a Member ID assigned yet.') . '</div>',
      ];
      return $build;
    }

    // Load the Member ID entity
    $member_id_id = reset($member_id_ids);
    $member_id_entity = $member_id_storage->load($member_id_id);

    // Member ID section
    $build['member_id_section'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Your Member ID'),
      '#attributes' => ['class' => ['member-id-section']],
    ];

    $build['member_id_section']['member_id'] = [
      '#markup' => '<div class="member-id-display"><strong>' . $this->t('Member ID: @id', ['@id' => $member_id_entity->getMemberID()]) . '</strong></div>',
    ];

    $build['member_id_section']['created'] = [
      '#markup' => '<div class="member-since">' . $this->t('Member since: @date', [
        '@date' => \Drupal::service('date.formatter')->format($member_id_entity->getCreatedTime(), 'long')
      ]) . '</div>',
    ];

    // Get all memberships for this Member ID
    $membership_storage = $this->entityTypeManager->getStorage('membership');
    $query = $membership_storage->getQuery()
      ->condition('member_id_ref', $member_id_id)
      ->sort('start_date', 'DESC')
      ->accessCheck(TRUE);
    $membership_ids = $query->execute();

    // Memberships section
    $build['memberships_section'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Your Memberships'),
      '#attributes' => ['class' => ['memberships-section']],
    ];

    $has_active_membership = FALSE;
    
    if (empty($membership_ids)) {
      $build['memberships_section']['no_memberships'] = [
        '#markup' => '<div class="alert alert-warning">' . $this->t('You do not have any membership periods registered.') . '</div>',
      ];
    } else {
      $memberships = $membership_storage->loadMultiple($membership_ids);
      
      $build['memberships_section']['table'] = [
        '#type' => 'table',
        '#header' => [
          $this->t('Period'),
          $this->t('Status'),
          $this->t('Duration'),
        ],
        '#attributes' => ['class' => ['membership-table']],
      ];

      foreach ($memberships as $membership) {
        $status_class = '';
        if ($membership->isActive()) {
          $status = $this->t('Active');
          $status_class = 'status-active';
        } elseif ($membership->isExpired()) {
          $status = $this->t('Expired');
          $status_class = 'status-expired';
        } else {
          $status = $this->t('Future');
          $status_class = 'status-future';
        }

        // Calculate duration
        $start_date = new \DateTime($membership->getStartDate());
        $end_date = new \DateTime($membership->getEndDate());
        $interval = $start_date->diff($end_date);
        $duration = $interval->days + 1 . ' ' . $this->t('days');

        $build['memberships_section']['table'][$membership->id()] = [
          'period' => [
            '#markup' => \Drupal::service('date.formatter')->format(strtotime($membership->getStartDate()), 'custom', 'd/m/Y') . 
                        ' - ' . 
                        \Drupal::service('date.formatter')->format(strtotime($membership->getEndDate()), 'custom', 'd/m/Y'),
          ],
          'status' => [
            '#markup' => '<span class="' . $status_class . '">' . $status . '</span>',
          ],
          'duration' => [
            '#markup' => $duration,
          ],
        ];
      }

      // Add current membership status summary
      $active_memberships = array_filter($memberships, function($membership) {
        return $membership->isActive();
      });

      if (!empty($active_memberships)) {
        $active_membership = reset($active_memberships);
        $build['current_status'] = [
          '#markup' => '<div class="alert alert-success">' . 
                      $this->t('Your membership is currently active until @date', [
                        '@date' => \Drupal::service('date.formatter')->format(strtotime($active_membership->getEndDate()), 'long')
                      ]) . '</div>',
          '#weight' => -10,
        ];
      } else {
        $build['current_status'] = [
          '#markup' => '<div class="alert alert-warning">' . $this->t('You do not have an active membership.') . '</div>',
          '#weight' => -10,
        ];
        
        // Add renewal button for users without active membership
        $renewal_url = Url::fromRoute('social_membership_system.user_renewal');
        $build['renewal_button'] = [
          '#type' => 'link',
          '#title' => $this->t('Renew Membership'),
          '#url' => $renewal_url,
          '#attributes' => [
            'class' => ['btn', 'btn-primary', 'btn-renewal'],
            'role' => 'button',
          ],
          '#weight' => -5,
        ];
      }
    }

    // Add some basic styling
    $build['#attached']['html_head'][] = [
      [
        '#tag' => 'style',
        '#value' => '
          .member-id-display { font-size: 1.2em; margin-bottom: 10px; }
          .member-since { color: #666; margin-bottom: 15px; }
          .membership-table { width: 100%; }
          .status-active { color: #28a745; font-weight: bold; }
          .status-expired { color: #dc3545; }
          .status-future { color: #17a2b8; }
          .alert { padding: 15px; margin-bottom: 20px; border-radius: 4px; }
          .alert-info { background-color: #d1ecf1; border-color: #bee5eb; color: #0c5460; }
          .alert-success { background-color: #d4edda; border-color: #c3e6cb; color: #155724; }
          .alert-warning { background-color: #fff3cd; border-color: #ffeaa7; color: #856404; }
          .btn-renewal { margin-top: 15px; padding: 10px 20px; text-decoration: none; border-radius: 4px; display: inline-block; }
        ',
      ],
      'membership-styles'
    ];

    return $build;
  }

  /**
   * Renews membership for the current user.
   *
   * @return \Symfony\Component\HttpFoundation\RedirectResponse
   *   Redirect response back to membership overview.
   */
  public function renewMembership() {
    $current_user_id = $this->currentUser->id();

    // Get the user's Member ID
    $member_id_storage = $this->entityTypeManager->getStorage('member_id');
    $query = $member_id_storage->getQuery()
      ->condition('user_id', $current_user_id)
      ->accessCheck(TRUE);
    $member_id_ids = $query->execute();

    if (empty($member_id_ids)) {
      $this->messenger()->addError($this->t('You do not have a Member ID. Please contact an administrator.'));
      return new RedirectResponse(Url::fromRoute('social_membership_system.user_overview')->toString());
    }

    $member_id_id = reset($member_id_ids);

    // Check if user already has an active membership
    $membership_storage = $this->entityTypeManager->getStorage('membership');
    $query = $membership_storage->getQuery()
      ->condition('member_id_ref', $member_id_id)
      ->accessCheck(TRUE);
    $membership_ids = $query->execute();

    if (!empty($membership_ids)) {
      $memberships = $membership_storage->loadMultiple($membership_ids);
      foreach ($memberships as $membership) {
        if ($membership->isActive()) {
          $this->messenger()->addWarning($this->t('You already have an active membership.'));
          return new RedirectResponse(Url::fromRoute('social_membership_system.user_overview')->toString());
        }
      }
    }

    // Create new membership for current year
    $current_year = date('Y');
    $start_date = date('Y-m-d');
    $end_date = $current_year . '-12-31';

    try {
      /** @var \Drupal\social_membership\Entity\Membership $membership */
      $membership = $membership_storage->create([
        'member_id_ref' => $member_id_id,
        'start_date' => $start_date,
        'end_date' => $end_date,
      ]);
      $membership->save();

      $this->messenger()->addStatus($this->t('Your membership has been renewed until December 31, @year.', [
        '@year' => $current_year
      ]));

      // Log the renewal
      \Drupal::logger('social_membership')->info('User @uid renewed membership (ID: @membership_id)', [
        '@uid' => $current_user_id,
        '@membership_id' => $membership->id(),
      ]);

    } catch (\Exception $e) {
      $this->messenger()->addError($this->t('An error occurred while renewing your membership. Please try again or contact an administrator.'));
      \Drupal::logger('social_membership')->error('Membership renewal failed for user @uid: @error', [
        '@uid' => $current_user_id,
        '@error' => $e->getMessage(),
      ]);
    }

    return new RedirectResponse(Url::fromRoute('social_membership_system.user_overview')->toString());
  }

}