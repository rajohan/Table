<?php
/**
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

if (isset($_POST["ajaxRequest"]) && $_POST['ajaxRequest'] === "true") {
    require_once("../../config/config.php");
    require_once("../../classes/Autoload.php");

    switch($_POST['dbTable']) {
        case "TRAFFIC_LOG_VIEW":
            $dbTable = "TRAFFIC_LOG_VIEW";
            $dbColumns = array("ID", "USERNAME", "USER", "IP", "REFERRER", "BROWSER", "PLATFORM", "PAGE", "DATE");
            $dbDateColumn = "DATE";
            break;
        default:
            echo "Database table parameter is required";
            exit();
            break;
    }

    $db = new Database();

    $sortBy = isset($_POST['sortBy']) ? $_POST['sortBy'] : "";
    $sortOrder = isset($_POST['sortOrder']) ? $_POST['sortOrder'] : "";
    $limit = isset($_POST['limit']) ? $_POST['limit'] : "";
    $pageNumber = isset($_POST['pageNumber']) ? $_POST['pageNumber'] : "";
    $search = isset($_POST['search']) ? $_POST['search'] : "";
    $sortDatesType = "";
    $sortDates = array();

    if(isset($_POST['sortHour']) && ($_POST['sortHour'] === "true")) {
        $sortDatesType = "hour";
    } else if(isset($_POST['sortDay']) && ($_POST['sortDay'] === "true")) {
        $sortDatesType = "day";
    } else if(isset($_POST['sortWeek']) && ($_POST['sortWeek'] === "true")) {
        $sortDatesType = "week";
    } else if(isset($_POST['sortMonth']) && ($_POST['sortMonth'] === "true")) {
        $sortDatesType = "month";
    } else if(isset($_POST['sortYear']) && ($_POST['sortYear'] === "true")) {
        $sortDatesType = "year";
    } else if(isset($_POST['sortDate1']) && !empty($_POST['sortDate1']) &&
        isset($_POST['sortDate2']) && !empty($_POST['sortDate2'])) {
        $sortDates = array($_POST['sortDate1'], $_POST['sortDate2']);
        $sortDatesType = "dates";
    }

    $table = new Table($db, $dbTable, $dbColumns, $sortBy, $sortOrder, $limit, $pageNumber, $search, $dbDateColumn, $sortDatesType, $sortDates);
    $table->getTable();
}