<?php
namespace App\Libraries;
date_default_timezone_set('America/Mexico_City');// Zona horaria de Mexico
use DateTime;

class Fechas {

    var $DATE_SEPARATOR = "/";
    var $TIME_SEPARATOR = ":";
    var $THOUSANDS_SEP = ",";
    var $DECIMAL_POINT = ".";
    var $DATE_FORMAT = "yyyy/mm/dd";
    var $DATE_FORMAT_ID = 5;
    var $UNFORMAT_YEAR = 50;
    
    // Variable para usar los recursos de codeigniter dentro de esta clase
    protected $CI;

    function __construct() {
        // Variable para usar los recursos de codeigniter dentro de esta clase
        //$this->CI =& get_instance();
    }

    // Check Euro Date format (dd/mm/yyyy)
    function ValidateEuroDate($value) {
        return $this->ValidateDateEx($value, "euro", $this->DATE_SEPARATOR);
    }

// Check Euro Date format (dd/mm/yy)
    function ValidateShortEuroDate($value) {
        return $this->ValidateDateEx($value, "euroshort", $this->DATE_SEPARATOR);
    }

// Check date format
// Format: std/stdshort/us/usshort/euro/euroshort
    function ValidateDateEx($value, $format, $sep) {
        if (strval($value) == "")
            return TRUE;
        while (strpos($value, "  ") !== FALSE)
            $value = str_replace("  ", " ", $value);
        $value = trim($value);
        $arDT = explode(" ", $value);
        if (count($arDT) > 0) {
            if (preg_match('/^([0-9]{4})-([0][1-9]|[1][0-2])-([0][1-9]|[1|2][0-9]|[3][0|1])$/', $arDT[0], $matches)) { // Accept yyyy-mm-dd
                $sYear = $matches[1];
                $sMonth = $matches[2];
                $sDay = $matches[3];
            } else {
                $wrksep = "\\$sep";
                switch ($format) {
                    case "std":
                        $pattern = '/^([0-9]{4})' . $wrksep . '([0]?[1-9]|[1][0-2])' . $wrksep . '([0]?[1-9]|[1|2][0-9]|[3][0|1])$/';
                        break;
                    case "stdshort":
                        $pattern = '/^([0-9]{2})' . $wrksep . '([0]?[1-9]|[1][0-2])' . $wrksep . '([0]?[1-9]|[1|2][0-9]|[3][0|1])$/';
                        break;
                    case "us":
                        $pattern = '/^([0]?[1-9]|[1][0-2])' . $wrksep . '([0]?[1-9]|[1|2][0-9]|[3][0|1])' . $wrksep . '([0-9]{4})$/';
                        break;
                    case "usshort":
                        $pattern = '/^([0]?[1-9]|[1][0-2])' . $wrksep . '([0]?[1-9]|[1|2][0-9]|[3][0|1])' . $wrksep . '([0-9]{2})$/';
                        break;
                    case "euro":
                        $pattern = '/^([0]?[1-9]|[1|2][0-9]|[3][0|1])' . $wrksep . '([0]?[1-9]|[1][0-2])' . $wrksep . '([0-9]{4})$/';
                        break;
                    case "euroshort":
                        $pattern = '/^([0]?[1-9]|[1|2][0-9]|[3][0|1])' . $wrksep . '([0]?[1-9]|[1][0-2])' . $wrksep . '([0-9]{2})$/';
                        break;
                }
                if (!preg_match($pattern, $arDT[0]))
                    return FALSE;
                $arD = explode($sep, $arDT[0]); // Change $this->DATE_SEPARATOR to $sep
                switch ($format) {
                    case "std":
                    case "stdshort":
                        $sYear = $this->UnformatYear($arD[0]);
                        $sMonth = $arD[1];
                        $sDay = $arD[2];
                        break;
                    case "us":
                    case "usshort":
                        $sYear = $this->UnformatYear($arD[2]);
                        $sMonth = $arD[0];
                        $sDay = $arD[1];
                        break;
                    case "euro":
                    case "euroshort":
                        $sYear = $this->UnformatYear($arD[2]);
                        $sMonth = $arD[1];
                        $sDay = $arD[0];
                        break;
                }
            }
            if (!$this->ValidateDay($sYear, $sMonth, $sDay))
                return FALSE;
        }
        if (count($arDT) > 1 && !$this->ValidateTime($arDT[1]))
            return FALSE;
        return TRUE;
    }

// Check Date format (yyyy/mm/dd)
    function ValidateDate($value) {
        return $this->ValidateDateEx($value, "std", $this->DATE_SEPARATOR);
    }

// Unformat 2 digit year to 4 digit year
    function UnformatYear($yr) {
        if (strlen($yr) == 2) {
            if ($yr > $this->UNFORMAT_YEAR)
                return "19" . $yr;
            else
                return "20" . $yr;
        } else {
            return $yr;
        }
    }

