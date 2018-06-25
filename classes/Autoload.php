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

class Autoload
{
    /**
     * Autoload classes - searches this files directory and all sub directories
     *
     * @param string $class
     * @param string $dir
     */
    public static function start(string $class, string $dir = null)
    {

        if (is_null($dir)) {
            $dir = __DIR__;
        }

        foreach (scandir($dir) as $file) {

            if (is_dir($dir . "/" . $file) && substr($file, 0, 1) !== '.') {
                self::start($class, $dir . "/" . $file);
            }

            if ($file === $class . ".php") {
                if (is_readable($dir . "/" . $class . ".php")) {
                    require_once($dir . "/" . $class . ".php");
                }
            }
        }
    }

}

spl_autoload_register("Autoload::start");