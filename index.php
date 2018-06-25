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
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1">
    <meta name="author" content="Raymond Johannessen, Raymond Johannessen Webutvikling">
    <title>Rajohan.no Table example</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.3.1/jquery.min.js"></script>
    <script src="assets/js/colResizable.min.js"></script>
    <script src="assets/js/script.js"></script>
</head>
<body>
<div class="action_bar__top">
    <div class="action_bar__top__left">
        <button type="submit" id="sortHour" title="Hour" role="button">
            Hour
        </button>
        <button type="submit" id="sortDay" title="Day" role="button">
            Day
        </button>
        <button type="submit" id="sortWeek" title="Week" role="button">
            Week
        </button>
        <button type="submit" id="sortMonth" title="Month" role="button">
            Month
        </button>
        <button type="submit" id="sortYear" title="Year" role="button">
            Year
        </button>
        <button type="submit" id="sortAll" class="sortDateButtonActive" title="All" role="button">
            All
        </button>
        <div class="action_bar__top__left__dates">
            <input type="date" id="sortDate1" title="Date" role="search">
            <span class="action_bar__top__left__dates__separator"> - </span>
            <input type="date" id="sortDate2" title="Date" role="search">
        </div>
    </div>
    <div class="action_bar__top__right">
        <button type="submit" name="refresh" title="Refresh table" role="button" onclick="reloadTable();">
            <svg class="action_bar__top__icon">
                <use xlink:href="assets/images/icons/refresh.svg#refresh"></use>
            </svg>
        </button>
    </div>
</div>
<div class="action_bar">
    <div class="action_bar__left">
        <div class="action_bar__left__item">
            Rows
            <label for="table_num_rows" class="action_bar__left__table_num_rows__label">
                <input id="table_num_rows" class="action_bar__left__table_num_rows" type="number" min="1"
                       step="1" list="table_num_rows_list" title="Number of rows to show" placeholder="50">
                <span class="loading"></span>
                <datalist id="table_num_rows_list">
                    <option value="10">
                    <option value="25">
                    <option value="50">
                    <option value="75">
                    <option value="100">
                    <option value="1000">
                </datalist>
            </label>
        </div>
        <div class="action_bar__left__item">
            Blur
            <label for="blur" class="toggle_switch" title="Blur rows not hovered">
                <input id="blur" type="checkbox" class="toggle_switch__checkbox" role="checkbox">
                <span class="toggle_switch__slider"></span>
            </label>
        </div>
        <div class="action_bar__left__item">
            Focus
            <label for="focus" class="toggle_switch" title="Focus row hovered">
                <input id="focus" type="checkbox" class="toggle_switch__checkbox" role="checkbox">
                <span class="toggle_switch__slider"></span>
            </label>
        </div>
        <div class="action_bar__left__item">
            HL Row
            <label for="highlight_row" class="toggle_switch" title="Highlight row hovered">
                <input id="highlight_row" type="checkbox" class="toggle_switch__checkbox" role="checkbox">
                <span class="toggle_switch__slider"></span>
            </label>
        </div>
        <div class="action_bar__left__item">
            HL Column
            <label for="highlight_column" class="toggle_switch" title="Highlight columns hovered">
                <input id="highlight_column" type="checkbox" class="toggle_switch__checkbox" role="checkbox">
                <span class="toggle_switch__slider"></span>
            </label>
        </div>
    </div>
    <div class="action_bar__center">
        <span class="action_bar__center__text">Filter</span>
        <label for="table_search">
            <input type="search" id="table_search" title="Filter table" list="table_search_list"  role="search"
                   placeholder="Start typing...">
            <datalist id="table_search_list">
            </datalist>
            <span class="loading"></span>
        </label>
    </div>
    <div class="action_bar__right">
        <div class="action_bar__right__page_number__wrapper">
            <span class="action_bar__right__text">Page</span>
            <label for="table_page_number">
                <svg class="action_bar__right__icon" onclick="previousTablePage()">
                    <title>Previous page</title>
                    <use xlink:href="assets/images/icons/arrow_left.svg#arrow_left"></use>
                </svg>
                <input id="table_page_number" class="action_bar__right__page_number" type="number"
                       title="Current page number" value="1" min="1" max="10" step="1">
                of <span id="total_pages_number" class="action_bar__right__total_pages_number">10</span>
                <svg class="action_bar__right__icon" onclick="nextTablePage()">
                    <title>Next page</title>
                    <use xlink:href="assets/images/icons/arrow_right.svg#arrow_right"></use>
                </svg>
            </label>
        </div>
    </div>
</div>
<div class="table__top__overlay">
</div>
<div class="table__wrapper">
    <table class="table">
        <thead>
        <tr>
            <th id="table_id" data-database-column="ID" data-sort-by="DESC" class="row_width_5" onclick="sortTable(this);">
                <span class="arrow_down">Id</span>
            </th>
            <th id="table_user" data-database-column="USERNAME" data-sort-by="" class="row_width_10" onclick="sortTable(this);">
                <span class="double_arrow">User (id)</span>
            </th>
            <th id="table_ip" data-database-column="IP" data-sort-by="" class="row_width_10" onclick="sortTable(this);">
                <span class="double_arrow">Ip</span>
            </th>
            <th id="table_referrer" data-database-column="REFERRER" data-sort-by="" class="row_width_20"
                onclick="sortTable(this);">
                <span class="double_arrow">Referrer</span>
            </th>
            <th id="table_browser" data-database-column="BROWSER" data-sort-by="" class="row_width_10"
                onclick="sortTable(this);">
                <span class="double_arrow">Browser</span>
            </th>
            <th id="table_platform" data-database-column="PLATFORM" data-sort-by="" class="row_width_10"
                onclick="sortTable(this);">
                <span class="double_arrow">Platform</span>
            </th>
            <th id="table_page" data-database-column="PAGE" data-sort-by="" class="row_width_20" onclick="sortTable(this);">
                <span class="double_arrow">Page</span>
            </th>
            <th id="table_date" data-database-column="DATE" data-sort-by="" class="row_width_15" onclick="sortTable(this);">
                <span class="double_arrow">Date</span>
            </th>
        </tr>
        </thead>
        <tbody>
            <!-- Populated by jQuery ajax call -->
        </tbody>
    </table>
</div>
</body>
</html>