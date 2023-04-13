<?php
/*-------------------------------------------------------+
| SYSTOPIA NewsStore Extension                           |
| Copyright (C) 2017 SYSTOPIA                            |
| Authors: Rich Lott (give email?)                       |
|          B. Endres (endres@systopia.de)                |
| http://www.systopia.de/                                |
+--------------------------------------------------------+
| This program is released as free software under the    |
| Affero GPL license. You can redistribute it and/or     |
| modify it under the terms of this license which you    |
| can read by viewing the included agpl.txt or online    |
| at www.gnu.org/licenses/agpl.html. Removal of this     |
| copyright header is strictly prohibited without        |
| written permission from the original author(s).        |
+--------------------------------------------------------*/

require_once 'newsstore.civix.php';

/**
 * Implements hook_civicrm_config().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_config
 */
function newsstore_civicrm_config(&$config) {
  _newsstore_civix_civicrm_config($config);
}

/**
 * Implements hook_civicrm_install().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_install
 */
function newsstore_civicrm_install() {
  _newsstore_civix_civicrm_install();
}

/**
 * Implements hook_civicrm_enable().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_enable
 */
function newsstore_civicrm_enable() {
  _newsstore_civix_civicrm_enable();
}

/**
 * Define our entities.
 */
function newsstore_civicrm_entityTypes(&$entityTypes) {
  $x=1;
  $entityTypes[] = [
    'name' => 'NewsStoreSource',
    'class' => 'CRM_Newsstore_DAO_NewsStoreSource',
    'table' => 'civicrm_newsstoresource',
  ];
  $entityTypes[] = [
    'name' => 'NewsStoreItem',
    'class' => 'CRM_Newsstore_DAO_NewsStoreItem',
    'table' => 'civicrm_newsstoreitem',
  ];
  $entityTypes[] = [
    'name' => 'NewsStoreConsumed',
    'class' => 'CRM_Newsstore_DAO_NewsStoreConsumed',
    'table' => 'civicrm_newsstoreconsumed',
  ];
}
/**
 * Implements hook_civicrm_navigationMenu().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_navigationMenu
 */
function newsstore_civicrm_navigationMenu(&$menu) {
  //$parentID =  CRM_Core_DAO::getFieldValue('CRM_Core_DAO_Navigation', 'Contacts', 'id', 'name');
  _newsstore_civix_insert_navigation_menu($menu, 'Administer', array(
    'label' => ts('NewsStore', array('domain' => 'de.systopia.newsstore')),
    'name' => 'newsstore',
    'url' => 'civicrm/a/#newsstore',
    'permission' => 'access CiviReport',
  ));
  _newsstore_civix_navigationMenu($menu);
}
