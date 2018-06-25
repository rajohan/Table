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

declare(strict_types=1);

class Table
{
    private $dbConnection;
    private $dbTable;
    private $dbColumns;
    private $sortBy;
    private $sortOrder;
    private $limit;
    private $limitQuery;
    private $pageNumber;
    private $search;
    private $dbDateColumn;
    private $sortDatesType;
    private $sortDates;
    private $dates;
    private $timezone;
    private $tableData;
    private $rowCount;
    private $output;
    private $error;

    /**
     ************************************* Table constructor *************************************
     *
     * @param Database $dbConnection - A database connection
     * @param string $dbTable        - Table to get data from
     * @param array $dbColumns       - All the db columns to get data from, in the desired output order
     * @param string $sortBy         - Optional: The db column to sort by. Defaults to ID
     * @param string $sortOrder      - Optional: Sort order (ASC/DESC). Defaults to DESC
     * @param string $limit          - Optional: Result row limit. Defaults to 50
     * @param string $pageNumber     - Optional: Result page number to output. Defaults to 1
     * @param string $search         - Optional: Search/Filter string.
     * @param string $dbDateColumn   - Optional: Name of the date column in the db. Used when $sortDatesType is set.
     * @param string $sortDatesType  - Optional: Type of date filter to apply.
     *                                  - Options: hour, day, week, month, year, dates.
     *                                  - Remember: $sortDates have to be passed in to use the dates option.
     * @param array $sortDates       - Optional: Two dates to limit the result to.
     *                                  - Remember: $dbDateColumn and $sortDatesType have to be set.
     */
    public function __construct(Database $dbConnection, string $dbTable, array $dbColumns, string $sortBy="",
                                string $sortOrder="", string $limit="", string $pageNumber="", string $search="",
                                string $dbDateColumn="", string $sortDatesType="", array $sortDates=[])
    {
        $this->dbConnection = $dbConnection;
        $this->dbTable = Filter::sanitize($dbTable);
        $this->dbColumns = Filter::sanitize($dbColumns);
        $this->sortBy = !empty($sortBy) ? Filter::sanitize($sortBy) : "ID";
        $this->sortOrder = $sortOrder === "ASC" ? "ASC" : "DESC";
        $this->limit = !empty($limit) ? Filter::sanitize($limit) : "50";
        $this->pageNumber = !empty($pageNumber) ? Filter::sanitize($pageNumber) : "1";
        $this->search = Filter::sanitize($search);
        $this->dbDateColumn = Filter::sanitize($dbDateColumn);
        $this->sortDatesType = Filter::sanitize($sortDatesType);
        $this->sortDates = Filter::sanitize($sortDates);
        $this->dates = array();
        $this->timezone = new DateTimeZone(date_default_timezone_get());
        $this->tableData = null;
        $this->rowCount = null;
        $this->output = null;
        $this->error = null;
    }

    /**
     ************************************* Get table Method **************************************
     *
     * returns the table rows as a json object. With "table", "totalPages", "error".
     */
    public function getTable()
    {
        $this->getTableData();

        // Loop through each row of data from the query result
        foreach ($this->tableData as $tableRow) {

            if(empty($tableRow->USERNAME)) {
                $tableRow->USERNAME = "N/A";
            }

            $this->output .= "<tr>";

            // Set values for each column in the table row
            foreach ($this->dbColumns as $dbColumn) {

                if ($dbColumn === "USERNAME") {
                    continue;
                }

                if ($dbColumn === "USER") {
                    $this->output .= "<td title='$tableRow->USERNAME (" . $tableRow->$dbColumn . ")'>
                                     $tableRow->USERNAME (" . $tableRow->$dbColumn . ")</td>";
                } else {
                    $this->output .= "<td title='" . $tableRow->$dbColumn . "'>" . $tableRow->$dbColumn . "</td>";
                }
            }

            $this->output .= "</tr>";
        }

        if(count($this->tableData) < 1) {
            $this->error .= "<span class='table__no_results'>No data matching your search filters was found.</span>";
        }

        echo json_encode(array("table" => $this->output, "totalPages" => $this->rowCount, "error" => $this->error));
    }

