app.factory('CustomerSvc', function(RequestSvc) {

    var model = 'customer';

    return {
        index: function(params) {
            return RequestSvc.get('/api/' + model + '/index', params);
        },
        read: function(id) {
            return RequestSvc.get('/api/' + model + '/read/' + id);
        },
        save: function(params) {
            return RequestSvc.post('/api/' + model + '/save', params);
        },
        saveFromNgData: function(params) {
            return RequestSvc.post('/api/' + model + '/save-from-ng-data', params);
        },
        remove: function(params) {
            return RequestSvc.post('api/' + model + '/delete', params);
        },
        options: function(params) {
            return RequestSvc.get('/api/' + model + '/options', params);
        },
    };

});
