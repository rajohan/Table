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

class Database
{
    private const HOST = "localhost";
    private const DB_NAME = "rajohan_no";
    private const USERNAME = "root";
    private const PASSWORD = "";
    private $connection;
    private $stmt;
    private $lastInsertId;
    private static $connectionInstance;

    public function __construct()
    {
        $this->lastInsertId = 0;

        if (!isset(self::$connectionInstance)) {
            try {
                self::$connectionInstance = new PDO("mysql:host=" . self::HOST . "; dbname=" . self::DB_NAME, self::USERNAME, self::PASSWORD);
                self::$connectionInstance->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            } catch (PDOException $exception) {
                echo $exception->getMessage();
            }
        }

        $this->connection = self::$connectionInstance;
        $this->stmt = $this->connection->prepare("");
    }

    /**
     ************************************ Database Select Method ***************************************
     *
     * @param string $table       - Database Table.
     * @param array $where        - Optional: Array holding the filters/'WHERE' clause for the query.
     * @param string $columns     - Optional: the column to select (SELECT * FROM ...), defaults to *.
     * @param string $whereMode   - Optional: Add an 'AND' or 'OR' after each item in the $where array, defaults to AND.
     * @param string $order       - Optional: string holding the 'ORDER BY' clause.
     * @param string $limit       - Optional: string holding the 'LIMIT' clause.
     * @param array $dataTypes    - Optional: Pass in data types as an array in equal order to the $where.
     *                              - Options: int/integer, bool/boolean, str/string.
     *                              - Data type will default to string if nothing is passed in (PDO::PARAM_STR).
     * @param string $dateColumn  - Optional: Date column in the table. Will only be used if $dates are provided.
     * @param array $dates        - Optional: Pass in two dates to limit the result to rows between two dates.
     *                              - Time will default to 00:00:00 if its not provided.
     *                              - Remember: Dates passed in have to be in the same format as the database.
     *                              - For MySQL this is YYYY-MM-DD HH:II:SS. Lowest date have to be passed in first.
     *                              - Correct: Array("2018-06-21 00:00:00", "2018-06-22 23:59:59");
     *                              - Invalid: Array("2018-06-22 00:00:00", "2018-06-21 23:59:59");
     * @param string $returnType  - Optional: Choose data type to get returned result as.
     *                              - Options: obj/object, class, array/arr/assoc. Defaults to object (PDO::FETCH_OBJ).
     *                              - Remember to set $returnClass if class is chosen or return type will be set to object.
     * @param string $returnClass - Optional: Class to return data as when class is chosen as $returnType.
     *
     * @return mixed              - Returns as object, class or array based on $returnType choice.
     *
     * @example $db->select("users",
     *                      array("id" => 55,
     *                            "firstName" => "Raymond"),
     *                      "*",
     *                      "OR",
     *                      "ORDER BY ID ASC",
     *                      "LIMIT 20",
     *                      array("int", "str", "str", "str"),
     *                      "DATE",
     *                      "Array("2018-06-21 00:00:00", "2018-06-22 23:59:59")",
     *                      "Class",
     *                      "TestClass");
     */
    public function select(string $table, array $where=[], string $columns="*", string $whereMode="AND",
                           string $order="", string $limit="", array $dataTypes=[], string $dateColumn="",
                           array $dates=[], string $returnType="object", string $returnClass="")
    {
        $whereFormatted = $this->formatWhereCondition($where, $whereMode);
        $datesFormatted = $this->formatDates($where, $dateColumn, $dates);

        $this->stmt = $this->connection->prepare("SELECT $columns FROM $table $whereFormatted $datesFormatted $order $limit");

        $where = array_values($where);
        $where = array_merge($where, $dates);

        $dataTypes = $this->setDataType($where, $dataTypes);

        foreach ($where as $key => $item) {
            $this->stmt->bindValue($key + 1, $item, $dataTypes[$key]);
        }

        $this->stmt->execute();

        $formattedReturnType = $this->formatReturnType($returnType, $returnClass);

        if ($formattedReturnType === PDO::FETCH_CLASS && !empty($returnClass)) {
            return $this->stmt->fetchAll($formattedReturnType, $returnClass);
        } else {
            return $this->stmt->fetchAll($formattedReturnType);
        }
    }