    // Check day
    function ValidateDay($checkYear, $checkMonth, $checkDay) {
        $maxDay = 31;
        if ($checkMonth == 4 || $checkMonth == 6 || $checkMonth == 9 || $checkMonth == 11) {
            $maxDay = 30;
        } elseif ($checkMonth == 2) {
            if ($checkYear % 4 > 0) {
                $maxDay = 28;
            } elseif ($checkYear % 100 == 0 && $checkYear % 400 > 0) {
                $maxDay = 28;
            } else {
                $maxDay = 29;
            }
        }
        return $this->ValidateRange($checkDay, 1, $maxDay);
    }

    // Check time
    function ValidateTime($value) {
        if (strval($value) == "")
            return TRUE;
        return preg_match('/^(0[0-9]|1[0-9]|2[0-3])' . preg_quote($this->TIME_SEPARATOR) . '[0-5][0-9](' . preg_quote($this->TIME_SEPARATOR) . '[0-5][0-9])?$/', $value);
    }

    // Check number
    function ValidateNumber($value) {
        if (strval($value) == "")
            return TRUE;
        $pat = '/^[+-]?(\d{1,3}(' . (($this->THOUSANDS_SEP) ? '\\' . $this->THOUSANDS_SEP . '?' : '') . '\d{3})*(\\' .
                $this->DECIMAL_POINT . '\d+)?|\\' . $this->DECIMAL_POINT . '\d+)$/';
        return preg_match($pat, $value);
    }

// Check range
    function ValidateRange($value, $min, $max) {
        if (strval($value) == "")
            return TRUE;
        if (is_int($min) || is_float($min) || is_int($max) || is_float($max)) { // Number
            if ($this->ValidateNumber($value))
                $value = floatval($this->StrToFloat($value));
        }
        if ((!is_null($min) && $value < $min) || (!is_null($max) && $value > $max))
            return FALSE;
        return TRUE;
    }

// Convert string to float
    function StrToFloat($v) {
        $v = str_replace(" ", "", $v);
        $v = str_replace(array($this->THOUSANDS_SEP, $this->DECIMAL_POINT), array("", "."), $v);
        return $v;
    }

// Get current date in default date format
// $namedformat = -1|5|6|7 (see comment for FormatDateTime)
    function CurrentDate($namedformat = -1) {
        if (in_array($namedformat, array(5, 6, 7, 9, 10, 11, 12, 13, 14, 15, 16, 17))) {
            if ($namedformat == 5 || $namedformat == 9 || $namedformat == 12 || $namedformat == 15) {
                $DT = $this->FormatDateTime(date('Y-m-d'), 5);
            } elseif ($namedformat == 6 || $namedformat == 10 || $namedformat == 13 || $namedformat == 16) {
                $DT = $this->FormatDateTime(date('Y-m-d'), 6);
            } else {
                $DT = $this->FormatDateTime(date('Y-m-d'), 7);
            }
            return $DT;
        } else {
            return date('Y-m-d');
        }
    }

// Get current time in hh:mm:ss format
    function CurrentTime() {
        return date("H:i:s");
    }

// Get current date in default date format with time in hh:mm:ss format
// $namedformat = -1, 5-7, 9-11 (see comment for FormatDateTime)
    function CurrentDateTime($namedformat = -1) {
        if (in_array($namedformat, array(5, 6, 7, 9, 10, 11, 12, 13, 14, 15, 16, 17))) {
            if ($namedformat == 5 || $namedformat == 9 || $namedformat == 12 || $namedformat == 15) {
                $DT = $this->FormatDateTime(date('Y-m-d H:i:s'), 9);
            } elseif ($namedformat == 6 || $namedformat == 10 || $namedformat == 13 || $namedformat == 16) {
                $DT = $this->FormatDateTime(date('Y-m-d H:i:s'), 10);
            } else {
                $DT = $this->FormatDateTime(date('Y-m-d H:i:s'), 11);
            }
            return $DT;
        } else {
            return date('Y-m-d H:i:s');
        }
    }

// Format a timestamp, datetime, date or time field
// $namedformat:
// 0 - Default date format
// 1 - Long Date (with time)
// 2 - Short Date (without time)
// 3 - Long Time (hh:mm:ss AM/PM)
// 4 - Short Time (hh:mm:ss)
// 5 - Short Date (yyyy/mm/dd)
// 6 - Short Date (mm/dd/yyyy)
// 7 - Short Date (dd/mm/yyyy)
// 8 - Short Date (Default) + Short Time (if not 00:00:00)
// 9 - Short Date (yyyy/mm/dd) + Short Time (hh:mm:ss)
// 10 - Short Date (mm/dd/yyyy) + Short Time (hh:mm:ss)
// 11 - Short Date (dd/mm/yyyy) + Short Time (hh:mm:ss)
// 12 - Short Date - 2 digit year (yy/mm/dd)
// 13 - Short Date - 2 digit year (mm/dd/yy)
// 14 - Short Date - 2 digit year (dd/mm/yy)
// 15 - Short Date (yy/mm/dd) + Short Time (hh:mm:ss)
// 16 - Short Date (mm/dd/yyyy) + Short Time (hh:mm:ss)
// 17 - Short Date (dd/mm/yyyy) + Short Time (hh:mm:ss)
    function FormatDateTime($ts, $namedformat) {
        if ($namedformat == 0)
            $namedformat = $this->DATE_FORMAT_ID;
        if (is_numeric($ts)) { // Timestamp
            switch (strlen($ts)) {
                case 14:
                    $patt = '/(\d{4})(\d{2})(\d{2})(\d{2})(\d{2})(\d{2})/';
                    break;
                case 12:
                    $patt = '/(\d{2})(\d{2})(\d{2})(\d{2})(\d{2})(\d{2})/';
                    break;
                case 10:
                    $patt = '/(\d{2})(\d{2})(\d{2})(\d{2})(\d{2})/';
                    break;
                case 8:
                    $patt = '/(\d{4})(\d{2})(\d{2})/';
                    break;
                case 6:
                    $patt = '/(\d{2})(\d{2})(\d{2})/';
                    break;
                case 4:
                    $patt = '/(\d{2})(\d{2})/';
                    break;
                case 2:
                    $patt = '/(\d{2})/';
                    break;
                default:
                    return $ts;
            }
            if ((isset($patt)) && (preg_match($patt, $ts, $matches))) {
                $year = $matches[1];
                $month = @$matches[2];
                $day = @$matches[3];
                $hour = @$matches[4];
                $min = @$matches[5];
                $sec = @$matches[6];
            }
            if (($namedformat == 0) && (strlen($ts) < 10))
                $namedformat = 2;
        }
        elseif (is_string($ts)) {
            if (preg_match('/(\d{4})-(\d{2})-(\d{2}) (\d{2}):(\d{2}):(\d{2})/', $ts, $matches)) { // Datetime
                $year = $matches[1];
                $month = $matches[2];
                $day = $matches[3];
                $hour = $matches[4];
                $min = $matches[5];
                $sec = $matches[6];
            } elseif (preg_match('/(\d{4})-(\d{2})-(\d{2})/', $ts, $matches)) { // Date
                $year = $matches[1];
                $month = $matches[2];
                $day = $matches[3];
                if ($namedformat == 0)
                    $namedformat = 2;
            }
            elseif (preg_match('/(^|\s)(\d{2}):(\d{2}):(\d{2})/', $ts, $matches)) { // Time
                $hour = $matches[2];
                $min = $matches[3];
                $sec = $matches[4];
                if (($namedformat == 0) || ($namedformat == 1))
                    $namedformat = 3;
                if ($namedformat == 2)
                    $namedformat = 4;
            }
            else {
                return $ts;
            }
        } else {
            return $ts;
        }
        if (!isset($year))
            $year = 0; // Dummy value for times
        if (!isset($month))
            $month = 1;
        if (!isset($day))
            $day = 1;
        if (!isset($hour))
            $hour = 0;
        if (!isset($min))
            $min = 0;
        if (!isset($sec))
            $sec = 0;
        $uts = @mktime($hour, $min, $sec, $month, $day, $year);
        if ($uts < 0 || $uts == FALSE || // Failed to convert
                (intval($year) == 0 && intval($month) == 0 && intval($day) == 0)) {
            $year = substr_replace("0000", $year, -1 * strlen($year));
            $month = substr_replace("00", $month, -1 * strlen($month));
            $day = substr_replace("00", $day, -1 * strlen($day));
            $hour = substr_replace("00", $hour, -1 * strlen($hour));
            $min = substr_replace("00", $min, -1 * strlen($min));
            $sec = substr_replace("00", $sec, -1 * strlen($sec));
            if ($this->ContainsStr($this->DATE_FORMAT, "yyyy"))
                $DefDateFormat = str_replace("yyyy", $year, $this->DATE_FORMAT);
            elseif ($this->ContainsStr($this->DATE_FORMAT, "yy"))
                $DefDateFormat = str_replace("yy", substr(strval($year), -2), $this->DATE_FORMAT);
            $DefDateFormat = str_replace("mm", $month, $DefDateFormat);
            $DefDateFormat = str_replace("dd", $day, $DefDateFormat);
            switch ($namedformat) {

                //case 0: // Default
                case 1:
                    return $DefDateFormat . " " . $hour . $this->TIME_SEPARATOR . $min . $this->TIME_SEPARATOR . $sec;
                    break;

                //case 2: // Default
                case 3:
                    if (intval($hour) == 0) {
                        if ($min == 0 && $sec == 0)
                            return "12 " . "medianoche";
                        else
                            return "12" . $this->TIME_SEPARATOR . $min . $this->TIME_SEPARATOR . $sec . " " . "AM";
                    } elseif (intval($hour) > 0 && intval($hour) < 12) {
                        return $hour . $this->TIME_SEPARATOR . $min . $this->TIME_SEPARATOR . $sec . " " . "AM";
                    } elseif (intval($hour) == 12) {
                        if ($min == 0 && $sec == 0)
                            return "12 " . "Tarde";
                        else
                            return $hour . $this->TIME_SEPARATOR . $min . $this->TIME_SEPARATOR . $sec . " " . "PM";
                    } elseif (intval($hour) > 12 && intval($hour) <= 23) {
                        return (intval($hour) - 12) . $this->TIME_SEPARATOR . $min . $this->TIME_SEPARATOR . $sec . " " . "PM";
                    } else {
                        return $hour . $this->TIME_SEPARATOR . $min . $this->TIME_SEPARATOR . $sec;
                    }
                    break;
                case 4:
                    return $hour . $this->TIME_SEPARATOR . $min . $this->TIME_SEPARATOR . $sec;
                    break;
                case 5:
                    return $year . $this->DATE_SEPARATOR . $month . $this->DATE_SEPARATOR . $day;
                    break;
                case 6:
                    return $month . $this->DATE_SEPARATOR . $day . $this->DATE_SEPARATOR . $year;
                    break;
                case 7:
                    return $day . $this->DATE_SEPARATOR . $month . $this->DATE_SEPARATOR . $year;
                    break;
                case 8:
                    return $DefDateFormat . (($hour == 0 && $min == 0 && $sec == 0) ? "" : " " . $hour . $this->TIME_SEPARATOR . $min . $this->TIME_SEPARATOR . $sec);
                    break;
                case 9:
                    return $year . $this->DATE_SEPARATOR . $month . $this->DATE_SEPARATOR . $day . " " . $hour . $this->TIME_SEPARATOR . $min . $this->TIME_SEPARATOR . $sec;
                    break;
                case 10:
                    return $month . $this->DATE_SEPARATOR . $day . $this->DATE_SEPARATOR . $year . " " . $hour . $this->TIME_SEPARATOR . $min . $this->TIME_SEPARATOR . $sec;
                    break;
                case 11:
                    return $day . $this->DATE_SEPARATOR . $month . $this->DATE_SEPARATOR . $year . " " . $hour . $this->TIME_SEPARATOR . $min . $this->TIME_SEPARATOR . $sec;
                    break;
                case 12:
                    return substr($year, -2) . $this->DATE_SEPARATOR . $month . $this->DATE_SEPARATOR . $day;
                    break;
                case 13:
                    return $month . $this->DATE_SEPARATOR . $day . $this->DATE_SEPARATOR . substr($year, -2);
                    break;
                case 14:
                    return $day . $this->DATE_SEPARATOR . $month . $this->DATE_SEPARATOR . substr($year, -2);
                    break;
                default:
                    return $DefDateFormat;
                    break;
            }
        } else {
            if ($this->ContainsStr($this->DATE_FORMAT, "yyyy"))
                $DefDateFormat = str_replace("yyyy", $year, $this->DATE_FORMAT);
            elseif ($this->ContainsStr($this->DATE_FORMAT, "yy"))
                $DefDateFormat = str_replace("yy", substr(strval($year), -2), $this->DATE_FORMAT);
            $DefDateFormat = str_replace("mm", $month, $DefDateFormat);
            $DefDateFormat = str_replace("dd", $day, $DefDateFormat);
            switch ($namedformat) {

                // case 0: // Default
                case 1:
                    return strftime($DefDateFormat . " %H" . $this->TIME_SEPARATOR . "%M" . $this->TIME_SEPARATOR . "%S", $uts);
                    break;

                // case 2: // Default
                case 3:
                    if (intval($hour) == 0) {
                        if ($min == 0 && $sec == 0)
                            return "12 " . "de la medianoche";
                        else
                            return strftime("%I" . $this->TIME_SEPARATOR . "%M" . $this->TIME_SEPARATOR . "%S", $uts) . " " . "AM";
                    } elseif (intval($hour) > 0 && intval($hour) < 12) {
                        return strftime("%I" . $this->TIME_SEPARATOR . "%M" . $this->TIME_SEPARATOR . "%S", $uts) . " " . "AM";
                    } elseif (intval($hour) == 12) {
                        if ($min == 0 && $sec == 0)
                            return "12 " . "de la tarde";
                        else
                            return strftime("%I" . $this->TIME_SEPARATOR . "%M" . $this->TIME_SEPARATOR . "%S", $uts) . " " . "PM";
                    } elseif (intval($hour) > 12 && intval($hour) <= 23) {
                        return strftime("%I" . $this->TIME_SEPARATOR . "%M" . $this->TIME_SEPARATOR . "%S", $uts) . " " . "PM";
                    } else {
                        return strftime("%I" . $this->TIME_SEPARATOR . "%M" . $this->TIME_SEPARATOR . "%S %p", $uts);
                    }
                    break;
                case 4:
                    return strftime("%H" . $this->TIME_SEPARATOR . "%M" . $this->TIME_SEPARATOR . "%S", $uts);
                    break;
                case 5:
                    return strftime("%Y" . $this->DATE_SEPARATOR . "%m" . $this->DATE_SEPARATOR . "%d", $uts);
                    break;
                case 6:
                    return strftime("%m" . $this->DATE_SEPARATOR . "%d" . $this->DATE_SEPARATOR . "%Y", $uts);
                    break;
                case 7:
                    return strftime("%d" . $this->DATE_SEPARATOR . "%m" . $this->DATE_SEPARATOR . "%Y", $uts);
                    break;
                case 8:
                    return strftime($DefDateFormat . (($hour == 0 && $min == 0 && $sec == 0) ? "" : " %H" . $this->TIME_SEPARATOR . "%M" . $this->TIME_SEPARATOR . "%S"), $uts);
                    break;
                case 9:
                    return strftime("%Y" . $this->DATE_SEPARATOR . "%m" . $this->DATE_SEPARATOR . "%d %H" . $this->TIME_SEPARATOR . "%M" . $this->TIME_SEPARATOR . "%S", $uts);
                    break;
                case 10:
                    return strftime("%m" . $this->DATE_SEPARATOR . "%d" . $this->DATE_SEPARATOR . "%Y %H" . $this->TIME_SEPARATOR . "%M" . $this->TIME_SEPARATOR . "%S", $uts);
                    break;
                case 11:
                    return strftime("%d" . $this->DATE_SEPARATOR . "%m" . $this->DATE_SEPARATOR . "%Y %H" . $this->TIME_SEPARATOR . "%M" . $this->TIME_SEPARATOR . "%S", $uts);
                    break;
                case 12:
                    return strftime("%y" . $this->DATE_SEPARATOR . "%m" . $this->DATE_SEPARATOR . "%d", $uts);
                    break;
                case 13:
                    return strftime("%m" . $this->DATE_SEPARATOR . "%d" . $this->DATE_SEPARATOR . "%y", $uts);
                    break;
                case 14:
                    return strftime("%d" . $this->DATE_SEPARATOR . "%m" . $this->DATE_SEPARATOR . "%y", $uts);
                    break;
                case 15:
                    return strftime("%y" . $this->DATE_SEPARATOR . "%m" . $this->DATE_SEPARATOR . "%d %H" . $this->TIME_SEPARATOR . "%M" . $this->TIME_SEPARATOR . "%S", $uts);
                    break;
                case 16:
                    return strftime("%m" . $this->DATE_SEPARATOR . "%d" . $this->DATE_SEPARATOR . "%y %H" . $this->TIME_SEPARATOR . "%M" . $this->TIME_SEPARATOR . "%S", $uts);
                    break;
                case 17:
                    return strftime("%d" . $this->DATE_SEPARATOR . "%m" . $this->DATE_SEPARATOR . "%y %H" . $this->TIME_SEPARATOR . "%M" . $this->TIME_SEPARATOR . "%S", $uts);
                    break;
                default:
                    return strftime($DefDateFormat, $uts);
                    break;
            }
        }
    }

// Unformat date time based on format type
    function UnFormatDateTime($dt, $namedformat) {
        if (preg_match('/^([0-9]{4})-([0][1-9]|[1][0-2])-([0][1-9]|[1|2][0-9]|[3][0|1])( (0[0-9]|1[0-9]|2[0-3]):([0-5][0-9]):([0-5][0-9]))?$/', $dt))
            return $dt;
        $dt = trim($dt);
        while (strpos($dt, "  ") !== FALSE)
            $dt = str_replace("  ", " ", $dt);
        $arDateTime = explode(" ", $dt);
        if (count($arDateTime) == 0)
            return $dt;
        if ($namedformat == 0 || $namedformat == 1 || $namedformat == 2 || $namedformat == 8)
            $namedformat = $this->DATE_FORMAT_ID;
        $arDatePt = explode($this->DATE_SEPARATOR, $arDateTime[0]);
        if (count($arDatePt) == 3) {
            switch ($namedformat) {
                case 5:
                case 9: //yyyymmdd
                    if ($this->ValidateDate($arDateTime[0])) {
                        list($year, $month, $day) = $arDatePt;
                        break;
                    } else {
                        return $dt;
                    }
                case 6:
                case 10: //mmddyyyy
                    if ($this->ValidateUSDate($arDateTime[0])) {
                        list($month, $day, $year) = $arDatePt;
                        break;
                    } else {
                        return $dt;
                    }
                case 7:
                case 11: //ddmmyyyy
                    if ($this->ValidateEuroDate($arDateTime[0])) {
                        list($day, $month, $year) = $arDatePt;
                        break;
                    } else {
                        return $dt;
                    }
                case 12:
                case 15: //yymmdd
                    if ($this->ValidateShortDate($arDateTime[0])) {
                        list($year, $month, $day) = $arDatePt;
                        $year = $this->UnformatYear($year);
                        break;
                    } else {
                        return $dt;
                    }
                case 13:
                case 16: //mmddyy
                    if ($this->ValidateShortUSDate($arDateTime[0])) {
                        list($month, $day, $year) = $arDatePt;
                        $year = $this->UnformatYear($year);
                        break;
                    } else {
                        return $dt;
                    }
                case 14:
                case 17: //ddmmyy
                    if ($this->ValidateShortEuroDate($arDateTime[0])) {
                        list($day, $month, $year) = $arDatePt;
                        $year = $this->UnformatYear($year);
                        break;
                    } else {
                        return $dt;
                    }
                default:
                    return $dt;
            }
            return $year . "-" . str_pad($month, 2, "0", STR_PAD_LEFT) . "-" .
                    str_pad($day, 2, "0", STR_PAD_LEFT) .
                    ((count($arDateTime) > 1) ? " " . str_replace($this->TIME_SEPARATOR, ":", $arDateTime[1]) : "");
        } else {
            if ($namedformat == 3 || $namedformat == 4) {
                $dt = str_replace($this->TIME_SEPARATOR, ":", $dt);
            }
            return $dt;
        }
    }

// Check Date format (yy/mm/dd)
    function ValidateShortDate($value) {
        return $this->ValidateDateEx($value, "stdshort", $this->DATE_SEPARATOR);
    }

// Check US Date format (mm/dd/yyyy)
    function ValidateUSDate($value) {
        return $this->ValidateDateEx($value, "us", $this->DATE_SEPARATOR);
    }

// Check US Date format (mm/dd/yy)
    function ValidateShortUSDate($value) {
        return $this->ValidateDateEx($value, "usshort", $this->DATE_SEPARATOR);
    }

// Contains a substring (case-sensitive)
    function ContainsStr($haystack, $needle, $offset = 0) {
        return strpos($haystack, $needle, $offset) !== FALSE;
    }
    
/**
 *  Calcula la edad en dias, meses y anios 
 * Recibe como parametros la fecha en formato aaaa-mm-dd y devuelve un string 
 * donde se plasma como numero entero la edad y se concatena una d para dias 
 * en caso de que la edad sea menor a un mes y se concatena una m para meses 
 * en caso de que la edad sea menor a 1 anio
 * @param type $fecha_nacimiento
 * @return array con los siguientes valores edad: Edad en numero entero, rango: 
 * A,M,D dependiendo del rango de edad, edadtexto: La edad con formato X DIAS, X Meses, X ANIOS segun corresponda, 
 * edadtextodesglose: La edad con formato X ANIOS, X Meses, X dias segun corresponda
 */
function calculaEdad($fecha_nacimiento, $fecha_en_formato_euro = FALSE, $fecha_compara = '') {
    $session = \Config\Services::session();
    $config = config('AuthConfig');
    $client = \Config\Services::curlrequest();

    $edad = array();
    //$this->CI->load->model("Mfechas");
    
    //$concatena = "";
    if (!empty($fecha_nacimiento)) {

        // Calculo de la Edad del Paciente
        if(!$fecha_en_formato_euro){
            $fecha_nacimiento = date('d/m/Y',strtotime($fecha_nacimiento));
        }
        if (empty($fecha_compara)){
            //$fecha_control = $this->CI->Mfechas->getFechaActualEuro();
            //Obtenemos del rest la fecha
            $response = $client->request('get', $config->base_url_urgencias.'con_catalogos/fecha_sql' , [
                'auth' => [$config->auth_user, $config->auth_pass,'basic'],
                'headers' => [$config->auth_token => $config->auth_token_pass]
            ]);
            $fecha= json_decode($response->getBody());
            $fecha_control = $fecha->respuesta->fecha_actual;
            //die($fecha_control);
        }
        else
        {
             $fecha_control = $this->FormatDateTime($fecha_compara , 7);             
        }
        $tiempo = $this->tiempo_transcurrido($fecha_nacimiento, $fecha_control);        
        //variables para leyendas
        $lanio = "";
        $lmes = "";
        $ldia = "";
        if ($tiempo[0] == 1) {
            $lanio = "año";
        } else {
            $lanio = "años";
        }
        if ($tiempo[1] == 1) {
            $lmes = "mes";
        } else {
            $lmes = "meses";
        }
        if ($tiempo[2] == 1) {
            $ldia = "dia";
        } else {
            $ldia = "dias";
        }
        if ($tiempo[0] < 1) {

            // El paciente es menor de 1 anio
            if ($tiempo[1] < 1) {

                // El paciente es menor de 1 mes
                //$concatena = 'D'; // D para dias

                $edad['edad'] = $tiempo[2];
                $edad['edad_id_tipo'] = 3;
                $edad['rango'] = 'D';
                $edad['edadtexto'] = $tiempo[2] . " " . $ldia;
                $edad['edadtextodesglose'] = $tiempo[2] . " " . $ldia;
                $edad['edad_dgis_a_dias'] = $tiempo[2];
                $edad['anios'] = $tiempo[0];
                $edad['meses'] = $tiempo[1];
                $edad['dias'] = $tiempo[2];
            } else {

                // El paciente es mayor a 1 mes
                //$concatena = 'm'; // M para meses
                //$edad = $tiempo[1];

                $edad['edad'] = $tiempo[1];
                $edad['edad_id_tipo'] = 4;
                $edad['rango'] = 'M';
                $edad['edadtexto'] = $tiempo[1] . " " . $lmes;
                $edad['edadtextodesglose'] = $tiempo[1] . " " . $lmes . ", " . $tiempo[2] . " $ldia";
                $edad['edad_dgis_a_dias'] = $tiempo[1] * 30;
                $edad['anios'] = $tiempo[0];
                $edad['meses'] = $tiempo[1];
                $edad['dias'] = $tiempo[2];
            }
        } else {

            // El paciente es mayor a 1 anio
            // para anios no se concatena nada
            //$edad = $tiempo[0];

            $edad['edad'] = $tiempo[0];
            $edad['edad_id_tipo'] = 5;
            $edad['rango'] = 'A';
            $edad['edadtexto'] = $tiempo[0] . " $lanio";
            $edad['edadtextodesglose'] = $tiempo[0] . " $lanio, " . $tiempo[1] . " $lmes, " . $tiempo[2] . " $ldia";
            $edad['edad_dgis_a_dias'] = $tiempo[0] * 365;
            $edad['anios'] = $tiempo[0];
            $edad['meses'] = $tiempo[1];
            $edad['dias'] = $tiempo[2];
        }
    }
    //die(print_r($edad));
    return $edad;
}

/**
 * Determina el tiempo transcurrido entre 2 fechas
 * @param type $fecha_nacimiento Fecha de nacimiento de una persona. (dd/mm/aaaa)
 * @param type $fecha_control Fecha actual o fecha a consultar. (dd/mm/aaaa)
 * @example tiempo_transcurrido('22/06/1977', '04/05/2009');
 * @return int
 */
function tiempo_transcurrido($fecha_nacimiento, $fecha_control) {
    $fecha_actual = $fecha_control;
    if (!strlen($fecha_actual)) {
        $fecha_actual = date('d/m/Y');
    }

    // separamos en partes las fechas 
    $array_nacimiento = explode("/", $fecha_nacimiento);
    $array_actual = explode("/", $fecha_actual);
    $anos = $array_actual[2] - $array_nacimiento[2]; // calculamos anios 
    $meses = $array_actual[1] - $array_nacimiento[1]; // calculamos meses 
    $dias = $array_actual[0] - $array_nacimiento[0]; // calculamos dias 
    //ajuste de posible negativo en $dias 
    if ($dias < 0) {
        --$meses;

        //ahora hay que sumar a $dias los dias que tiene el mes anterior de la fecha actual 
        switch ($array_actual[1]) {
            case 1:
                $dias_mes_anterior = 31;
                break;
            case 2:
                $dias_mes_anterior = 31;
                break;
            case 3:
                if ($this->bisiesto($array_actual[2])) {
                    $dias_mes_anterior = 29;
                    break;
                } else {
                    $dias_mes_anterior = 28;
                    break;
                }
            case 4:
                $dias_mes_anterior = 31;
                break;
            case 5:
                $dias_mes_anterior = 30;
                break;
            case 6:
                $dias_mes_anterior = 31;
                break;
            case 7:
                $dias_mes_anterior = 30;
                break;
            case 8:
                $dias_mes_anterior = 31;
                break;
            case 9:
                $dias_mes_anterior = 31;
                break;
            case 10:
                $dias_mes_anterior = 30;
                break;
            case 11: 
                $dias_mes_anterior = 31;
                break;
            case 12:
                $dias_mes_anterior = 30;
                break;
        }
        $dias = $dias + $dias_mes_anterior;
        if ($dias < 0) {
            --$meses;
            if ($dias == -1) {
                $dias = 30;
            }
            if ($dias == -2) {
                $dias = 29;
            }
        }
    }

    //ajuste de posible negativo en $meses 
    if ($meses < 0) {
        --$anos;
        $meses = $meses + 12;
    }
    $tiempo[0] = $anos;
    $tiempo[1] = $meses;
    $tiempo[2] = $dias;
    return $tiempo;
}

/**
 * Funcion que determina si un anio es bisiesto o no
 * @param type $anio_actual (aaaa)
 * @return boolean true si el anio es bisiesto, false en caso contrario
 */
function bisiesto($anio_actual) {
    $bisiesto = false;

    //probamos si el mes de febrero del anio actual tiene 29 dias 
    if (checkdate(2, 29, $anio_actual)) {
        $bisiesto = true;
    }
    return $bisiesto;
}
function actualizaEdadConsulta($id_paciente){
    $ci = & get_instance();
    $ci->load->library('Fechas');
    $ci->load->library('Bitacora');
    $ci->load->model('Mpacientes');
    $ci->load->model('Mconexion');

        $where = "id_paciente = ".$id_paciente;        
        $datos_paciente = $ci->Mpacientes->obtienePacienteDatosGenerales("id_paciente =".$id_paciente);   
        $consulta = $ci->Mconexion->obtieneHojaDiariaTodo($where);

        foreach ($consulta as $con) {
            $fecha_nacimiento = $datos_paciente->pac_fecha_nacimiento;
            $fecha_consulta = $con->fecha_consulta;
            $resultado =$ci->fechas->calculaEdad($fecha_nacimiento, '', $fecha_consulta);
            
            if($con->hdce_pac_edad <> $resultado['edad'] || $con->hdce_pac_id_tipo_edad <> $resultado['edad_id_tipo'] || $fecha_nacimiento <> $con->hdce_pac_fecha_nacimiento){
                //echo "Actualiza =>".$con->hdce_pac_edad." =>". $con->hdce_pac_id_tipo_edad." =====>".$resultado['edad']."=>".$resultado['edad_id_tipo'];
                //$resultado =$this->fechas->actualizaEdadConsulta($id_consulta, $id_paciente, $resultado['edad'],$resultado['edad_id_tipo'],$fecha_nacimiento, $consulta->hdce_pac_edad, $consulta->hdce_pac_id_tipo_edad,$consulta->hdce_pac_fecha_nacimiento);
                $mi_bitacora = $ci->bitacora->GuardaBitacora("Hubo modificación en fecha de nacimiento en paciente con fecha nacimiento consulta", "hoja_diaria_consulta", "hdce_pac_edad,hdce_pac_id_tipo_edad,hdce_pac_fecha_nacimiento", $con->id_consulta,$con->hdce_pac_edad."- ".$con->hdce_pac_id_tipo_edad."- ".$con->hdce_pac_fecha_nacimiento, $resultado['edad']."- ".$resultado['edad_id_tipo']."- ".$fecha_nacimiento);
                $hoja_diaria = array(
                    'hdce_pac_edad' => $resultado['edad'],
                    'hdce_pac_id_tipo_edad' => $resultado['edad_id_tipo'],
                    'hdce_pac_fecha_nacimiento' => date('Y-m-d', strtotime($fecha_nacimiento)),
                    'fecha_ultima_actualizacion' => date('Y-m-d H:i:s')            
                );
                $where_consulta = "id_consulta = ".$con->id_consulta;            
                $inserta_hoja_diaria = $ci->Mconexion->ActualizaConsultaHojaDiaria($hoja_diaria, $where_consulta);
            }else{
               // echo "No actualiza";
            }
        }
            

    }

