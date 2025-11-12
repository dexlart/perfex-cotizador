(function ($) {
    'use strict';

    var state = {
        models: (window.app && app.options && app.options.ecoBagEstimatorModels) || {},
        products: (window.app && app.options && app.options.ecoBagEstimatorProducts) || {},
        messages: (window.app && app.options && app.options.ecoBagEstimatorMessages) || {}
    };

    function parseDataset(value) {
        if (!value) {
            return {};
        }
        try {
            return JSON.parse(value);
        } catch (error) {
            return {};
        }
    }

    function notify(message) {
        if (!message) {
            return;
        }
        if (typeof alert_float === 'function') {
            alert_float('info', message);
        }
    }

    function formatNumber(value) {
        if (typeof app !== 'undefined' && typeof app.formatNumber === 'function') {
            return app.formatNumber(value);
        }

        var floatValue = parseFloat(value);
        if (!isFinite(floatValue)) {
            return value;
        }

        return Math.abs(floatValue % 1) < 0.0001 ? floatValue.toFixed(0) : floatValue.toFixed(2);
    }

    function updateModelSummary(modelKey) {
        var model = state.models[modelKey];
        var $label = $('#ecoBagSelectedModelLabel');
        var $handle = $('#ecoBagHandleType');
        var $dimensions = $('#ecoBagModelDimensions');
        var $extra = $('#ecoBagModelExtra');
        var $weight = $('#ecoBagModelWeight');

        if (!model) {
            $label.text($('#ecoBagSelectedModelLabel').data('empty') || '--');
            $handle.text('');
            $dimensions.text('--');
            $extra.text($('#ecoBagModelExtra').data('help') || '');
            $weight.text('--');
            return null;
        }

        $label.text(model.name || '');
        $handle.text(model.handle_type || '');
        $dimensions.text(formatNumber(model.panel_width_cm) + ' × ' + formatNumber(model.panel_height_cm) + ' cm');
        if (typeof _l === 'function') {
            $extra.text(_l('eco_bag_estimator_dimensions_detail', formatNumber(model.gusset_cm)));
        } else {
            $extra.text('Gusset ' + model.gusset_cm + ' cm');
        }
        $weight.text(formatNumber(model.fabric_weight_gsm) + ' g/m²');

        return model;
    }

    function hydrateProductDetail(product) {
        var $detail = $('#ecoBagSelectedProductDetail');
        if (!$detail.length) {
            return;
        }

        var $name = $('#ecoBagProductName');
        var $description = $('#ecoBagProductDescription');
        var $notes = $('#ecoBagProductNotes');
        var $link = $('#ecoBagProductLink');

        if (!product || $.isEmptyObject(product)) {
            $name.text(typeof _l === 'function' ? _l('eco_bag_estimator_no_product_selected') : '');
            $description.text('');
            $notes.text('');
            $link.addClass('is-hidden').attr('href', '#');
            return;
        }

        $name.text(product.name || '');
        $description.text(product.short_description || '');
        $notes.text(product.notes || '');

        if (product.url) {
            $link.removeClass('is-hidden').attr('href', product.url);
        } else {
            $link.addClass('is-hidden').attr('href', '#');
        }
    }

    function applyDefaults(defaults) {
        if (!defaults) {
            return;
        }

        Object.keys(defaults).forEach(function (field) {
            var $field = $("[name='" + field + "']");
            if (!$field.length) {
                return;
            }
            $field.val(defaults[field]).trigger('change');
        });

        notify(state.messages.productDefaultsApplied);
    }

    function computeLayoutData() {
        var modelKey = $("[name='model']").val();
        var model = state.models[modelKey];
        var rollWidth = parseFloat($('#ecoBagRollWidth').val()) || 0;
        var quantity = parseInt($('#ecoBagQuantity').val(), 10) || 0;

        if (!model) {
            return null;
        }

        var bagWidth = model.panel_width_cm + (2 * (model.gusset_cm + model.seam_allowance_cm));
        var bagHeight = model.panel_height_cm + model.fold_allowance_cm + model.seam_allowance_cm;
        var panelsPerLay = rollWidth > 0 ? Math.max(1, Math.floor(rollWidth / bagWidth)) : 0;
        var layCount = panelsPerLay > 0 && quantity > 0 ? Math.ceil(quantity / panelsPerLay) : 0;
        var wasteWidth = rollWidth - panelsPerLay * bagWidth;
        var wastePercent = rollWidth > 0 ? Math.max(wasteWidth, 0) / rollWidth : 0;

        return {
            modelKey: modelKey,
            model: model,
            rollWidth: rollWidth,
            bagWidth: bagWidth,
            bagHeight: bagHeight,
            panelsPerLay: panelsPerLay,
            layCount: layCount,
            wasteWidth: wasteWidth,
            wastePercent: wastePercent,
            quantity: quantity
        };
    }

    function updateLayoutMeta(data) {
        if (!data) {
            $('#ecoBagPreviewRollWidth').text('--');
            $('#ecoBagPreviewPanelWidth').text('--');
            $('#ecoBagPreviewPanelHeight').text('--');
            $('#ecoBagPreviewPanelsPerLay').text('--');
            $('#ecoBagPreviewLayCount').text('--');
            if (state.messages.noPreviewData) {
                notify(state.messages.noPreviewData);
            }
            return;
        }

        $('#ecoBagPreviewRollWidth').text(formatNumber(data.rollWidth) + ' cm');
        $('#ecoBagPreviewPanelWidth').text(formatNumber(data.bagWidth) + ' cm');
        $('#ecoBagPreviewPanelHeight').text(formatNumber(data.bagHeight) + ' cm');
        $('#ecoBagPreviewPanelsPerLay').text(data.panelsPerLay ? data.panelsPerLay : '--');
        $('#ecoBagPreviewLayCount').text(data.layCount ? data.layCount : '--');
    }

    function drawPreview(data, initial) {
        var canvas = document.getElementById('ecoBagLayPreview');
        if (!canvas || !canvas.getContext || !data) {
            return;
        }

        var ctx = canvas.getContext('2d');
        var margin = 30;
        var rollWidth = data.rollWidth || initial.rollWidth || 0;
        var bagWidth = data.bagWidth || initial.bagWidth || 0;
        var bagHeight = data.bagHeight || initial.bagHeight || 0;
        var panelsPerLay = data.panelsPerLay || initial.panelsPerLay || 0;

        ctx.clearRect(0, 0, canvas.width, canvas.height);

        if (!(rollWidth > 0 && bagWidth > 0 && bagHeight > 0 && panelsPerLay > 0)) {
            return;
        }

        var availableWidth = canvas.width - margin * 2;
        var scale = availableWidth / rollWidth;
        var scaledRollWidth = rollWidth * scale;
        var scaledBagWidth = bagWidth * scale;
        var scaledBagHeight = bagHeight * scale;
        var offsetX = (canvas.width - scaledRollWidth) / 2;
        var offsetY = margin;

        // Draw roll area
        ctx.fillStyle = '#eef5ff';
        ctx.fillRect(offsetX, offsetY, scaledRollWidth, scaledBagHeight + 80);
        ctx.strokeStyle = '#1f6feb';
        ctx.lineWidth = 2;
        ctx.strokeRect(offsetX, offsetY, scaledRollWidth, scaledBagHeight + 80);

        var gap = 10;
        var innerOffsetX = offsetX + gap;
        var innerOffsetY = offsetY + gap;

        ctx.fillStyle = '#6fc2ff';
        ctx.strokeStyle = '#005a9c';
        for (var index = 0; index < panelsPerLay; index += 1) {
            var x = innerOffsetX + index * (scaledBagWidth + gap);
            if (x + scaledBagWidth > offsetX + scaledRollWidth) {
                break;
            }
            ctx.fillRect(x, innerOffsetY, scaledBagWidth, scaledBagHeight);
            ctx.strokeRect(x, innerOffsetY, scaledBagWidth, scaledBagHeight);
        }

        // Waste area
        var wasteWidth = rollWidth - panelsPerLay * bagWidth;
        if (wasteWidth > 0) {
            var scaledWasteWidth = wasteWidth * scale;
            var wasteX = offsetX + scaledRollWidth - scaledWasteWidth;
            ctx.fillStyle = '#ffd6a5';
            ctx.strokeStyle = '#ff8b3d';
            ctx.fillRect(wasteX, innerOffsetY, scaledWasteWidth, scaledBagHeight);
            ctx.strokeRect(wasteX, innerOffsetY, scaledWasteWidth, scaledBagHeight);
        }
    }

    function updateLayoutPreview(isInitial) {
        var $canvas = $('#ecoBagLayPreview');
        if (!$canvas.length) {
            return;
        }
        var initial = {
            bagWidth: parseFloat($canvas.data('bag-width')) || 0,
            bagHeight: parseFloat($canvas.data('bag-height')) || 0,
            rollWidth: parseFloat($canvas.data('roll-width')) || 0,
            panelsPerLay: parseInt($canvas.data('panels-per-lay'), 10) || 0,
            layCount: parseInt($canvas.data('lay-count'), 10) || 0
        };

        var data = computeLayoutData();
        if (!data && isInitial) {
            data = {
                bagWidth: initial.bagWidth,
                bagHeight: initial.bagHeight,
                rollWidth: initial.rollWidth,
                panelsPerLay: initial.panelsPerLay,
                layCount: initial.layCount
            };
        }

        updateLayoutMeta(data);
        drawPreview(data, initial);
    }

    function bindProductSelection() {
        var $list = $('#ecoBagProductList');
        var $productSlug = $('#ecoBagProductSlug');
        var $modelSelect = $("[name='model']");

        $list.on('click', '.eco-bag-product', function (event) {
            event.preventDefault();
            var $item = $(this);
            var slug = $item.data('product');
            var defaults = parseDataset($item.attr('data-defaults'));
            var model = $item.data('model');

            $list.find('.eco-bag-product').removeClass('is-active');
            $item.addClass('is-active');

            $productSlug.val(slug);

            if (model) {
                $modelSelect.val(model).trigger('change');
            }

            applyDefaults(defaults);
            updateLayoutPreview();

            var product = $.extend({}, state.products[slug] || {}, defaults, {
                slug: slug,
                model: model,
                defaults: defaults
            });
            hydrateProductDetail(state.products[slug] || product);
        });

        var $search = $('#ecoBagProductSearch');
        $search.on('input', function () {
            var term = $(this).val().toLowerCase();
            $list.find('.eco-bag-product').each(function () {
                var $product = $(this);
                var name = ($product.data('name') || '').toString().toLowerCase();
                var description = ($product.data('description') || '').toString().toLowerCase();
                var matches = !term || name.indexOf(term) !== -1 || description.indexOf(term) !== -1;
                $product.toggle(matches);
            });
            $list.find('.eco-bag-product-category').each(function () {
                var $category = $(this);
                var hasVisible = $category.find('.eco-bag-product:visible').length > 0;
                $category.toggle(hasVisible);
            });
        });
    }

    function bindFormListeners() {
        $("[name='model']").on('change', function () {
            updateModelSummary($(this).val());
            updateLayoutPreview();
        });

        $('#ecoBagEstimatorForm').on('input change', '#ecoBagRollWidth, #ecoBagQuantity', function () {
            updateLayoutPreview();
        });
    }

    app.on('appInit', function () {
        bindProductSelection();
        bindFormListeners();

        var $modelSelect = $("[name='model']");
        updateModelSummary($modelSelect.val());

        var $detail = $('#ecoBagSelectedProductDetail');
        var productData = parseDataset($detail.attr('data-selected-product'));
        hydrateProductDetail(productData);

        updateLayoutPreview(true);
    });
})(jQuery);