    /**
     ************************************ Database Search Method ***************************************
     *
     * @param string $table       - Database Table.
     * @param array $where        - Optional: Array holding the filters/'WHERE' clause for the query.
     * @param string $columns     - Optional: the column to select (SELECT * FROM ...), defaults to *.
     * @param string $whereMode   - Optional: Add an 'AND' or 'OR' after each item in the $where array, defaults to OR.
     * @param string $order       - Optional: string holding the 'ORDER BY' clause.
     * @param string $limit       - Optional: string holding the 'LIMIT' clause.
     * @param array $dataTypes    - Optional: Pass in data types as an array in equal order to the $where.
     *                              - Options: int/integer, bool/boolean, str/string.
     *                              - Data type will default to string if nothing is passed in (PDO::PARAM_STR).
     * @param string $dateColumn  - Optional: Date column in the table. Will only be used if $dates are passed in.
     * @param array $dates        - Optional: Pass in two dates to limit the result to rows between two dates.
     *                              - Time will default to 00:00:00 if its not provided.
     *                              - Remember: Dates passed in have to be in the same format as the database.
     *                              - For MySQL this is YYYY-MM-DD HH:II:SS. Lowest date have to be passed in first.
     *                              - Correct: Array("2018-06-21 00:00:00", "2018-06-22 23:59:59");
     *                              - Invalid: Array("2018-06-22 00:00:00", "2018-06-21 23:59:59");
     * @param string $returnType  - Optional: Choose data type to get returned result as.
     *                              - Options: obj/object, class, array/arr/assoc. Defaults to object (PDO::FETCH_OBJ).
     *                              - Remember to set $returnClass if class is chosen or return type will be set to object.
     * @param string $returnClass - Optional: Class to return data as when class is chosen as $returnType.
     *
     * @return mixed              - Returns as object, class or array based on $returnType choice.
     *
     * @example $db->search("users",
     *                      array("lastName" => "%Johannessen%",
     *                            "firstName" => "%Raymond%"),
     *                      "*",
     *                      "OR",
     *                      "ORDER BY ID ASC",
     *                      "LIMIT 20",
     *                      array("str", "str"),
     *                      "DATE",
     *                      "Array("2018-06-21 00:00:00", "2018-06-22 23:59:59")",
     *                      "Class",
     *                      "TestClass");
     */
    public function search(string $table, array $where=[], string $columns="*", string $whereMode="OR",
                           string $order="", string $limit="", array $dataTypes=[], string $dateColumn="",
                           array $dates=[], string $returnType="object", string $returnClass="")
    {
        $whereFormatted = $this->formatWhereLikeCondition($where, $whereMode);
        $datesFormatted = $this->formatDates($where, $dateColumn, $dates);

        $this->stmt = $this->connection->prepare("SELECT $columns FROM $table $whereFormatted $datesFormatted $order $limit");

        $where = array_values($where);
        $where = array_merge($where, $dates);
        $dataTypes = $this->setDataType($where, $dataTypes);

        foreach ($where as $key => $item) {
            $this->stmt->bindValue($key + 1, $item, $dataTypes[$key]);
        }

        $this->stmt->execute();

        $formattedReturnType = $this->formatReturnType($returnType, $returnClass);

        if ($formattedReturnType === PDO::FETCH_CLASS && !empty($returnClass)) {
            return $this->stmt->fetchAll($formattedReturnType, $returnClass);
        } else {
            return $this->stmt->fetchAll($formattedReturnType);
        }
    }

