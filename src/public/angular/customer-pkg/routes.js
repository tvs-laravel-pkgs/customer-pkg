app.config(['$routeProvider', function($routeProvider) {

    $routeProvider.
    //CUSTOMER
    when('/customer-pkg/customer/list', {
        template: '<customer-list></customer-list>',
        title: 'Customers',
    }).
    when('/customer-pkg/customer/add', {
        template: '<customer-form></customer-form>',
        title: 'Add Customer',
    }).
    when('/customer-pkg/customer/edit/:id', {
        template: '<customer-form></customer-form>',
        title: 'Edit Customer',
    });
}]);