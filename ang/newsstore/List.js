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
  angular.module('newsstore').controller('NewsstoreList', function($scope, crmApi, crmStatus, crmUiHelp, nsSources) {
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

    $scope.viewItemsInSource = function(nsSource) {

      $scope.nsSource = nsSource;
      return crmApi('NewsStoreItem', 'get', { source: nsSource.id })
      .then(function(result) {
        $scope.nsItems = result.values || [];
        $scope.screen = 'items';
      });
    };
    $scope.fetchSource = function(nsSource) {
      console.log("@todo", nsSource);
    };
    $scope.editSource = function(nsSource) {
      console.log("@todo", nsSource);
    };
    $scope.deleteSource = function(nsSource) {
      console.log("@todo", nsSource);
    };


  });

})(angular, CRM.$, CRM._);
