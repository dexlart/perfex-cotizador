(function ($) {
    'use strict';

    function parseJSONAttribute($element, attribute, fallback) {
        var raw = $element.attr(attribute);
        if (!raw) {
            return fallback;
        }

        try {
            return JSON.parse(raw);
        } catch (error) {
            console.error('Invalid JSON in attribute', attribute, error);
            return fallback;
        }
    }

    function formatNumber(value, decimals) {
        decimals = typeof decimals === 'number' ? decimals : 2;
        return parseFloat(value || 0).toLocaleString(undefined, {
            minimumFractionDigits: decimals,
            maximumFractionDigits: decimals,
        });
    }

    function formatCurrency(value, decimals) {
        return '$' + formatNumber(value, typeof decimals === 'number' ? decimals : 2);
    }

    function formatPercentage(value, decimals) {
        return formatNumber(value, typeof decimals === 'number' ? decimals : 2) + '%';
    }

    function buildDescription(result) {
        var pieces = [];
        pieces.push('Base ' + formatNumber(result.base_cm, 2) + ' cm');
        pieces.push('Altura ' + formatNumber(result.height_cm, 2) + ' cm');
        if (result.bag_type === 'with_gusset') {
            pieces.push('Fuelle ' + formatNumber(result.gusset_cm, 2) + ' cm');
        }
        pieces.push('Asas ' + formatNumber(result.handle_cm, 2) + ' cm');
        return pieces.join(', ');
    }

    function buildLongDescription(result, bagTypeLabel) {
        var lines = [];
        lines.push('Tipo: ' + bagTypeLabel);
        lines.push('Cantidad solicitada: ' + result.quantity);
        lines.push('Consumo por bolsa: ' + formatNumber(result.area_per_bag_m2, 4) + ' m²');
        lines.push('Metros lineales totales: ' + formatNumber(result.linear_meters_with_waste, 3) + ' m');
        lines.push('Vueltas: ' + result.layouts_needed);
        lines.push('--- Piezas ---');
        result.pieces.forEach(function (piece) {
            lines.push(piece.label + ': ' + piece.quantity + ' × ' + formatNumber(piece.width_cm, 2) + ' cm × ' + formatNumber(piece.height_cm, 2) + ' cm');
        });
        return lines.join('\n');
    }

    function buildCSV(result, bagTypeLabel) {
        var rows = [];
        rows.push(['Campo', 'Valor']);
        rows.push(['Modelo', result.model]);
        rows.push(['Tipo', bagTypeLabel]);
        rows.push(['Base (cm)', result.base_cm]);
        rows.push(['Altura (cm)', result.height_cm]);
        if (result.bag_type === 'with_gusset') {
            rows.push(['Fuelle (cm)', result.gusset_cm]);
        }
        rows.push(['Asa (cm)', result.handle_cm]);
        rows.push(['Cantidad', result.quantity]);
        rows.push(['Área por bolsa (m2)', result.area_per_bag_m2]);
        rows.push(['Área total (m2)', result.total_area_m2]);
        rows.push(['Metros lineales con merma', result.linear_meters_with_waste]);
        rows.push(['Precio tela por metro', result.price_per_meter]);
        rows.push(['Precio tela por m2', result.price_per_square_meter]);
        rows.push(['Ancho de tela (m)', result.fabric_width_m]);
        rows.push(['Costo tela unitario', result.fabric_cost_per_bag]);
        rows.push(['Costo costura unitario', result.stitch_cost_per_bag]);
        rows.push(['Costo electricidad unitario', result.electricity_cost_per_bag]);
        rows.push(['Merma general (%)', result.general_waste_percentage]);
        rows.push(['Merma de tendido (%)', result.layout_waste_percentage]);
        rows.push(['Varios (%)', result.misc_percentage]);
        rows.push(['Margen aplicado (%)', result.margin_percentage]);
        rows.push(['Costo unitario', result.unit_cost]);
        Object.keys(result.price_breakdown).forEach(function (qty) {
            var item = result.price_breakdown[qty];
            rows.push(['Precio unitario ' + qty, item.unit_price]);
            rows.push(['Precio total ' + qty, item.total_price]);
        });
        rows.push(['Vueltas', result.layouts_needed]);
        rows.push(['Longitud tendido (cm)', result.layout_length_cm]);

        rows.push(['--- Piezas ---', '']);
        rows.push(['Etiqueta', 'Cantidad', 'Ancho (cm)', 'Alto (cm)', 'Área (m2)']);
        result.pieces.forEach(function (piece) {
            rows.push([
                piece.label,
                piece.quantity,
                piece.width_cm,
                piece.height_cm,
                piece.area_m2,
            ]);
        });

        return rows.map(function (row) {
            return row.map(function (cell) {
                if (typeof cell === 'number') {
                    cell = cell.toString();
                }
                if (cell === null || typeof cell === 'undefined') {
                    cell = '';
                }
                cell = cell.toString().replace(/"/g, '""');
                if (cell.indexOf(',') !== -1 || cell.indexOf('\n') !== -1) {
                    cell = '"' + cell + '"';
                }
                return cell;
            }).join(',');
        }).join('\n');
    }

    $(function () {
        var $app = $('#eco-bag-estimator-app');
        if (!$app.length) {
            return;
        }

        var presets = parseJSONAttribute($app, 'data-presets', []);
        var options = parseJSONAttribute($app, 'data-options', {});
        if (!options || Array.isArray(options)) {
            options = {};
        }

        var presetMap = {};
        presets.forEach(function (preset) {
            presetMap[preset.model] = preset;
        });

        var translations = window.eco_bag_estimator_translations || {};
        var canEditOptions = String($app.data('can-edit-options')) === '1';

        var $form = $('#eco-bag-estimator-form');
        var $bagType = $('#bag_type');
        var $gussetRow = $('#gusset-row');
        var $layoutRange = $('#layout_length_cm');
        var $layoutValue = $('#layout_length_value');
        var $optionsForm = $('#eco-bag-options-form');
        var $optionsFeedback = $('#eco-bag-options-feedback');
        var $optionMetrics = $('#eco-bag-option-metrics');
        var lastResult = null;

        var bagTypeLabels = {
            'with_gusset': $bagType.find('option[value="with_gusset"]').text(),
            'without_gusset': $bagType.find('option[value="without_gusset"]').text(),
            'drawstring': $bagType.find('option[value="drawstring"]').text(),
        };

        function updateOptionLocalCache(newOptions) {
            options = $.extend({}, options, newOptions || {});
        }

        function updateGussetVisibility() {
            if ($bagType.val() === 'with_gusset') {
                $gussetRow.show();
            } else {
                $gussetRow.hide();
            }
        }

        function updateLayoutValue() {
            $layoutValue.text($layoutRange.val());
        }

        function applyLayoutBounds() {
            if (!options) {
                return;
            }

            if (typeof options.eco_bag_layout_length_min_cm !== 'undefined') {
                $layoutRange.attr('min', options.eco_bag_layout_length_min_cm);
            }
            if (typeof options.eco_bag_layout_length_max_cm !== 'undefined') {
                $layoutRange.attr('max', options.eco_bag_layout_length_max_cm);
            }

            var min = parseFloat($layoutRange.attr('min')) || 0;
            var max = parseFloat($layoutRange.attr('max')) || min;
            if (min > max) {
                max = min;
                $layoutRange.attr('max', max);
            }

            var current = parseFloat($layoutRange.val());
            if (isNaN(current) || current < min || current > max) {
                $layoutRange.val(min);
            }

            updateLayoutValue();
        }

        function populateOptionsForm() {
            if (!$optionsForm.length) {
                return;
            }

            $optionsForm.find('input[name]').each(function () {
                var $input = $(this);
                var name = $input.attr('name');
                if (typeof options[name] !== 'undefined') {
                    $input.val(options[name]);
                }
            });
        }

        function renderOptionMetrics() {
            if (!$optionMetrics.length) {
                return;
            }

            $optionMetrics.find('.eco-bag-metric').each(function () {
                var $metric = $(this);
                var key = $metric.data('option');
                if (!key) {
                    return;
                }
                var value = options[key];
                var type = $metric.data('type') || 'number';
                var suffix = $metric.data('suffix') || '';
                var formatted = '—';

                if (typeof value !== 'undefined' && value !== null && value !== '') {
                    switch (type) {
                        case 'currency':
                            formatted = formatCurrency(value);
                            break;
                        case 'percentage':
                            formatted = formatPercentage(value);
                            break;
                        default:
                            formatted = formatNumber(value, 2);
                            break;
                    }
                }

                $metric.find('.eco-bag-metric-value').text(formatted + suffix);
            });
        }

        function showOptionsFeedback(type, message) {
            if (!$optionsFeedback.length) {
                return;
            }

            if (!message) {
                $optionsFeedback.addClass('hidden').removeClass('alert-success alert-danger alert-info');
                $optionsFeedback.text('');
                return;
            }

            $optionsFeedback.removeClass('hidden alert-success alert-danger alert-info');
            if (type === 'success') {
                $optionsFeedback.addClass('alert-success');
            } else if (type === 'info') {
                $optionsFeedback.addClass('alert-info');
            } else {
                $optionsFeedback.addClass('alert-danger');
            }
            $optionsFeedback.text(message);
        }

        function bindOptionsForm() {
            if (!$optionsForm.length) {
                return;
            }

            $optionsForm.on('submit', function (event) {
                event.preventDefault();

                var $button = $('#eco-bag-options-save').prop('disabled', true);
                showOptionsFeedback('info', translations.options_saving || '...');

                $.post(admin_url('eco_bag_estimator/update_options'), $optionsForm.serialize())
                    .done(function (response) {
                        if (response && response.status) {
                            updateOptionLocalCache(response.data || {});
                            renderOptionMetrics();
                            populateOptionsForm();
                            applyLayoutBounds();
                            showOptionsFeedback('success', response.message || translations.options_saved);
                        } else {
                            showOptionsFeedback('danger', (response && response.message) || translations.options_error);
                        }
                    })
                    .fail(function (xhr) {
                        var message = translations.options_error;
                        if (xhr.responseJSON && xhr.responseJSON.message) {
                            message = xhr.responseJSON.message;
                        }
                        showOptionsFeedback('danger', message);
                    })
                    .always(function () {
                        $button.prop('disabled', false);
                    });
            });
        }

        function fillPreset(model) {
            var preset = presetMap[model];
            if (!preset || !preset.specs) {
                return;
            }

            $('#model').val(preset.model);
            if (preset.specs.bag_type) {
                $bagType.val(preset.specs.bag_type).trigger('change');
            }
            if (preset.specs.base_cm) {
                $('#base_cm').val(preset.specs.base_cm);
            }
            if (preset.specs.height_cm) {
                $('#height_cm').val(preset.specs.height_cm);
            }
            if (preset.specs.gusset_cm) {
                $('#gusset_cm').val(preset.specs.gusset_cm);
            }
            if (preset.specs.handle_cm) {
                $('#handle_cm').val(preset.specs.handle_cm);
            }
        }

        $('#preset_selector').on('change', function () {
            var value = $(this).val();
            if (value) {
                fillPreset(value);
            }
        });

        $bagType.on('change', function () {
            updateGussetVisibility();
        });

        $('.preset-quantity').on('click', function () {
            $('#quantity').val($(this).data('value'));
        });

        $layoutRange.on('input change', function () {
            updateLayoutValue();
        });

        if (typeof options.eco_bag_layout_length_min_cm !== 'undefined') {
            $layoutRange.val(options.eco_bag_layout_length_min_cm);
        }

        renderOptionMetrics();
        populateOptionsForm();
        applyLayoutBounds();
        if (canEditOptions) {
            showOptionsFeedback(null);
            bindOptionsForm();
        }
        updateGussetVisibility();

        function renderResult(result) {
            $('#eco-bag-results-empty').addClass('hidden');
            $('#eco-bag-results').removeClass('hidden');
            $('#eco-bag-error').addClass('hidden');
            $('#eco-bag-success').addClass('hidden');

            if (result.options_snapshot) {
                updateOptionLocalCache(result.options_snapshot);
                renderOptionMetrics();
                populateOptionsForm();
                applyLayoutBounds();
            }

            var $piecesList = $('#eco-bag-pieces-list');
            $piecesList.empty();
            result.pieces.forEach(function (piece) {
                var text = piece.label + ' — ' + piece.quantity + ' × ' + formatNumber(piece.width_cm, 2) + ' cm × ' + formatNumber(piece.height_cm, 2) + ' cm (' + formatNumber(piece.area_m2, 4) + ' m²)';
                $('<li>').text(text).appendTo($piecesList);
            });

            var $consumption = $('#eco-bag-consumption');
            $consumption.empty();
            $consumption.append($('<li>').text(translations.consumption_per_bag + ': ' + formatNumber(result.area_per_bag_m2, 4) + ' m²'));
            $consumption.append($('<li>').text(translations.total_area + ': ' + formatNumber(result.total_area_m2, 4) + ' m²'));
            $consumption.append($('<li>').text(translations.layout_area + ': ' + formatNumber(result.layout_area_m2, 4) + ' m²'));
            $consumption.append($('<li>').text(translations.layouts + ': ' + result.layouts_needed));
            $consumption.append($('<li>').text(translations.linear_meters + ': ' + formatNumber(result.linear_meters_base, 3) + ' m'));
            $consumption.append($('<li>').text(translations.linear_meters_waste + ': ' + formatNumber(result.linear_meters_with_waste, 3) + ' m'));

            var $costs = $('#eco-bag-costs');
            $costs.empty();
            $costs.append($('<li>').text(translations.fabric_cost + ': ' + formatCurrency(result.fabric_cost_per_bag)));
            $costs.append($('<li>').text(translations.stitch_cost + ': ' + formatCurrency(result.stitch_cost_per_bag)));
            $costs.append($('<li>').text(translations.electricity_cost + ': ' + formatCurrency(result.electricity_cost_per_bag)));

            if (typeof result.price_per_meter !== 'undefined') {
                $costs.append($('<li>').text(translations.price_per_meter + ': ' + formatCurrency(result.price_per_meter) + (translations.price_per_meter_suffix || '')));
            }
            if (typeof result.price_per_square_meter !== 'undefined') {
                $costs.append($('<li>').text(translations.price_per_square_meter + ': ' + formatCurrency(result.price_per_square_meter) + (translations.price_per_square_suffix || '')));
            }
            if (typeof result.fabric_width_m !== 'undefined') {
                $costs.append($('<li>').text(translations.fabric_width + ': ' + formatNumber(result.fabric_width_m, 2) + ' m'));
            }
            if (typeof result.general_waste_percentage !== 'undefined') {
                $costs.append($('<li>').text(translations.general_waste + ': ' + formatPercentage(result.general_waste_percentage)));
            }
            if (typeof result.layout_waste_percentage !== 'undefined') {
                $costs.append($('<li>').text(translations.layout_waste + ': ' + formatPercentage(result.layout_waste_percentage)));
            }
            if (typeof result.misc_percentage !== 'undefined') {
                $costs.append($('<li>').text(translations.misc_percentage + ': ' + formatPercentage(result.misc_percentage)));
            }
            if (typeof result.margin_percentage !== 'undefined') {
                $costs.append($('<li>').text(translations.margin + ': ' + formatPercentage(result.margin_percentage)));
            }
            $costs.append($('<li>').text(translations.unit_cost + ': ' + formatCurrency(result.unit_cost)));

            var $prices = $('#eco-bag-prices');
            $prices.empty();
            Object.keys(result.price_breakdown).sort(function (a, b) { return parseInt(a, 10) - parseInt(b, 10); }).forEach(function (quantity) {
                var priceData = result.price_breakdown[quantity];
                var line = quantity + ' pzs — ' + formatCurrency(priceData.unit_price) + ' ' + translations.per_unit + ' / ' + formatCurrency(priceData.total_price) + ' ' + translations.total;
                $('<li>').text(line).appendTo($prices);
            });
        }

        $form.on('submit', function (event) {
            event.preventDefault();

            var formData = $form.serialize();
            var $button = $('#calculate-button').prop('disabled', true);
            $('#eco-bag-error').addClass('hidden');

            $.post(admin_url('eco_bag_estimator/calculate'), formData)
                .done(function (response) {
                    if (response && response.status) {
                        lastResult = response.data;
                        renderResult(response.data);
                    } else {
                        $('#eco-bag-error').removeClass('hidden').text(response.message || translations.generic_error || 'Error');
                    }
                })
                .fail(function (xhr) {
                    var message = translations.generic_error || 'Error';
                    if (xhr.responseJSON && xhr.responseJSON.message) {
                        message = xhr.responseJSON.message;
                    }
                    $('#eco-bag-error').removeClass('hidden').text(message);
                })
                .always(function () {
                    $button.prop('disabled', false);
                });
        });

        $('#save-as-product').on('click', function () {
            if (!lastResult) {
                return;
            }

            var bagTypeLabel = bagTypeLabels[lastResult.bag_type] || lastResult.bag_type;
            var description = lastResult.model + ' — ' + buildDescription(lastResult);
            var longDescription = buildLongDescription(lastResult, bagTypeLabel);
            var selectedQuantity = lastResult.quantity;
            var priceData = lastResult.price_breakdown[selectedQuantity] || { unit_price: lastResult.unit_price_base };

            var payload = {
                model: lastResult.model,
                bag_type: lastResult.bag_type,
                description: description,
                long_description: longDescription,
                rate: priceData.unit_price,
                sku: lastResult.model,
            };

            $.post(admin_url('eco_bag_estimator/save_as_product'), payload)
                .done(function (response) {
                    if (response && response.status) {
                        $('#eco-bag-success').removeClass('hidden').text(response.message || 'OK');
                    } else {
                        $('#eco-bag-error').removeClass('hidden').text(response.message || 'Error');
                    }
                })
                .fail(function (xhr) {
                    var message = 'Error';
                    if (xhr.responseJSON && xhr.responseJSON.message) {
                        message = xhr.responseJSON.message;
                    }
                    $('#eco-bag-error').removeClass('hidden').text(message);
                });
        });

        $('#export-csv').on('click', function () {
            if (!lastResult) {
                return;
            }
            var bagTypeLabel = bagTypeLabels[lastResult.bag_type] || lastResult.bag_type;
            var csv = buildCSV(lastResult, bagTypeLabel);
            var blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' });
            var url = URL.createObjectURL(blob);
            var a = document.createElement('a');
            a.href = url;
            a.download = lastResult.model + '_cotizacion.csv';
            document.body.appendChild(a);
            a.click();
            document.body.removeChild(a);
            URL.revokeObjectURL(url);
        });
    });
})(jQuery);
