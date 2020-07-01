/*
 Document    : vssmp_graph.js
 Author      : Raghubendra Singh
 Description : To Generate the graph(with tooltip) on the basis of passed data.
 */

function generateSellerDashboardGraph(json_data) {
    var dataObj = JSON && JSON.parse(json_data) || jQuery.parseJSON(json_data);

    var ticks = [], total_orders = [], total_revenue = [], total_qty = [];
    graph_tooltip_data = [];
    for (var i = 0; i < dataObj.length; i++) {
        var tooltip_row = [];
        tooltip_row.push(['Total Revenue', dataObj[i]['formatted_total_revenue']]);
        tooltip_row.push(['Total Orders', parseInt(dataObj[i]['total_order'])]);
        tooltip_row.push(['Product Sold', parseInt(dataObj[i]['qty'])]);
        graph_tooltip_data.push(tooltip_row);
        ticks.push([i, dataObj[i]['xaxis']]);
        total_orders.push([i, parseInt(dataObj[i]['total_order'])]);
        total_revenue.push([i, dataObj[i]['total_revenue']]);
        total_qty.push([i, dataObj[i]['qty']]);
    }

    var dataset = [
        {
            label: "Total Revenue",
            data: total_revenue,
            yaxis: 1,
            bars: {order: 1, lineWidth: 0}
        },
        {
            label: "Total Orders",
            data: total_orders,
            yaxis: 2,
            bars: {order: 2, lineWidth: 0}
        }, {
            label: "Products Sold",
            data: total_qty,
            yaxis: 3,
            bars: {order: 3, lineWidth: 0}
        }
    ];

    var options = {
        bars: {
            show: true,
            barWidth: 0.2,
            fill: 1
        },
        series: {
            grow: {active: false}
        },
        xaxis: {ticks: ticks,autoscaleMargin: 0.01,axisLabel: graphLast7daysLabel,rotateTicks: 145},
        yaxes: [{
                min: 0,
                position: "left",
                color: "black",
                axisLabel: graphRevenueLabel,
                axisLabelUseCanvas: true,
                axisLabelFontSizePixels: 12,
                axisLabelFontFamily: 'Verdana, Arial',
                axisLabelPadding: 20
            }, {
                min: 0,
                position: "right",
                color: "black",
                axisLabel: graphOrdersLabel,
                axisLabelUseCanvas: true,
                axisLabelFontSizePixels: 12,
                axisLabelFontFamily: 'Verdana, Arial',
                axisLabelPadding: 3,
                tickDecimals: 0
            }, {
                min: 0,
                alignTicksWithAxis: 2,
                position: "right",
                color: "black",
                axisLabel: graphProductsLabel,
                axisLabelUseCanvas: true,
                axisLabelFontSizePixels: 12,
                axisLabelFontFamily: 'Verdana, Arial',
                axisLabelPadding: 3,
                tickDecimals: 0
            }
        ],
        legend: {
            noColumns: 0,
            backgroundColor: 'null',
            backgroundOpacity: 0.9,
            labelBoxBorderColor: '#000000',
            container: jQuery('#chartLegendHolder'),
            position: "ne"
        },
        grid: {
            hoverable: true,
            borderWidth: 1,
            borderColor: '#EEEEEE',
            mouseActiveRadius: 10,
            backgroundColor: "#ffffff",
            axisMargin: 20
        }
//        tooltip: true,
//            tooltipOpts: {
//            content: "%s : %y",
//            shifts: {x: -30, y: -50},
//            defaultTheme: false
//        }
    };

    previousPoint = null;
    previousLabel = null;
    jQuery.plot(jQuery("#graphicalReportsHolder"), dataset, options);
    jQuery("#graphicalReportsHolder").CreateVerticalGraphToolTip();
    
    window.onresize = function(event) {
        jQuery.plot(jQuery("#graphicalReportsHolder"), dataset, options);
    }
}

jQuery.fn.CreateVerticalGraphToolTip = function() {
    jQuery(this).bind("plothover", function(event, pos, item) {
        if (item) {
            if ((previousLabel != item.series.label) || (previousPoint != item.dataIndex)) {
                previousPoint = item.dataIndex;
                previousLabel = item.series.label;
                jQuery("#tooltip").remove();

                var x = item.datapoint[0];
                var y = item.datapoint[1];

                var color = item.series.color;
                showCustomTooltip(previousPoint, item.pageX, item.pageY, color,
                        "<strong>" + item.series.label + "</strong>" +
                        " : <strong>" + y + "</strong> ");
            }
        } else {
            jQuery("#tooltip").remove();
            previousPoint = null;
        }
    });
};

function showCustomTooltip(dataIndex, x, y, color)
{
    var html = '';
    var data_array = graph_tooltip_data[dataIndex];

    for (var i = 0; i < data_array.length; i++) {
        html += '<p class="bva_graph_tooltip"><strong>' + data_array[i][0] + ': </strong>' + data_array[i][1] + '</p>';
    }
    jQuery('<div id="tooltip">' + html + '</div>').css({
        position: 'absolute',
        display: 'none',
        top: y - 40,
        left: x - 120,
        border: '2px solid ' + color,
        padding: '5px',
        'font-size': '9px',
        'border-radius': '5px',
        'background-color': '#fff',
        'font-family': 'Verdana, Arial, Helvetica, Tahoma, sans-serif',
        opacity: 0.9
    }).appendTo("body").fadeIn(200);
};