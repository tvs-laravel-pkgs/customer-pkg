@if(config('customer-pkg.DEV'))
    <?php $customer_pkg_prefix = '/packages/abs/customer-pkg/src';?>
@else
    <?php $customer_pkg_prefix = '';?>
@endif

<script type="text/javascript">
	app.config(['$routeProvider', function($routeProvider) {

	    $routeProvider.
	    when('/customer-pkg/customers/list', {
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

    var customer_list_template_url = "{{URL::asset($customer_pkg_prefix.'/public/angular/customer-pkg/pages/customer/list.html')}}";
    var customer_get_form_data_url = "{{url('customer-pkg/customer/get-form-data/')}}";
    var customer_form_template_url = "{{URL::asset($customer_pkg_prefix.'/public/angular/customer-pkg/pages/customer/form.html')}}";
    var customer_delete_data_url = "{{url('customer-pkg/customer/delete/')}}";
</script>
<script type="text/javascript" src="{{URL::asset($customer_pkg_prefix.'/public/angular/customer-pkg/pages/customer/controller.js?v=2')}}"></script>
