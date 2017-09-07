<?php
// This file declares a managed database record of type "Job".
// The record will be automatically inserted, updated, or deleted from the
// database as appropriate. For more details, see "hook_civicrm_managed" at:
// http://wiki.civicrm.org/confluence/display/CRMDOC42/Hook+Reference
return [
  [
    'name' => 'Cron:NewsStoreSource.FetchWeekly',
    'entity' => 'Job',
    'params' =>
    [
      'version' => 3,
      'name' => 'NewsStore weekly fetch',
      'description' => 'Fetch sources configured for weekly fetches.',
      'run_frequency' => 'Weekly',
      'api_entity' => 'NewsStoreSource',
      'api_action' => 'Fetch',
      'parameters' => 'fetch_frequency=weekly',
    ],
  ],
  [
    'name' => 'Cron:NewsStoreSource.FetchDaily',
    'entity' => 'Job',
    'params' =>
    [
      'version' => 3,
      'name' => 'NewsStore daily fetch',
      'description' => 'Fetch sources configured for daily fetches.',
      'run_frequency' => 'Daily',
      'api_entity' => 'NewsStoreSource',
      'api_action' => 'Fetch',
      'parameters' => 'fetch_frequency=daily',
    ],
  ],
  [
    'name' => 'Cron:NewsStoreSource.FetchHourly',
    'entity' => 'Job',
    'params' =>
    [
      'version' => 3,
      'name' => 'NewsStore hourly fetch',
      'description' => 'Fetch sources configured for hourly fetches.',
      'run_frequency' => 'Hourly',
      'api_entity' => 'NewsStoreSource',
      'api_action' => 'Fetch',
      'parameters' => 'fetch_frequency=hourly',
    ],
  ],
];
