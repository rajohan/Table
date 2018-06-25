/*
 * MIT License
 *
 * Copyright (c) 2018. Raymond Johannessen
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in
 * all copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
 * SOFTWARE.
 *
 * Raymond Johannessen Webutvikling
 * https://rajohan.no
 */
let sortBy;
let sortOrder;
let sortElement;
let pageNumber;
let searchTimer;
let hour = false;
let day = false;
let week = false;
let month = false;
let year = false;
let sortDate1 = "";
let sortDate2 = "";

$(document).ready(function () {

    // Load table
    $.ajax({
        method: "POST",
        url: "functions/ajax/getTable.php",
        dataType: "json",
        data: {
            "ajaxRequest": "true",
            "dbTable" : "TRAFFIC_LOG_VIEW",
        },
        success(data) {
            $(".table__wrapper .table tbody").html(data.table);
            $("#total_pages_number").text(data.totalPages);
            $("#table_page_number").attr("max", data.totalPages);
            $(".table__wrapper .table__no_results").remove();
            $(".table__wrapper").append(data.error);

            $(function(){
                $(".table").colResizable({
                    resizeMode: "fit",
                    liveDrag: true,
                    partialRefresh: true,
                    minWidth: 60,
                    draggingClass:"",
                    dragCursor: "col-resize",
                    hoverCursor: "col-resize"
                });
            });
        },
    });

    // Toggle sort by hour
    $("#sortHour").click(function () {
        let currentHourValue = hour;
        resetDateSortValues();
        hour = !currentHourValue;

        if(hour) {
            $("#sortHour").addClass("sortDateButtonActive");
        } else {
            $("#sortAll").addClass("sortDateButtonActive");
        }

        reloadTable();
    });

    // Toggle sort by day
    $("#sortDay").click(function () {
        let currentDayValue = day;
        resetDateSortValues();
        day = !currentDayValue;

        if(day) {
            $("#sortDay").addClass("sortDateButtonActive");
        } else {
            $("#sortAll").addClass("sortDateButtonActive");
        }

        reloadTable();
    });

    // Toggle sort by week
    $("#sortWeek").click(function () {
        let currentWeekValue =  week;
        resetDateSortValues();
        week = !currentWeekValue;

        if(week) {
            $("#sortWeek").addClass("sortDateButtonActive");
        } else {
            $("#sortAll").addClass("sortDateButtonActive");
        }

        reloadTable();
    });

    // Toggle sort by month
    $("#sortMonth").click(function () {
        let currentMonthValue = month;
        resetDateSortValues();
        month = !currentMonthValue;

        if(month) {
            $("#sortMonth").addClass("sortDateButtonActive");
        } else {
            $("#sortAll").addClass("sortDateButtonActive");
        }

        reloadTable();
    });

    // Toggle sort by year
    $("#sortYear").click(function () {
        let currentYearValue = year;
        resetDateSortValues();
        year = !currentYearValue;

        if(year) {
            $("#sortYear").addClass("sortDateButtonActive");
        } else {
            $("#sortAll").addClass("sortDateButtonActive");
        }

        reloadTable();
    });

    // Toggle sort all
    $("#sortAll").click(function () {
       resetDateSortValues();
        $("#sortAll").addClass("sortDateButtonActive");
       reloadTable();
    });

    // On sortDate1 value change
    $("#sortDate1").change(function () {
        sortDate1 = $("#sortDate1").val();
        if(sortDate1.length < 1 && sortDate2.length > 0) {
            $("#sortAll").addClass("sortDateButtonActive");
            reloadTable();
        }

        if(sortDate1.length > 0 && sortDate2.length > 0) {
            let tempSortDate1 = sortDate1;
            let tempSortDate2 = sortDate2;
            resetDateSortValues();
            $("#sortDate1").val(tempSortDate1);
            $("#sortDate2").val(tempSortDate2);
            sortDate1 = tempSortDate1;
            sortDate2 = tempSortDate2;

            reloadTable();
        }

    });

    // On sortDate2 value change
    $("#sortDate2").change(function () {
        sortDate2 = $("#sortDate2").val();

        if(sortDate2.length < 1 && sortDate1.length > 0) {
            $("#sortAll").addClass("sortDateButtonActive");
            reloadTable();
        }

        if(sortDate1.length > 0 && sortDate2.length > 0) {
            let tempSortDate1 = sortDate1;
            let tempSortDate2 = sortDate2;
            resetDateSortValues();
            $("#sortDate1").val(tempSortDate1);
            $("#sortDate2").val(tempSortDate2);
            sortDate1 = tempSortDate1;
            sortDate2 = tempSortDate2;

            reloadTable();
        }

    });

    // Toggle blur on table
    $(".action_bar #blur").change(function () {
        $(".table").toggleClass("table__blur");
    });

    // Toggle focus on table
    $(".action_bar #focus").change(function () {
        $(".table").toggleClass("table__focus");
    });

    // Toggle highlight row
    $(".action_bar #highlight_row").change(function () {
        $(".table").toggleClass("table__highlight_row");
    });

    // Toggle highlight column
    $(".table__wrapper").on("mouseenter mouseleave", ".table td, .table th", function () {
        if ($(".action_bar #highlight_column").is(":checked")) {
            $(".table td:nth-child(" + ($(this).index() + 1) + ")").toggleClass("table__highlight_column");
        }
    });

    // On table num rows value change
    $("#table_num_rows").change(function () {
        let loadingElement = $(".action_bar__left .loading");
        loadingElement.show();
        reloadTable();
        loadingElement.hide();
    });

    // On table page number value change
    $("#table_page_number").change(function () {
        pageNumber = $("#table_page_number").val();
        reloadTable();
    });

    // Table search
    $("#table_search").on("change paste keyup click", function () {
        clearTimeout(searchTimer);
        $(".action_bar__center .loading").show();
        searchTimer = setTimeout(function () {
            reloadTable();
            $(".action_bar__center .loading").hide();
        }, 1000);
    });

});