    /**
     ******************************** Database Insert Method **********************************
     *
     * @param string $table       - Database Table.
     * @param array $columnsData  - Array of columns and data to insert to the assign columns.
     * @param array $dataTypes    - Optional: Pass in data types as an array in equal order to $columnsData.
     *                              - Options: int/integer, bool/boolean, str/string.
     *                              - Data type will default to string if nothing is passed in (PDO::PARAM_STR).
     *
     * @return boolean            - True = Success, False = Error.
     *
     * @example $db->insert("users",
     *                      Array("firstName" => "Raymond",
     *                            "lastName" => "Johannessen",
     *                            "email" => "mail@rajohan.no"),
     *                      Array("str", "str", "str"));
     */
    public function insert(string $table, Array $columnsData, Array $dataTypes=[])
    {
        $placeholders = $this->generatePlaceholders($columnsData);
        $columns = implode(", ", array_keys($columnsData));

        $this->stmt = $this->connection->prepare("INSERT INTO $table ($columns) VALUES ($placeholders)");

        $columnsData = array_values($columnsData);
        $dataTypes = $this->setDataType($columnsData, $dataTypes);

        foreach ($columnsData as $index => $data) {
            $this->stmt->bindValue($index + 1, $data, $dataTypes[$index]);
        }

        $result = $this->stmt->execute();
        $this->lastInsertId = (int)$this->connection->lastInsertId();

        return $result;
    }


    /**
     ******************************** Database Update Method **********************************
     *
     * @param string $table       - Database Table.
     * @param array $columnsData  - Array of columns and data to insert to the assign columns.
     * @param array $where        - Optional: Array holding the filters/'WHERE' clause for the query.
     * @param string $whereMode   - Optional: Add an 'AND' or 'OR' after each item in the $where array, defaults to AND.
     * @param array $dataTypes    - Optional: Pass in data types as an array in equal order to $columnsData.
     *                              - Options: int/integer, bool/boolean, str/string.
     *                              - Data type will default to string if nothing is passed in (PDO::PARAM_STR).
     *
     * @return boolean            - True = Success, False = Error.
     *
     * @example $db->update("users",
     *                      Array("firstName" => "Raymond",
     *                            "lastName" => "Johannessen",
     *                            "email" => "mail@rajohan.no"),
     *                      Array("id" => 1,
     *                            "username" => "Rajohan"),
     *                      "OR",
     *                      Array("str", "str", "str", "int", "str"));
     */
    public function update(string $table, array $columnsData, array $where=[], string $whereMode="AND", Array $dataTypes=[])
    {
        $columns = $this->appendPlaceholders($columnsData);
        $whereFormatted = $this->formatWhereCondition($where, $whereMode);

        $this->stmt = $this->connection->prepare("UPDATE $table SET $columns $whereFormatted");

        $columnsData = array_values($columnsData);
        $where = array_values($where);
        $data = array_merge($columnsData, $where);

        $dataTypes = $this->setDataType($data, $dataTypes);

        foreach ($data as $key => $item) {
            $this->stmt->bindValue($key + 1, $item, $dataTypes[$key]);
        }

        return $this->stmt->execute();
    }

    /**
     ******************************** Database Delete Method **********************************
     *
     * @param string $table       - Database Table.
     * @param array $where        - Optional: Array holding the filters/'WHERE' clause for the query.
     * @param string $whereMode   - Optional: Add an 'AND' or 'OR' after each item in the $where array, defaults to AND.
     * @param array $dataTypes    - Optional: Pass in data types as an array in equal order to $where.
     *                              - Options: int/integer, bool/boolean, str/string.
     *                              - Data type will default to string if nothing is passed in (PDO::PARAM_STR).
     *
     * @return boolean            - True = Success, False = Error.
     *
     * @example $db->delete("users",
     *                      Array("id" => 1,
     *                            "username" => "Rajohan"),
     *                      "OR",
     *                      Array("int", "str"));
     */
    public function delete(string $table, array $where=[], string $whereMode="AND", Array $dataTypes=[])
    {
        $whereFormatted = $this->formatWhereCondition($where, $whereMode);

        $this->stmt = $this->connection->prepare("DELETE FROM $table $whereFormatted");

        $where = array_values($where);
        $dataTypes = $this->setDataType($where, $dataTypes);

        foreach ($where as $key => $item) {
            $this->stmt->bindValue($key + 1, $item, $dataTypes[$key]);
        }

        return $this->stmt->execute();
    }

