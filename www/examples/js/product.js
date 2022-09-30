var filter = [];
//var property = [{"entity": "product", "field": "*"}];
var property = [];
//var sort = [{"field": $("#sort_select").val(), "direction": "ASC"}];
var sort = [{"field": "naam", "direction": "ASC"}];
var relation = [];
var producten = [];
var paging = {"page": 2, "items_per_page": 10};
var project = "LluG3gwZKPzC";
var token = "eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJ1c2VyX2lkIjoxLCJleHAiOjE1OTk3NTY0NzYsImlzcyI6IkxsdUczZ3daS1B6QyIsImlhdCI6MTU5OTcyMDQ3Nn0.tBVfPYWrvbCOQLgfSijiiflci8YwDY1Ol3TjWQigkVM";

function doAjaxRequest(endpoint, method, data, token, done_callback) {
    console.log("doAjaxRequest");

    var ajax_parameters = {
        url: endpoint,
        type: method,
        data: data,
        contentType: false,
    };

    if (token != "") {
        //ajax_parameters.headers = {"Authorization" : "Bearer " + token};
    }

    if (method == "POST" || method == "PUT") {
        ajax_parameters.processData = false;
    }
    console.log(ajax_parameters);
    $.ajax(ajax_parameters).done(function(response) {
        console.log("DONE")
        console.log(response);
        return done_callback(response);
    }).fail(function (msg) {
        console.log(msg.responseText);
        console.log('FAIL');
    }).always(function (msg) {
        console.log('ALWAYS');
    });
}

function toonProducten() {
    var data = {"filter": filter, "property": property, "sort": sort, "relation": relation};
    doAjaxRequest("/item?project="+project+"&entity=product", "GET", data, token,function(response) {
        producten = response.result.items;
        $("#producten_table tbody").html("");
        producten.forEach(function(product) {
            var image = "";
            if (product.image != null) {
                image = '<img src="' +  response.result.assets_path + "/" +  product.image.name + '" />';
            }
            var row = "<tr>" +
                "<td>" + product.naam + "</td>" +
                "<td>Product categorie 1</td>" +
                "<td>" + product.omschrijving + "</td>" +
                "<td>&euro; " + product.prijs + "</td>" +
                "<td>" + image + "</td>" +
                "<td>" +
                "<button class=\"btn btn-sm btn-primary\" onclick=\"$('#delete_id').val(" + product.id + ");\"  data-toggle=\"modal\" data-target=\"#delete_modal\">verwijderen</button>" +

                "<button class=\"btn btn-sm btn-primary\" onclick=\"toonProductPopup('update', '" + product.id + "')\" data-toggle=\"modal\" data-target=\"#product_modal\">bewerken</button>" +
                "</td>" +
                "</tr>";

            $("#producten_table").append(row);
        });
    });

}

function doSort() {
    sort = [{"field": $("#sort_select").val(), "direction": "ASC"}];
    toonProducten();
}

function doFilter() {
    filter = [];
    if ($("#filter_naam").val() != "") {
        filter.push({"field":"naam","operator":"LIKE", "value": "%" + $("#filter_naam").val() + "%"});
    }

    if ($("#filter_omschrijving").val() != "") {
        filter.push({"field":"omschrijving","operator":"LIKE", "value": "%" + $("#filter_omschrijving").val() + "%"});
    }

    if ($("#filter_prijs").val() != "") {
        filter.push({"field":"prijs","operator":">=", "value": $("#filter_prijs").val()});
    }

    toonProducten();
}

function doDelete() {
    var id = $('#delete_id').val();
    var data = {"filter": [["id", "=", id]]};
    doAjaxRequest("/item?project=LluG3gwZKPzC&entity=product", "DELETE", data, token,function(response) {
        toonProducten();
        $('#delete_modal').modal('hide');
    });
}

function doProductActie() {
    var product_actie = $("#product_actie").val();

    var formData = new FormData();

    var post_values = {
        "naam": $("#product_naam").val(),
        "omschrijving": $("#product_omschrijving").val(),
        "prijs": $("#product_prijs").val(),
        "image": $("#product_beeld_origineel").val()
    };
    formData.set("values", JSON.stringify(post_values));

    var post_image = $('#product_beeld')[0].files[0];
    formData.set("image",post_image);

    if (product_actie == "update") {
        var post_filter = ["id", "=", $("#product_id").val()];
        formData.set("filter", JSON.stringify(post_filter));

        var endpoint = "/item?project=LluG3gwZKPzC&entity=product";
        var method = "PUT";
    }

    if (product_actie == "insert") {
        var endpoint = "/item?project=LluG3gwZKPzC&entity=product";
        var method = "POST";
    }

    console.log(post_values);
    doAjaxRequest(endpoint, method, formData, token,function(response) {
        toonProducten();
        console.log(response);
        $('#product_modal').modal('hide');
    });
}



function toonProductPopup(actie, id) {
    $("#product_actie").val(actie);

    if (actie == "insert") {
        $("#product_modal_titel").html("Product toeveogen");
        $('#product_id').val("");
        $('#product_naam').val("");
        $('#product_omschrijving').val("");
        $('#product_prijs').val("");
        $('#product_beeld_origineel').val("");
    }

    if (actie == "update") {
        $("#product_modal_titel").html("Product wijzigen");
        doAjaxRequest("/item/single_read?project=LluG3gwZKPzC&entity=product&id=" + id, "GET", null, token,function(response) {
            $('#product_id').val(response.result.item.id);
            $('#product_naam').val(response.result.item.naam);
            $('#product_omschrijving').val(response.result.item.omschrijving);
            $('#product_prijs').val(response.result.item.prijs);
            $('#product_beeld_origineel').val(JSON.stringify(response.result.item.image));
            $('#product_beeld').val("");
            $('#product_beeld_label').html("Kies (nieuw) beeld");
        });
    }
}

function start() {

   toonProducten();

}