// Recover the table options set on table reload
function recoverTableOptions() {
    if ($(".action_bar #blur").is(":checked")) {
        $(".table").addClass("table__blur");
    }

    if ($(".action_bar #focus").is(":checked")) {
        $(".table").addClass("table__focus");
    }

    if ($(".action_bar #highlight_row").is(":checked")) {
        $(".table").addClass("table__highlight_row");
    }

    $(".table .arrow_down").addClass("double_arrow").removeClass("arrow_down");
    $(".table .arrow_up").addClass("double_arrow").removeClass("arrow_up");
    $("#" + sortElement).attr("data-sort-by", sortOrder);

    let sortElementSpan = "#" + sortElement + " span";

    if(sortOrder === "ASC") {
        $(sortElementSpan).removeClass("double_arrow");
        $(sortElementSpan).removeClass("arrow_down");
        $(sortElementSpan).addClass("arrow_up");
    } else {
        $(sortElementSpan).removeClass("double_arrow");
        $(sortElementSpan).removeClass("arrow_up");
        $(sortElementSpan).addClass("arrow_down");
    }
}

// Sort the table by column
function sortTable(column) {
    sortElement = $(column).attr("id");
    sortBy = $(column).attr("data-database-column");
    sortOrder = $(column).attr("data-sort-by");

    if (sortOrder === "ASC") {
        sortOrder = "DESC";
    }
    else if (sortOrder === "DESC") {
        sortOrder = "ASC"
    } else {
        sortOrder = "ASC";
    }

    reloadTable();
}

// Reset all Date sort variables to false and clear date fields
function resetDateSortValues() {
    hour = day = week = month = year = false;
    sortDate1 = sortDate2 = "";
    $("#sortDate1").val("");
    $("#sortDate2").val("");
    $(".action_bar__top__left button").removeClass("sortDateButtonActive");
}

// Previous table page button
function previousTablePage() {
    let pageNumberElement = $("#table_page_number");
    let minNumber = parseInt(pageNumberElement.attr("min"));
    pageNumber = parseInt(pageNumberElement.val());

    if(pageNumber > minNumber) {
        let loadingElement = $(".action_bar__right__text .loading");
        loadingElement.show();
        pageNumber--;
        pageNumberElement.val(pageNumber);
        reloadTable();
        loadingElement.hide();
    }
}
// Next table page button
function nextTablePage() {
    let pageNumberElement = $("#table_page_number");
    let maxNumber = parseInt(pageNumberElement.attr("max"));
    pageNumber = parseInt(pageNumberElement.val());

    if(pageNumber < maxNumber) {
        pageNumber++;
        pageNumberElement.val(pageNumber);
        reloadTable();
    }
}

// Reload table
function reloadTable() {
    $.ajax({
        method: "POST",
        url: "functions/ajax/getTable.php",
        dataType: "json",
        data: {
            "ajaxRequest": "true",
            "dbTable" : "TRAFFIC_LOG_VIEW",
            "search": $("#table_search").val(),
            "limit": $("#table_num_rows").val(),
            "sortBy" : sortBy,
            "sortOrder" : sortOrder,
            "pageNumber" : pageNumber,
            "sortHour" : hour,
            "sortDay" : day,
            "sortWeek" : week,
            "sortMonth" : month,
            "sortYear" : year,
            "sortDate1" : sortDate1,
            "sortDate2" : sortDate2
        },
        success(data) {
            $(".table__wrapper .table tbody").html(data.table);
            $("#total_pages_number").text(data.totalPages);
            $("#table_page_number").attr("max", data.totalPages);
            $(".table__wrapper .table__no_results").remove();
            $(".table__wrapper").append(data.error);
            recoverTableOptions();
        },
    });
}