app.config(['$routeProvider', function($routeProvider) {

    $routeProvider.
    when('/', {
        template: '<login></login>',
        title: 'Login',
    }).
    when('/login', {
        template: '<login></login>',
        title: 'Login',
    }).
    when('/forgot-password', {
        template: '<forgot-password></forgot-password>',
        title: 'Forgot Password',
    }).
    when('/reset-password', {
        template: '<reset-password></reset-password>',
        title: 'Reset Password',
    });

}]);

app.component('login', {
    templateUrl: login_page_template_url,
    controller: function($http, $location, HelperService, $scope, $routeParams, $rootScope) {
        var self = this;
        self.hasPermission = HelperService.hasPermission;
        self.angular_routes = angular_routes;
        // $http.get(
        //     get_form_data_url
        // ).then(function(response) {
        //     // console.log(response);
        //     self.customer = response.data.customer;
        //     self.address = response.data.address;
        //     self.country_list = response.data.country_list;
        //     self.action = response.data.action;
        //     $rootScope.loading = false;
        //     if (self.action == 'Edit') {
        //         $scope.onSelectedCountry(self.address.country_id);
        //         $scope.onSelectedState(self.address.state_id);
        //         if (self.customer.deleted_at) {
        //             self.switch_value = 'Inactive';
        //         } else {
        //             self.switch_value = 'Active';
        //         }
        //     } else {
        //         self.switch_value = 'Active';
        //         self.state_list = [{ 'id': '', 'name': 'Select State' }];
        //         self.city_list = [{ 'id': '', 'name': 'Select City' }];
        //     }
        // });

        var form_id = '#login-form';
        var v = jQuery(form_id).validate({
            ignore: '',
            rules: {
                'username': {
                    required: true,
                    minlength: 3,
                    maxlength: 255,
                },
                'password': {
                    required: true,
                    minlength: 3,
                    maxlength: 255,
                },
            },
            submitHandler: function(form) {
                let formData = new FormData($(form_id)[0]);
                $('#submit').button('loading');
                $.ajax({
                        url: laravel_routes['login'],
                        method: "POST",
                        data: formData,
                        processData: false,
                        contentType: false,
                    })
                    .done(function(res) {
                        if (!res.success) {
                            showErrorNoty(res);
                            $('#submit').button('reset');
                            return;
                        }
                        window.location.reload();
                    })
                    .fail(function(xhr) {
                        $('#submit').button('reset');
                        custom_noty('error', 'Something went wrong at server');
                    });
            }
        });
    }
});
