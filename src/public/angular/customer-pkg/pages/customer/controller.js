app.component('customerList', {
    templateUrl: customer_list_template_url,
    controller: function($http, $location, HelperService, $scope, $routeParams, $rootScope, $location,$cookies) {
        $scope.loading = true;
        var self = this;
        self.hasPermission = HelperService.hasPermission;
        var table_scroll;
        $('#search_customer').focus();
        $http.get(
                customer_session_data_url,
            ).then(function(response) {
                if (response.data.success) {
                    console.log('response.data.success');
                    console.log(response.data);
                    self.customer_code = response.data.customer_code;
                    self.customer_name = response.data.customer_name;
                    self.mobile_no = response.data.mobile_no;
                    self.email = response.data.email;
                    $('#search_customer').val(response.data.search_customer);
                }
            });
         //console.log(typeof(search_customer));
        table_scroll = $('.page-main-content').height() - 37;
        setTimeout(function(){
            var dataTable = $('#customers_list').DataTable({
                "dom": cndn_dom_structure,
                "language": {
                    "lengthMenu": "Rows _MENU_",
                    "paginate": {
                        "next": '<i class="icon ion-ios-arrow-forward"></i>',
                        "previous": '<i class="icon ion-ios-arrow-back"></i>'
                    },
                },
                pageLength: 10,
                processing: true,
                stateSaveCallback: function(settings, data) {
                    localStorage.setItem('CDataTables_' + settings.sInstance, JSON.stringify(data));
                },
                stateLoadCallback: function(settings) {
                    var state_save_val = JSON.parse(localStorage.getItem('CDataTables_' + settings.sInstance));
                    if (state_save_val) {
                        $('#search_customer').val(state_save_val.search.search);
                    }
                    return JSON.parse(localStorage.getItem('CDataTables_' + settings.sInstance));
                },
               serverSide: true,
                paging: true,
                stateSave: true,
                ordering: true,
                sorting:true,
                 scrollY: table_scroll + "px",
                scrollCollapse: true,
                ajax: {
                    url: laravel_routes['getCustomerList'],
                    type: "GET",
                    dataType: "json",
                    data: function(d) {
                        d.customer_code = self.customer_code;
                        d.customer_name = self.customer_name;
                        d.mobile_no = self.mobile_no;
                        d.email = self.email;
                    },
                },
                columns: [
                    { data: 'action', class: 'action', "name": 'action', searchable: false },
                    { data: 'code', "name": 'code' },
                    { data: 'name', "name": 'name', sortable: true },
                    { data: 'mobile_no', "name": 'mobile_no' },
                    { data: 'email', "name": 'email' },
                ],
                "infoCallback": function(settings, start, end, max, total, pre) {
                    $('#table_info').html(total)
                    $('.foot_info').html('Showing ' + start + ' to ' + end + ' of ' + max + ' entries')
                },
                rowCallback: function(row, data) {
                    $(row).addClass('highlight-row');
                }
            });

            $('.dataTables_length select').select2();

        $scope.clear_search = function() {
            $('#search_customer').val('');
            $('#customers_list').DataTable().search('').draw();
        }

        var dataTables = $('#customers_list').dataTable();
        $("#search_customer").keyup(function() {
            dataTables.fnFilter(this.value);
        });
        $scope.statusChange=function(){
            dataTables.fnFilter();
        }
        //DELETE
        $scope.deleteCustomer = function($id) {
            $('#customer_id').val($id);
        }
        $scope.deleteConfirm = function() {
            $id = $('#customer_id').val();
            $http.get(
                customer_delete_data_url + '/' + $id,
            ).then(function(response) {
                if (response.data.success) {
                    $noty = new Noty({
                        type: 'success',
                        layout: 'topRight',
                        text: 'Customer Deleted Successfully',
                    }).show();
                    setTimeout(function() {
                        $noty.close();
                    }, 3000);
                    $('#customers_list').DataTable().ajax.reload(function(json) {});
                    $location.path('/customer-pkg/customers/list');
                }
            });
        }

        //FOR FILTER
        $('#customer_code').on('keyup', function() {
            dataTables.fnFilter();
        });
        $('#customer_name').on('keyup', function() {
            dataTables.fnFilter();
        });
        $('#mobile_no').on('keyup', function() {
            dataTables.fnFilter();
        });
        $('#email').on('keyup', function() {
            dataTables.fnFilter();
        });
        $scope.reset_filter = function() {
            self.customer_code = '';
            self.customer_name = "";
            self.mobile_no = "";
            self.email = "";
            dataTables.fnFilter();
        }

        $rootScope.loading = false;
        },2000);
        
    }
});
//------------------------------------------------------------------------------------------------------------------------
//------------------------------------------------------------------------------------------------------------------------
app.component('customerForm', {
    templateUrl: customer_form_template_url,
    controller: function($http, $location, HelperService, $scope, $routeParams, $rootScope) {
        get_form_data_url = typeof($routeParams.id) == 'undefined' ? customer_get_form_data_url : customer_get_form_data_url + '/' + $routeParams.id;
        var self = this;
        $('#code').focus();

        self.hasPermission = HelperService.hasPermission;
        self.angular_routes = angular_routes;
        $http.get(
            get_form_data_url
        ).then(function(response) {
            // console.log(response);
            self.customer = response.data.customer;
            self.address = response.data.address;
            self.country_list = response.data.country_list;
            self.action = response.data.action;
            self.customer_details = response.data.customer_details;
            $rootScope.loading = false;
            if (self.action == 'Edit') {
                $scope.onSelectedCountry(self.address.country_id);
                $scope.onSelectedState(self.address.state_id);
                if (self.customer.deleted_at) {
                    self.switch_value = 'Inactive';
                } else {
                    self.switch_value = 'Active';
                }
            } else {
                self.switch_value = 'Active';
                self.state_list = [{ 'id': '', 'name': 'Select State' }];
                self.city_list = [{ 'id': '', 'name': 'Select City' }];
            }
        });

        /* Tab Funtion */
        $('.btn-nxt').on("click", function() {
            $('.cndn-tabs li.active').next().children('a').trigger("click");
            tabPaneFooter();
        });
        $('.btn-prev').on("click", function() {
            $('.cndn-tabs li.active').prev().children('a').trigger("click");
            tabPaneFooter();
        });
        $('.btn-pills').on("click", function() {
            tabPaneFooter();
        });
        $scope.btnNxt = function() {}
        $scope.prev = function() {}

        //SELECT STATE BASED COUNTRY
        $scope.onSelectedCountry = function(id) {
            customer_get_state_by_country = vendor_get_state_by_country;
            $http.post(
                customer_get_state_by_country, { 'country_id': id }
            ).then(function(response) {
                // console.log(response);
                self.state_list = response.data.state_list;
            });
        }

        //SELECT CITY BASED STATE
        $scope.onSelectedState = function(id) {
            customer_get_city_by_state = vendor_get_city_by_state
            $http.post(
                customer_get_city_by_state, { 'state_id': id }
            ).then(function(response) {
                // console.log(response);
                self.city_list = response.data.city_list;
            });
        }
        self.control = function(){
            $('#address_line1').focus();

        }
        var form_id = '#form';
        var v = jQuery(form_id).validate({
            ignore: '',
            rules: {
                'code': {
                    required: true,
                    minlength: 3,
                    maxlength: 255,
                },
                'name': {
                    required: true,
                    minlength: 3,
                    maxlength: 255,
                },
                'cust_group': {
                    maxlength: 100,
                },
                'gst_number': {
                    maxlength: 20,
                },
                'dimension': {
                    maxlength: 50,
                },
                'address': {
                    required: true,
                    minlength: 5,
                    maxlength: 250,
                },
                'address_line1': {
                    minlength: 3,
                    maxlength: 255,
                },
                'address_line2': {
                    minlength: 3,
                    maxlength: 255,
                },
                // 'pincode': {
                //     required: true,
                //     minlength: 6,
                //     maxlength: 6,
                // },
            },
            messages: {
                'code': {
                    maxlength: 'Maximum of 255 charaters',
                },
                'name': {
                    maxlength: 'Maximum of 255 charaters',
                },
                'cust_group': {
                    maxlength: 'Maximum of 100 charaters',
                },
                'dimension': {
                    maxlength: 'Maximum of 50 charaters',
                },
                'gst_number': {
                    maxlength: 'Maximum of 25 charaters',
                },
                'email': {
                    maxlength: 'Maximum of 100 charaters',
                },
                'address_line1': {
                    maxlength: 'Maximum of 255 charaters',
                },
                'address_line2': {
                    maxlength: 'Maximum of 255 charaters',
                },
                // 'pincode': {
                //     maxlength: 'Maximum of 6 charaters',
                // },
            },
            invalidHandler: function(event, validator) {
                $noty = new Noty({
                    type: 'error',
                    layout: 'topRight',
                    text: 'You have errors,Please check all tabs'
                }).show();
                setTimeout(function() {
                    $noty.close();
                }, 3000)
            },
            submitHandler: function(form) {
                let formData = new FormData($(form_id)[0]);
                $('.submit').button('loading');
                $.ajax({
                        url: laravel_routes['saveCustomer'],
                        method: "POST",
                        data: formData,
                        processData: false,
                        contentType: false,
                    })
                    .done(function(res) {
                        if (res.success == true) {
                            $noty = new Noty({
                                type: 'success',
                                layout: 'topRight',
                                text: res.message,
                            }).show();
                            setTimeout(function() {
                                $noty.close();
                            }, 3000);
                            $location.path('/customer-pkg/customers/list');
                            $scope.$apply();
                        } else {
                            if (!res.success == true) {
                                $('.submit').button('reset');
                                var errors = '';
                                for (var i in res.errors) {
                                    errors += '<li>' + res.errors[i] + '</li>';
                                }
                                $noty = new Noty({
                                    type: 'error',
                                    layout: 'topRight',
                                    text: errors
                                }).show();
                                setTimeout(function() {
                                    $noty.close();
                                }, 3000);
                            } else {
                                $('.submit').button('reset');
                                $location.path('/customer-pkg/customers/list');
                                $scope.$apply();
                            }
                        }
                    })
                    .fail(function(xhr) {
                        $('.submit').button('reset');
                        $noty = new Noty({
                            type: 'error',
                            layout: 'topRight',
                            text: 'Something went wrong at server',
                        }).show();
                        setTimeout(function() {
                            $noty.close();
                        }, 3000);
                    });
            }
        });
    }
});
