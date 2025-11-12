(function ($) {
    'use strict';

    function parseJSONAttribute($element, attribute) {
        var raw = $element.attr(attribute) || '[]';
        try {
            return JSON.parse(raw);
        } catch (error) {
            console.error('Invalid JSON in attribute', attribute, error);
            return [];
        }
    }

    function formatNumber(value, decimals) {
        decimals = typeof decimals === 'number' ? decimals : 2;
        return parseFloat(value || 0).toLocaleString(undefined, {
            minimumFractionDigits: decimals,
            maximumFractionDigits: decimals,
        });
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
        rows.push(['Costo tela unitario', result.fabric_cost_per_bag]);
        rows.push(['Costo costura unitario', result.stitch_cost_per_bag]);
        rows.push(['Costo electricidad unitario', result.electricity_cost_per_bag]);
        rows.push(['Costo unitario', result.unit_cost]);
        Object.keys(result.price_breakdown).forEach(function (qty) {
            var item = result.price_breakdown[qty];
            rows.push(['Precio unitario ' + qty, item.unit_price]);
            rows.push(['Precio total ' + qty, item.total_price]);
        });
        rows.push(['Vueltas', result.layouts_needed]);
        rows.push(['Longitud tendido (cm)', result.layout_length_cm]);
        rows.push(['Ancho tela (m)', result.price_per_meter && result.price_per_square_meter ? (result.price_per_meter / result.price_per_square_meter) : '']);
        rows.push(['Costo tela por metro', result.price_per_meter]);

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

        var presets = parseJSONAttribute($app, 'data-presets');
        var options = parseJSONAttribute($app, 'data-options');
        var presetMap = {};
        presets.forEach(function (preset) {
            presetMap[preset.model] = preset;
        });

        var $form = $('#eco-bag-estimator-form');
        var $bagType = $('#bag_type');
        var $gussetRow = $('#gusset-row');
        var $layoutRange = $('#layout_length_cm');
        var $layoutValue = $('#layout_length_value');
        var lastResult = null;

        var bagTypeLabels = {
            'with_gusset': $bagType.find('option[value="with_gusset"]').text(),
            'without_gusset': $bagType.find('option[value="without_gusset"]').text(),
            'drawstring': $bagType.find('option[value="drawstring"]').text(),
        };

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

        if (options && options.eco_bag_layout_length_min_cm) {
            $layoutRange.val(options.eco_bag_layout_length_min_cm);
        }
        updateLayoutValue();
        updateGussetVisibility();

        function renderResult(result) {
            $('#eco-bag-results-empty').addClass('hidden');
            $('#eco-bag-results').removeClass('hidden');
            $('#eco-bag-error').addClass('hidden');
            $('#eco-bag-success').addClass('hidden');

            var $piecesList = $('#eco-bag-pieces-list');
            $piecesList.empty();
            result.pieces.forEach(function (piece) {
                var text = piece.label + ' — ' + piece.quantity + ' × ' + formatNumber(piece.width_cm, 2) + ' cm × ' + formatNumber(piece.height_cm, 2) + ' cm (' + formatNumber(piece.area_m2, 4) + ' m²)';
                $('<li>').text(text).appendTo($piecesList);
            });

            var $consumption = $('#eco-bag-consumption');
            $consumption.empty();
            $consumption.append($('<li>').text(window.eco_bag_estimator_translations.consumption_per_bag + ': ' + formatNumber(result.area_per_bag_m2, 4) + ' m²'));
            $consumption.append($('<li>').text(window.eco_bag_estimator_translations.total_area + ': ' + formatNumber(result.total_area_m2, 4) + ' m²'));
            $consumption.append($('<li>').text(window.eco_bag_estimator_translations.layout_area + ': ' + formatNumber(result.layout_area_m2, 4) + ' m²'));
            $consumption.append($('<li>').text(window.eco_bag_estimator_translations.layouts + ': ' + result.layouts_needed));
            $consumption.append($('<li>').text(window.eco_bag_estimator_translations.linear_meters + ': ' + formatNumber(result.linear_meters_base, 3) + ' m'));
            $consumption.append($('<li>').text(window.eco_bag_estimator_translations.linear_meters_waste + ': ' + formatNumber(result.linear_meters_with_waste, 3) + ' m'));

            var $costs = $('#eco-bag-costs');
            $costs.empty();
            $costs.append($('<li>').text(window.eco_bag_estimator_translations.fabric_cost + ': $' + formatNumber(result.fabric_cost_per_bag, 2)));
            $costs.append($('<li>').text(window.eco_bag_estimator_translations.stitch_cost + ': $' + formatNumber(result.stitch_cost_per_bag, 2)));
            $costs.append($('<li>').text(window.eco_bag_estimator_translations.electricity_cost + ': $' + formatNumber(result.electricity_cost_per_bag, 2)));
            $costs.append($('<li>').text(window.eco_bag_estimator_translations.unit_cost + ': $' + formatNumber(result.unit_cost, 2)));

            var $prices = $('#eco-bag-prices');
            $prices.empty();
            Object.keys(result.price_breakdown).sort(function (a, b) { return parseInt(a, 10) - parseInt(b, 10); }).forEach(function (quantity) {
                var priceData = result.price_breakdown[quantity];
                var line = quantity + ' pzs — $' + formatNumber(priceData.unit_price, 2) + ' ' + window.eco_bag_estimator_translations.per_unit + ' / $' + formatNumber(priceData.total_price, 2) + ' ' + window.eco_bag_estimator_translations.total;
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
                        $('#eco-bag-error').removeClass('hidden').text(response.message || 'Error');
                    }
                })
                .fail(function (xhr) {
                    var message = 'Error';
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
