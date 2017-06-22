(function(angular, $, _) {

  angular.module('newsstore').config(function($routeProvider) {
      $routeProvider.when('/newsstore', {
        controller: 'NewsstoreList',
        templateUrl: '~/newsstore/List.html',

        // If you need to look up data when opening the page, list it out
        // under "resolve".
        resolve: {
          nsSources: function(crmApi) {
            return crmApi('NewsStoreSource', 'get', {} );
          }
        }
      });
    }
  );

  // The controller uses *injection*. This default injects a few things:
  //   $scope -- This is the set of variables shared between JS and HTML.
  //   crmApi, crmStatus, crmUiHelp -- These are services provided by civicrm-core.
  //   myContact -- The current contact, defined above in config().
  angular.module('newsstore').controller('NewsstoreList', function($scope, crmApi, crmUiAlert, crmStatus, crmUiHelp, nsSources) {
    // The ts() and hs() functions help load strings for this module.
    var ts = $scope.ts = CRM.ts('newsstore');
    var hs = $scope.hs = crmUiHelp({file: 'CRM/newsstore/List'}); // See: templates/CRM/newsstore/List.hlp
    // All sources:
    $scope.nsSources = nsSources.values || [];
    // The selected source:
    $scope.nsSource = null;
    // The selected source's items
    $scope.nsItems = null;
    // UI mode:
    $scope.screen = 'sources';
    // Item selected.
    $scope.itemSelected = null;

    // DRY. Returns a Promise.
    var reloadSources = function() {
      return crmApi('NewsStoreSource', 'get', {} )
        .then(function(result) {
          $scope.nsSources = result.values;
        });
    };

    // Functions for sources.
    $scope.viewItemsInSource = function(nsSource) {
      $scope.nsSource = nsSource;
      return crmApi('NewsStoreItem', 'getWithUsage', { source: nsSource.id })
      .then(function(result) {
        $scope.nsItems = result.values || [];
        $scope.screen = 'items';
      });
    };
    $scope.fetchSource = function(nsSource) {
      return crmStatus(
        {start: ts('Fetching Source...'), end: ''},
        crmApi('NewsStoreSource', 'fetch', { id: nsSource.id }))
      .then(function(result) {
        crmUiAlert({
          title: ts('Fetch results'),
          text: ts(
          '%1 new item(s),<br/>%2 existing item(s) linked this source,<br/>%3 item(s) already cached.',
          { 1: result.values['new'], 2: result.values.new_link, 3: result.values.old }
        ), type: 'info'});
        return reloadSources();
      });
    };
    $scope.editSource = function(nsSource) {
      // Take a copy; we might not want to save it.
      $scope.nsSource = Object.assign({
        retention_days: 30,
        fetch_frequency: 'daily',
        type: 'Rss',
      }, nsSource);
      $scope.screen = 'source-edit';
    };
    $scope.saveSourceEdits = function(nsSource) {
      var params = _.pick(nsSource, ['id', 'name', 'uri', 'type', 'retention_days', 'fetch_frequency']);
      return crmApi('NewsStoreSource', 'create', params)
        .then(reloadSources)
        .then(function() { $scope.screen = 'sources'; });
    };
    $scope.deleteSource = function(nsSource) {
      if (confirm(ts('Delete source "%1"? This cannot be un-done.', { 1: nsSource.name }))) {
        return crmApi('NewsStoreSource', 'delete', { id: nsSource.id })
          .then(reloadSources);
      }
    };

    // Functions for items.
    $scope.updateItemConsumed = function(item, newIsConsumed) {
      return crmApi('NewsStoreConsumed', 'create', { id: item.newsstoreconsumed_id, is_consumed: newIsConsumed })
      .then(function(result) {
        item.is_consumed = newIsConsumed;
      })
      .then(reloadSources);
    };

    $scope.showItemDetails = function(item) {
      if (!item) {
        $scope.itemSelected = null;
      }
      else {
        // Fetch item and pop it up.
        return crmApi('NewsStoreItem', 'get', { id: item.id })
          .then(function(result) {
            $scope.itemSelected = result.values[item.id];
          });
      }
    };


  });

})(angular, CRM.$, CRM._);