    /**
     ************************************ Get row count ***************************************
     *
     * @param string $table     - Database Table.
     * @param array $where      - Optional: Array holding the filters/'WHERE' clause for the query.
     * @param string $whereMode - Optional: Add an 'AND' or 'OR' after each item in the $where array, defaults to AND.
     * @param string $columns   - Optional: the column to select (SELECT count(*) FROM ...), defaults to *.
     * @param array $dataTypes  - Optional: Pass in data types as an array in equal order to the $where.
     *                              - Options: int/integer, bool/boolean, str/string.
     *                              - Data type will default to string if nothing is passed in (PDO::PARAM_STR).
     *
     * @return integer          - Row count.
     *
     * @example $db->count("users",
     *                     Array("id" => 1,
     *                           "firstName" => "Raymond"),
     *                     "OR",
     *                     "*",
     *                     Array("int", "str"));
     */
    public function count(string $table, Array $where=[], $whereMode="AND", string $columns="*", Array $dataTypes=[])
    {
        $whereFormatted = $this->formatWhereCondition($where, $whereMode);

        $this->stmt = $this->connection->prepare("SELECT count($columns) AS `count` FROM $table $whereFormatted");

        $where = array_values($where);
        $dataTypes = $this->setDataType($where, $dataTypes);

        foreach ($where as $key => $item) {
            $this->stmt->bindValue($key + 1, $item, $dataTypes[$key]);
        }

        $this->stmt->execute();

        return (int)$this->stmt->fetchObject()->count;
    }

    /**
     ****************************** Get last inserted row's id ********************************
     *
     * @return integer - Last inserted row's Id.
     *
     * @example $db->id();
     */
    public function id()
    {
        return $this->lastInsertId;
    }

    /**
     ********************************* Get last used query ************************************
     *
     * @return string - Last used sql query (debugDumpParams)
     *
     * @example $db->sql();
     */
    public function sql()
    {
        return $this->stmt->debugDumpParams();
    }

    /**
     **************************** Close the database connection *******************************
     *
     * @return void
     *
     * @example $db->closeConnection();
     */
    public function closeConnection()
    {
        $this->stmt->closeCursor();
        $this->stmt = null;
        $this->connection = null;
        self::$connectionInstance = null;
    }

    /**
     ************ Helper method to generate placeholders for prepared statements **************
     *
     * @param array $dataArray - Array of data to generate placeholders for.
     *
     * @return string          - String of generated placeholders. Format: "?, ?, ?, ?"
     */
    private function generatePlaceholders(Array $dataArray)
    {
        $dataArrayLength = count($dataArray);
        $placeholders = "";

        for ($i = 1; $i <= $dataArrayLength; $i++) {
            $placeholders .= $i !== $dataArrayLength ? "?, " : "?";
        }

        return $placeholders;
    }

    /**
     ************* Helper method to append placeholders for prepared statements ***************
     *
     * @param array $data - Data to append placeholders to.
     *
     * @return string     - String with placeholders appended. Format: "firstName=?, lastName=?"
     */
    private function appendPlaceholders(array $data)
    {
        $data = implode("=?, ", array_keys($data));
        $data .= "=?";

        return $data;
    }

    /**
     ********************* Helper method to format the where condition ************************
     *
     * @param array $where      - Data to format the where condition on
     * @param string $whereMode - Add an 'AND' or 'OR' after each item in the $where array, defaults to AND
     *
     * @return string           - String with placeholders appended. Format: "WHERE (id=? AND username=?)"
     */
    private function formatWhereCondition(array $where, string $whereMode="AND")
    {
        $andOr = $whereMode === "OR" ? "OR" : "AND";

        $where = implode("=? $andOr ", array_keys($where));

        if (!empty($where)) {
            $where = "WHERE ($where=?)";
        }

        return $where;
    }

