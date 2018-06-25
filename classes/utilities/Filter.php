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

class Filter
{
    private static $data;

    /**
     * @param string/array $data - data to sanitize
     * @return mixed - data sanitized
     */
    public static function sanitize($data)
    {
        self::$data = $data;

        if(is_array(self::$data)) {
            self::sanitize_array();
        } else {
            self::sanitize_string();
        }

        return self::$data;
    }

    /**
     * Sanitize string
     */
    private static function sanitize_string()
    {
        self::$data = trim(self::$data);
        self::$data = strip_tags(self::$data);
        self::$data = htmlspecialchars(self::$data, ENT_QUOTES, "UTF-8");
    }

    /**
     * Sanitize array
     */
    private static function sanitize_array()
    {
        self::$data = array_map("trim", self::$data);
        self::$data = array_map("strip_tags", self::$data);
        self::$data = array_map("self::htmlspecialchars_array", self::$data);
    }

    /**
     * Helper method to sanitize array with array map
     * @param $data - data to sanitize
     * @return string - sanitized data
     */
    private static function htmlspecialchars_array($data)
    {
        return htmlspecialchars($data, ENT_QUOTES, "UTF-8");
    }
}