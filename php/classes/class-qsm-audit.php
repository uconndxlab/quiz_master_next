<?php

if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * This class handles the audit trail of the plugin
 */
class QSM_Audit {

  /**
   * Adds new audit to Audit Trail table
   *
   * @since 4.7.1
   * @param string $action The action that is to be saved into the audit trail
   * @return bool Returns true if successfull and false if fails
   */
  public function new_audit( $action, $user = null ) {

    // Sanitizes action just in case 3rd party uses this funtion
    $action = sanitize_text_field( $action );

    // Retrieves current user's data
    if ( is_null( $user ) ) {
      $current_user = wp_get_current_user();
    } else {
      $current_user = $user;
    }

    // Returns if the current user is not valid
    if ( ! ( $current_user instanceof WP_User ) ) {
      return false;
    }

    global $wpdb;

    // Inserts new audit into table
    $inserted = $wpdb->insert(
      $wpdb->prefix . "mlw_qm_audit_trail",
      array(
        'action_user' => $current_user->display_name,
        'action' => $action,
        'time' => date("h:i:s A m/d/Y")
      ),
      array(
        '%s',
        '%s',
        '%s'
      )
    );

    // If the insert returns false, then return false
    if ( false === $inserted ) {
      return false;
    }

    return true;
  }

  /**
   * Adds new logs to User Audit behaviour table
   *
   * @since 7.1.17
   * @param string $action The action that is to be saved into the audit trail
   * @return bool Returns true if successfull and false if fails
   */
  public function new_user_behaviour( $quiz_id, $action, $user = null ) {

    global $qmnQuizManager;
    $qmnQuizManager = new QMNQuizManager();

    // Sanitizes action just in case 3rd party uses this funtion
    $action = sanitize_text_field( $action );

    $user_ip = sanitize_text_field($qmnQuizManager->get_user_ip());

    // Retrieves current user's data
    if ( is_null( $user ) ) {
      $current_user = wp_get_current_user();
    } else {
      $current_user = $user;
    }

    $username = $current_user->display_name;

    // Returns if the current user is not valid
    if ( ! ( $current_user ) ) {
      $username = 'Guest';
    }

    

    global $wpdb;

    // Inserts new audit into table
    $inserted = $wpdb->insert(
      $wpdb->prefix . "mlw_user_behaviour_analytics",
      array(
        'quiz_id' => $quiz_id,
        'ip_address' => $user_ip,
        'user' => $username,
        'user_behaviour' => $action,
      ),
      array(
        '%d',
        '%s',
        '%s',
        '%s'
      )
    );

    // If the insert returns false, then return false
    if ( false === $inserted ) {
      return false;
    }

    return true;
  }
}
?>