    /**
     ******************** Helper method to format the where like condition ********************
     *
     * @param array $where      - Data to format the where like condition on
     * @param string $whereMode - Add an 'AND' or 'OR' after each item in the $where array, defaults to OR
     *
     * @return string           - String with placeholders appended. Format: "WHERE (id LIKE ? OR username LIKE ?)"
     */
    private function formatWhereLikeCondition(array $where, string $whereMode="OR")
    {
        $andOr = $whereMode === "OR" ? "OR" : "AND";

        $where = implode(" LIKE ? $andOr ", array_keys($where));

        if (!empty($where)) {
            $where = "WHERE ($where LIKE ?)";
        }

        return $where;
    }

    /**
     ********************* Helper method to format between dates condition ********************
     *
     * @param array $where       - Used to check if there is a where condition
     * @param string $dateColumn - Used to check if a date column is set
     * @param array $dates       - The two dates to add the between condition for
     *
     * @return string            - String with placeholders added in the between condition.
     *                              - Format: "WHERE (DATE BETWEEN ? AND ?" or "AND (DATE BETWEEN ? AND ?)"
     */
    private function formatDates(array $where, string $dateColumn, array $dates=[])
    {
        if (!empty($dateColumn) && count($dates) === 2 && count($where) < 1) {
            $formattedDates = "WHERE (" . $dateColumn . " BETWEEN  ?  AND  ?)";
        } else if (!empty($dateColumn) && count($dates) === 2 && count($where) > 0) {
            $formattedDates = "AND (" . $dateColumn . " BETWEEN ? AND ?)";
        } else {
            $formattedDates = "";
        }

        return $formattedDates;
    }

    /**
     *********************** Helper method to format the return type **************************
     *
     * @param string $returnType  - Data type to get returned result as.
     *                            - Options: obj/object, class, array/arr/assoc. Defaults to object (PDO::FETCH_OBJ).
     * @param string $returnClass - Class to return data as when class is chosen as $returnType
     *
     * @return string             - Data type value associated with PDO::FETCH_OBJ, PDO::FETCH_CLASS, PDO::FETCH_ASSOC.
     */
    private function formatReturnType(string $returnType, string $returnClass)
    {
        switch (strtolower($returnType)) {
            case "object":
            case "obj":
                $returnType = PDO::FETCH_OBJ;
                break;
            case "class":
                if (!empty($returnClass)) {
                    $returnType = PDO::FETCH_CLASS;
                } else {
                    $returnType = PDO::FETCH_OBJ;
                }
                break;
            case "array":
            case "arr":
            case "assoc":
                $returnType = PDO::FETCH_ASSOC;
                break;
            default:
                $returnType = PDO::FETCH_OBJ;
                break;
        }

        return $returnType;
    }

    /**
     ********************* Helper method to set the correct data types ************************
     *
     * @param array $data      - Array of data to link to the data types
     * @param array $dataTypes - Array with dataType for $data. Options: int/integer, bool/boolean, str/string
     *
     * @return array           - Data type value associated with PDO::PARAM_INT, PDO::PARAM_STR AND PDO::PARAM_BOOL.
     */
    private function setDataType(Array $data, Array $dataTypes)
    {
        foreach ($data as $key => $value) {
            if (!isset($dataTypes[$key]) || empty($dataTypes[$key])) {
                $dataTypes[$key] = PDO::PARAM_STR;
            } else {
                switch (strtolower($dataTypes[$key])) {
                    case "integer":
                    case "int":
                        $dataTypes[$key] = PDO::PARAM_INT;
                        break;
                    case "string":
                    case "str":
                        $dataTypes[$key] = PDO::PARAM_STR;
                        break;
                    case "boolean":
                    case "bool":
                        $dataTypes[$key] = PDO::PARAM_BOOL;
                        break;
                    default:
                        $dataTypes[$key] = PDO::PARAM_STR;
                        break;
                }
            }
        }
        return $dataTypes;
    }
}
?>