    /**
     **************************** Helper method to get the table data ****************************
     */
    private function getTableData()
    {
        $this->handleLimit();
        $this->handlePageNumber();

        if (!empty($this->sortDatesType) && !empty($this->dbDateColumn)) {
            $this->handleSortByDates();
        }

        if (!empty($this->search)) {

            $where = array();

            foreach($this->dbColumns as $dbColumn) {
                $where[$dbColumn] = "%" . $this->search . "%";
            }

            $this->tableData = $this->dbConnection->search($this->dbTable, $where, "*", "OR", "ORDER BY $this->sortBy $this->sortOrder",
                "LIMIT " . $this->limitQuery, array(), $this->dbDateColumn, $this->dates);

            $this->rowCount = ceil(count($this->dbConnection->search($this->dbTable, $where, "ID", "OR", "", "", array(),
                    $this->dbDateColumn, $this->dates)) / $this->limit);


        } else {
            $this->tableData = $this->dbConnection->select($this->dbTable, array(), "*", "OR", "ORDER BY $this->sortBy $this->sortOrder",
                "LIMIT " . $this->limitQuery, array(), $this->dbDateColumn, $this->dates);

            $this->rowCount = ceil(count($this->dbConnection->select($this->dbTable, array(), "ID", "OR", "", "", array(),
                    $this->dbDateColumn, $this->dates)) / $this->limit);
        }
    }

    /**
     ***************************** Helper method to handle the limit *****************************
     */
    private function handleLimit()
    {
        if (is_numeric($this->limit) && $this->limit > 0) {
            $this->limitQuery = $this->limit;
        } else {
            $this->limit = 50;
            $this->limitQuery = $this->limit;
        }
    }

    /**
     ************************** Helper method to handle the page number **************************
     */
    private  function handlePageNumber()
    {
        if(is_numeric($this->pageNumber) && $this->pageNumber > 0) {
            $this->pageNumber--;

            if ($this->pageNumber < 1) {
                $this->limitQuery = "0, " . $this->limit;
            } else {
                $this->limitQuery = ($this->pageNumber * $this->limit) . ", " . $this->limit;
            }
        }
    }

    /**
     ************************* Helper method to handle the sort by dates *************************
     */
    private function handleSortByDates()
    {
        $time1 = array("hour" => 00, "minutes" => 00, "seconds" => 00);
        $time2 = array("hour" => 23, "minutes" => 59, "seconds" => 59);
        $interval = "";
        switch(strtolower($this->sortDatesType)) {
            case "hour":
                $interval = "PT1H";
                $time1 = $time2 = array("hour" => (int) date("H"), "minutes" => (int) date("i"), "seconds" => (int) date("s"));
                break;
            case "day":
                break;
            case "week":
                $interval = "P1W";
                break;
            case "month":
                $interval = "P1M";
                break;
            case "year":
                $interval = "P1Y";
                break;
            case "dates":
                if($this->checkValidDate($this->sortDates[0]) && $this->checkValidDate($this->sortDates[1])) {
                    $tempDate1 = new DateTime($this->sortDates[0], $this->timezone);
                    $tempDate2 = new DateTime($this->sortDates[1], $this->timezone);
                    $date1 = $tempDate1 > $tempDate2 ? $tempDate2 : $tempDate1;
                    $date2 = $tempDate1 > $tempDate2 ? $tempDate1 : $tempDate2;
                }
                break;
            default:
                break;
        }

        if(!isset($date1) || !isset($date2)) {
            $date1 = new DateTime(date("Y-m-d"), $this->timezone);
            $date2 = new DateTime(date("Y-m-d"), $this->timezone);
        }

        $date1->setTime($time1["hour"], $time1["minutes"], $time1['seconds']);
        $date2->setTime($time2["hour"], $time2["minutes"], $time2['seconds']);

        if(!empty($interval)) {
            try {
                $date1->sub(new DateInterval($interval));
            } catch (Exception $exception) {}
        }

        $this->dates = array($date1->format("Y-m-d H:i:s"), $date2->format("Y-m-d H:i:s"));
    }

    /**
     ************************* Helper method to check if a date is valid *************************
     *
     * @param $date - date to check
     *
     * @return bool - true/false
     */
    private function checkValidDate($date) {
        $date = explode("-", $date);
        return checkdate((int) $date[1], (int) $date[2], (int) $date[0]);
    }
}