    function calculaedadMeses($fechanac = '', $fechacompara = '') {
        $edadmeses = -1;
        if (!empty($fechanac) && !empty($fechacompara) ) {
            $edadcalculada = $this->calculaEdad($fechanac, false, $fechacompara);
            $edadmeses = ($edadcalculada['anios'] * 12 ) + $edadcalculada['meses'];             
        }
        return $edadmeses;
    }
    
    /**
     * Extrae la fecha de nacimiento de una CURP
     * @param string $curp
     * @return string La fecha en formato yyyy-mm-dd en caso de error, retorna cadena vacia
     */
    function extraeFechaNacDeCURP($curp) {
        $fechaNac = '';
        if (empty($curp) || strlen($curp) <> 18) {
            return $fechaNac;
        }
        $fechaCurp = substr($curp, 4, 6);
        $digitoCenturia = substr($curp, 16, 1);
        if ($fechaCurp && is_numeric($fechaCurp)) {
            $anio = substr($fechaCurp, 0, 2);
            $mes = substr($fechaCurp, 2, 2);
            $dia = substr($fechaCurp, 4, 2);
            if (is_numeric($digitoCenturia)) {
                $anio = "19" . $anio;
            } else {
                $anio = "20" . $anio;
            }
            $fechaNac = $anio . "-" . $mes . "-" . $dia;
        }
        return $fechaNac;
    }

