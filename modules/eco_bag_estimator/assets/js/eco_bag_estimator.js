(function ($) {
    'use strict';

    function updateDefaults(models, selected) {
        if (!selected || !models[selected]) {
            return;
        }

        var model = models[selected];
        var mappings = {
            fabric_cost_per_meter: 'default_fabric_cost_per_meter',
            thread_per_bag_m: 'default_thread_per_bag_m',
            thread_spool_length_m: 'default_thread_spool_length_m',
            thread_spool_cost: 'default_thread_spool_cost',
            labor_cost_per_bag: 'default_labor_cost_per_bag'
        };

        Object.keys(mappings).forEach(function (field) {
            var defaultKey = mappings[field];
            if (model[defaultKey] !== undefined && !$("[name='" + field + "']").val()) {
                $("[name='" + field + "']").val(model[defaultKey]);
            }
        });
    }

    app.on('appInit', function () {
        var models = app.options.ecoBagEstimatorModels || {};
        var $modelSelect = $("[name='model']");

        updateDefaults(models, $modelSelect.val());

        $modelSelect.on('change', function () {
            updateDefaults(models, $(this).val());
        });
    });
})(jQuery);
