app.component('customerList', {
    templateUrl: customer_list_template_url,
    controller: function($http, $location, HelperService, $scope, $routeParams, $rootScope, $location, $mdSelect, $element) {
        $scope.loading = true;
        var self = this;
        self.hasPermission = HelperService.hasPermission;
        $('li').removeClass('active');
        $('.master_link').addClass('active').trigger('click');
        var table_scroll;
        table_scroll = $('.page-main-content').height() - 37;
        var dataTable = $('#customers_list').DataTable({
            "dom": cndn_dom_structure,
            "language": {
                // "search": "",
                // "searchPlaceholder": "Search",
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
            ordering: false,
            scrollY: table_scroll + "px",
            scrollCollapse: true,
            ajax: {
                url: laravel_routes['getCustomerList'],
                type: "GET",
                dataType: "json",
                data: function(d) {
                    d.customer_code = $('#customer_code').val();
                    d.customer_name = $('#customer_name').val();
                    d.mobile_no = $('#mobile_no').val();
                    d.email = $('#email').val();
                    d.state_id = $('#state_id').val();
                    d.city_id = $('#city_id').val();
                },
            },

            columns: [
                { data: 'action', class: 'action', name: 'action', searchable: false },
                { data: 'code', name: 'customers.code' },
                { data: 'name', name: 'customers.name' },
                { data: 'mobile_no', name: 'customers.mobile_no' },
                { data: 'email', name: 'customers.email' },
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
                    $location.path('/customer-pkg/customer/list');
                }
            });
        }

        //FOR FILTER
        $http.get(
            laravel_routes['getCustomerFilterData']
        ).then(function(response) {
            console.log(response.data);
            $scope.extras = response.data.extras;
        });

        $('.modal').bind('click', function(event) {
            if ($('.md-select-menu-container').hasClass('md-active')) {
                $mdSelect.hide();
            }
        });
        $scope.clearSearchTerm = function() {
            $scope.searchTerm = '';
            $scope.searchTerm1 = '';
        };
        $element.find('input').on('keydown', function(ev) {
            ev.stopPropagation();
        });
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
        $scope.onSelectedState = function(id) {
            $('#state_id').val(id);
            customer_get_city_by_state = vendor_get_city_by_state
            $http.post(
                customer_get_city_by_state, { 'state_id': id }
            ).then(function(response) {
                console.log(response);
                self.city_list = response.data.city_list;
            });
            dataTables.fnFilter();
        }
        $scope.onSelectedCity = function(id) {
            $('#city_id').val(id);
            dataTables.fnFilter();
        }
        $scope.reset_filter = function() {
            $("#customer_name").val('');
            $("#customer_code").val('');
            $("#mobile_no").val('');
            $("#email").val('');
            $("#state_id").val('');
            $("#city_id").val('');
            dataTables.fnFilter();
        }

        $rootScope.loading = false;
    }
});
//------------------------------------------------------------------------------------------------------------------------
//------------------------------------------------------------------------------------------------------------------------
app.component('customerForm', {
    templateUrl: customer_form_template_url,
    controller: function($http, $location, HelperService, $scope, $routeParams, $rootScope) {
        get_form_data_url = typeof($routeParams.id) == 'undefined' ? customer_get_form_data_url : customer_get_form_data_url + '/' + $routeParams.id;
        var self = this;
        self.hasPermission = HelperService.hasPermission;
        self.angular_routes = angular_routes;
        $http.get(
            get_form_data_url
        ).then(function(response) {
            // console.log(response);
            self.customer = response.data.customer;
            self.address = response.data.address;
            self.country_list = response.data.country_list;
            self.pdf_format_list = response.data.pdf_format_list;
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
                self.customer.pdf_format_id = 11310; // REGULAR PDF FORMAT FOR CNDN
                self.switch_value = 'Active';
                self.state_list = [{ 'id': '', 'name': 'Select State' }];
                self.city_list = [{ 'id': '', 'name': 'Select City' }];
                //Customer cash limit by Karthick T on 14-12-2020
                self.customer.cash_limit_status = 1;
            }
            //Outlet by Karthick T on 23-10-2020
            self.outlet_list = response.data.outlet_list;
            //IMS type BY PARTHIBAN V ON 29-07-2021
            self.ims_type_list=response.data.ims_type_list;
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
                $('#submit').button('loading');
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
                            $location.path('/customer-pkg/customer/list');
                            $scope.$apply();
                        } else {
                            if (!res.success == true) {
                                $('#submit').button('reset');
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
                                $('#submit').button('reset');
                                $location.path('/customer-pkg/customer/list');
                                $scope.$apply();
                            }
                        }
                    })
                    .fail(function(xhr) {
                        $('#submit').button('reset');
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