    /**
     * Formatea la fecha en formato español de mexico
     * por default sale en 0 => Miercoles 15 de Enero de 2020
     * estilos programados:
     * 0=> Miercoles 15 de Enero de 2020
     * 1=> 15 Enero 2020
     * 2=> 15 Ene 2020
     */
    function fechaESMX ($fecha, $estilo = 0) 
    {
        if (empty($fecha)) return "";
        $fecha = substr($fecha, 0, 10);
        $numeroDia = date('d', strtotime($fecha));
        $dia = date('l', strtotime($fecha));
        $mes = date('F', strtotime($fecha));
        $anio = date('Y', strtotime($fecha));
        $dias_ES = array("Lunes", "Martes", "Miércoles", "Jueves", "Viernes", "Sábado", "Domingo");
        $dias_EN = array("Monday", "Tuesday", "Wednesday", "Thursday", "Friday", "Saturday", "Sunday");
        $nombredia = str_replace($dias_EN, $dias_ES, $dia);
        $meses_ES = array("Enero", "Febrero", "Marzo", "Abril", "Mayo", "Junio", "Julio", "Agosto", "Septiembre", "Octubre", "Noviembre", "Diciembre");
        $meses_EN = array("January", "February", "March", "April", "May", "June", "July", "August", "September", "October", "November", "December");
        $nombreMes = str_replace($meses_EN, $meses_ES, $mes);
        switch($estilo){
                case 0:	$fecha_esmx = $nombredia." ".$numeroDia." de ".$nombreMes." de ".$anio;
                        break;
                case 1:	$fecha_esmx = $numeroDia." ".$nombreMes." ".$anio;
                        break;		
                case 2: $meses_es_c = array("Ene", "Feb", "Mar", "Abr", "May", "Jun", "Jul", "Ago", "Sep", "Oct", "Nov", "Dic");
                        $meses_es = array("Enero", "Febrero", "Marzo", "Abril", "Mayo", "Junio", "Julio", "Agosto", "Septiembre", "Octubre", "Noviembre", "Diciembre");
                        $nombremes = str_replace($meses_es, $meses_es_c, $nombreMes);
                        $fecha_esmx = $numeroDia." ".$nombremes." ".$anio;
                    break;	
        }
        return $fecha_esmx;
    }   

}
