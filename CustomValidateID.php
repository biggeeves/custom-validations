<?php

namespace DCC\CustomValidateID;

use Exception;
use ExternalModules\AbstractExternalModule;
use phpDocumentor\Reflection\Types\Boolean;
use REDCap;


/**
 * Class CustomValidateID
 *
 * @package DCC\CustomValidateID
 */
class CustomValidateID extends AbstractExternalModule
{

    /**
     * @var
     */
    private $validation_formats;

    /** @var integer $is_auto_inc
     * If auto incrementing IDs then disable validation.
     */
    private $is_auto_inc;

    /**
     * @var int $debug If =1 display debug info.
     */
    private $debug;

    /** @var  string $record_id_field
     *  Get the project's Record ID field
     */
    private $record_id_field;

    /** @var boolean $isCustomValidationEnabled Boolean
     */
    private $isCustomValidationEnabled;

    /** @var integer $char_limit */
    private $char_limit;

    /** @var string $validateJSURL URL to javascript file */
    private $validateJSURL;

    /** @var integer $require_submit User set.
     *  Require the submit button instead of the default onblur event
     * passed to javascript via created js.
     */
    private $require_submit;


    /** @var string $valid_message User Set.   Message to display when input is valid.
     * passed to javascript via created js.
     */
    private $valid_message;

    /** @var string $invalid_message User Set.   Message to display when input is invalid.
     * passed to javascript via created js.
     */
    private $invalid_message;

    /** @var string $validation_sanitized_regex User Set.   Regular expression to validate input
     * passed to javascript via created js.
     */
    private $validation_sanitized_regex;

    /** @var boolean $has_regex True if regex is present.  False if not present.
     * passed to javascript via created js.
     */
    private $has_regex;

    /** @var string $validation_chars_custom Replacement values for the string, $, character in the format specification
     */
    private $validation_chars_custom;


    /** @var array $record_id_meta Meta data about the record ID.
     *
     */
    private $record_id_meta;

    /**
     * @var string $js JavaScript to include in the page.  Responsible for creating variable values.
     */
    private $js;

    /**
     * @var string $record_id_field_type REDCap defined field type: Integer, Dates, String, etc.
     */
    private $record_id_field_type;

    /**
     * @var array $all_validation_formats All user specified formats
     */
    private $all_validation_formats;

    /**
     * @var string $validation_format_begin Format begins with
     */
    private $validation_format_begin;

    /**
     * @var string $validation_format_end Format ends with
     */
    private $validation_format_end;

    /**
     * @var string $validation_exact_num_chars Exact numbers of characters the ID must have.
     */
    private $validation_exact_num_chars;

    /** @var integer $testValue when in debug mode, pass a test value for immediate testing */
    private $testValue;

    /** @var integer $validation_chars_min number of characters the input must have */
    private $validation_chars_min;

    /**
     * CustomValidateID constructor.
     */
    public function __construct()
    {
        parent::__construct();
        $this->debug = 0;
        $this->isDebug();
    }

    /**
     * @param int $project_id
     * @param string|null $instrument
     * @param int|null $event_id
     */
    public function redcap_add_edit_records_page(int $project_id, string $instrument = null, int $event_id = null)
    {
        print"";
    }

    /**
     * @param int $project_id
     */
    function redcap_every_page_top(int $project_id)
    {
        // Only fire on pages where new IDs are created.
        if (!$this->isPageCreateId()) {
            return;
        }

        /** initialize variables */
        $this->initialize_vars();

        /** echo debug info */
        if ($this->debug) echo $this->echo_debug();

        // under certain conditions validation should not run.
        if (!$this->run_validation()) {
            return;
        }

        $this->js = $this->create_js();

        print $this->js;
    }


    /**
     * Should validation exit?
     * returns false if validation should not continue.  True if should continue
     *
     */
    function run_validation()
    {
        /** @var string $project_id */
        global $project_id;
        if (!$this->isCustomValidationEnabled) {
            return false;
        }
        if ($this->is_auto_inc) {
            return false;
        }
        if (!$project_id) {
            return false;
        }
        return true;
    }

    /** set the character limit
     * returns integer or null  $limit
     */
    private function set_char_limit()
    {
        $limit = null;
        $meta = explode('@', $this->record_id_meta["field_annotation"]);
        foreach ($meta as $value) {
            if (substr($value, 0, 10) === 'CHARLIMIT=') {
                $limit = intval(substr($value, 10));
            }
        }
        return $limit;
    }

    /**
     * @return string
     */
    private function create_js()
    {

        /** Wrap in jQuery onLoad.  All elements will be displayed on page */
        $jquery_onload_open = "$(window).on('load', function() {" . PHP_EOL;
        $jquery_onload_close = "});" . PHP_EOL;

        /** echo debug info */
        $debug_js = 'validation.debug = ';
        $debugStart = '';
        if ($this->debug) {
            /** Debug text, beginning, middle and end.  Feel free to customize. */
            $debugStart = 'console.log("Custom Validation Enabled");' . PHP_EOL;
            $debug_js .= '1;';
        } else {
            $debug_js .= '0;';
        }
        $debug_js .= PHP_EOL;

        $testValue_js = 'validation.testValue = "' . str_replace('"', '', $this->testValue) . '";' . PHP_EOL;

        $submitJS = "validation.requireSubmit =";
        if ($this->require_submit) {
            $submitJS .= "1";
        } else {
            $submitJS .= "0";
        }
        $submitJS .= ";";

        $valid_message = "validation.validMessage = ";
        if ($this->valid_message) {
            $valid_message .= '"' . htmlspecialchars($this->valid_message) . '"';
        } else {
            $valid_message .= '""';
        }
        $valid_message .= ';';

        $invalid_message = "validation.invalidMessage = ";
        if ($this->invalid_message) {
            $invalid_message .= '"' . htmlspecialchars($this->invalid_message) . '"';
        } else {
            $invalid_message .= '""';
        }
        $invalid_message .= ';';

        if ($this->hasSettings($this->validation_formats)) {
            $json_validation_formats = 'validation.formats = ' . json_encode($this->validation_formats);
        } else {
            $json_validation_formats = 'validation.formats = null';
        }
        $json_validation_formats .= ';';

        $json_validation_regex = 'validation.regex = ';
        if ($this->has_regex) {
            $json_validation_regex .= 'new RegExp("' . $this->validation_regex_escaped . '")';
        } else {
            $json_validation_regex .= 'null';
        }
        $json_validation_regex .= ';';

        $char_limit = 'validation.charLimit = ';
        if ($this->char_limit) {
            $char_limit .= $this->char_limit;
        } else {
            $char_limit .= 'null';
        }
        $char_limit .= ';';

        $char_min = 'validation.charMin = ';
        if ($this->validation_chars_min) {
            $char_min .= $this->validation_chars_min;
        } else {
            $char_min .= 'null';
        }
        $char_min .= ';';

        $char_custom = 'validation.charCustom = ';
        if ($this->validation_chars_custom) {
            $char_custom .= '"' . $this->validation_chars_custom . '"';
        } else {
            $char_custom .= 'null';
        }
        $char_custom .= ';';

        $char_exact = 'validation.charExactNum = ';
        if ($this->validation_exact_num_chars) {
            $char_exact .= $this->validation_exact_num_chars;
        } else {
            $char_exact .= 'null';
        }
        $char_exact .= ';';

        $beginsWith = 'validation.beginsWithFormat = ';
        if ($this->validation_format_begin) {
            // $beginsWith .= '"' . $this->validation_format_begin . '"' ;
            $beginsWith .= json_encode($this->all_validation_format_begin);
        } else {
            $beginsWith .= 'null';
        }
        $beginsWith .= ';';

        $endsWith = 'validation.endsWith = ';
        if ($this->validation_format_end) {
//            $endsWith .= '"' . $this->validation_format_end . '"' ;
            $endsWith .= json_encode($this->all_validation_format_end);
        } else {
            $endsWith .= 'null';
        }
        $endsWith .= ';';

        /** Prepare JavaScript for output. */

        $js = ' <script type="text/javascript">' . PHP_EOL .
            'validation = {};' . PHP_EOL .
            $jquery_onload_open .
            $debugStart .
            $debug_js .
            $testValue_js .
            $submitJS . PHP_EOL .
            $char_limit . PHP_EOL .
            $char_min . PHP_EOL .
            $beginsWith . PHP_EOL .
            $endsWith . PHP_EOL .
            $char_exact . PHP_EOL .
            $char_custom . PHP_EOL .
            $json_validation_formats . PHP_EOL .
            $json_validation_regex . PHP_EOL .
            $valid_message . PHP_EOL .
            $invalid_message . PHP_EOL .
            $jquery_onload_close .
            ' </script > ' .
            '<script  defer type="text/javascript" src="' . $this->validateJSURL .
            '"></script>' . PHP_EOL;

        return $js;

    }

    /**
     * @throws Exception
     */
    private function initialize_vars()
    {
        global $is_auto_inc;
        global $project_id;
        $this->is_auto_inc = $is_auto_inc;
        $this->record_id_field = REDCap::getRecordIdField();
        $this->record_id_field_type = REDCap::getFieldType($this->record_id_field);
        $this->record_id_meta = array_shift(
            REDCap::getDataDictionary($project_id, 'array', false, $this->record_id_field)
        );
        /** if Character Limit is being used, get the character limit.  DK how it will play out yet. */
        $this->char_limit = $this->set_char_limit();

        /** Get if validation is enabled */
        $this->isCustomValidationEnabled = AbstractExternalModule::getProjectSetting('validation_enabled', $project_id);

        /** Require the submit button */
        $this->require_submit = AbstractExternalModule::getProjectSetting('validation_require_submit', $project_id);

        /** Get the validation format */
        $this->all_validation_formats = AbstractExternalModule::getProjectSetting('validation_format', $project_id);

        /** remove null values (when a user has opened a repeating variable, but added no value */
        if (!empty($this->all_validation_formats)) {
            $this->validation_formats = array_filter($this->all_validation_formats);
        } else {
            $this->validation_formats = null;
        }

        /** Get regular expression */
        $validation_regex = AbstractExternalModule::getProjectSetting('validation_regex', $project_id);

        $this->validation_sanitized_regex = $this->sanitizeRegex($validation_regex);
        $this->validation_regex_escaped = str_replace('\\', '\\\\', $this->validation_sanitized_regex);

        /** Get minimum number of characters*/
        $this->validation_chars_min = intval(
            AbstractExternalModule::getProjectSetting('validation_chars_min', $project_id));

        /** Get begins with characters*/
        $this->validation_format_begin = AbstractExternalModule::getProjectSetting('validation_format_begin', $project_id);
        $this->all_validation_format_begin = str_split($this->validation_format_begin, strlen($this->validation_format_begin));

        /** Get ends with characters*/
        $this->validation_format_end = AbstractExternalModule::getProjectSetting('validation_format_end', $project_id);
        $this->all_validation_format_end = str_split($this->validation_format_end, strlen($this->validation_format_end));


        if ($this->validation_chars_min == 0) {
            $this->validation_chars_min = null;
        }

        /** Get exact number of characters*/
        $this->validation_exact_num_chars = intval(
            AbstractExternalModule::getProjectSetting('validation_exact_num_chars', $project_id));

        if ($this->validation_exact_num_chars == 0) {
            $this->validation_exact_num_chars = null;
        }

        $this->validation_chars_custom = AbstractExternalModule::getProjectSetting('validation_chars_custom', $project_id);

        /** text to display when the id is invalid*/
        $this->invalid_message = AbstractExternalModule::getProjectSetting('validation_invalid_message', $project_id);

        /** text to display when the id is valid*/
        $this->valid_message = AbstractExternalModule::getProjectSetting('validation_valid_message', $project_id);

        $this->has_regex = false;

        if ($this->validation_sanitized_regex) {
            $this->has_regex = true;
        }

        $this->setTestValue();

        $this->validateJSURL = $this->getUrl("js/validateId.js");
    }

    // allow the parameter to override the local debug setting in the file.


    /** earlier versions of REDCap return an array instead of null for empty repeatable settings.
     *  Return true if there is a setting or a non-empty array.
     * @param array $setting from AbstractExternalModule::getProjectSetting(
     * @return bool
     */
    private function hasSettings($setting = null)
    {
        $empty_array = [0 => null];
        if (empty($setting) || is_null($setting)) {
            return false;
        } else if ($setting === $empty_array) {
            return false;
        }
        return true;
    }

    /** Display relative information for debugging. */
    private function echo_debug()
    {
        global $project_id;
        $debug_info = '<div class="yellow debug_info">' .
            "<p>Debugger is on.</p>";

        if ($this->testValue) $debug_info .= '<p>Test value: ' . $this->testValue . ' </p>';

        if ($this->isCustomValidationEnabled) {
            $debug_info .= '<p>Validation enabled.</p>';
        } else {
            $debug_info .= '<p>Custom Validation is not enabled.  Nothing should happen.</p>';
        }


        if ($this->hasSettings($this->validation_formats)) {
            $debug_info .= "<p>Validation format(s) is<br>";
            foreach ($this->validation_formats as $format) {
                $debug_info .= $format . "<br>";
            }
            $debug_info .= '</p>' . PHP_EOL;
        } else {
            $debug_info .= "No validation formats";
        }

        $debug_info .= "<p>Character limit: $this->char_limit </p>" . PHP_EOL .
            "<p>Minimum number of characters: $this->validation_chars_min </p>" . PHP_EOL .
            "<p>Exact number of characters: $this->validation_exact_num_chars </p>" . PHP_EOL .
            "<p>Customized valid characters: $this->validation_chars_custom </p>" . PHP_EOL;

        $debug_info .= "<p>Regular Expression: " .
            ($this->validation_sanitized_regex ? htmlspecialchars($this->validation_sanitized_regex) : "Off") .
            "</p>";

        // Auto increment should be turned off or set to null.

        $debug_info .= '<p>Require submit:  ' .
            ($this->require_submit ? " On" : "Off") . "</p>";

        if (!$project_id) {
            $debug_info .= '<p>Missing Required Project ID.  Nothing should happen.  Exit</p>';
        }

        if ($this->valid_message) {
            $debug_info .= '<p>Valid Message: ' . htmlspecialchars($this->valid_message) . '</p>';
        }

        if ($this->validation_format_begin) {
            $debug_info .= '<p>Begins with: ' . htmlspecialchars($this->validation_format_begin) . '</p>';
        }

        if ($this->validation_format_end) {
            $debug_info .= '<p>Ends with: ' . htmlspecialchars($this->validation_format_end) . '</p>';
        }

        if ($this->invalid_message) {
            $debug_info .= '<p>Invalid Message: ' . htmlspecialchars($this->invalid_message) . '</p>';
        }

        $debug_info .= "<p>The Record ID field is $this->record_id_field </p>" .
            "<p>Field Type(s):  $this->record_id_field_type </p>";

        $debug_info .= '</div>';
        return $debug_info;
    }

    /**
     * Debugger override.  Getting the setting from URL and override setting in file.
     * sets $this->debug = 1 if it should be on.
     */
    private function isDebug()
    {
        if ($_GET["valdebug"] === "1") {
            $this->debug = 1;
        }
    }

    /**
     *
     */
    private function setTestValue()
    {
        // use the URL to set a test value
        $val = str_replace('"', '', trim(filter_var($_GET['testval'], FILTER_SANITIZE_STRING)));
        if ($val) {
            $this->testValue = $val;
        } else {
            $this->testValue = false;
        }
    }

    /**
     * @param $value
     * @return string|string[]|null
     */
    private function sanitizeRegex($value)
    {
        // Characters that are not allowed: "/';
        $notAllowed = ['/"/', "/'/", '/;/'];
        $sanitizedString = strip_tags($value);
        $sanitizedString = preg_replace($notAllowed, '', $sanitizedString);
        return $sanitizedString;
    }

    /**
     * @return bool  true=Page that new ID can be created.  false=page can not create a new ID.
     */
    private function isPageCreateId()
    {
        $x = basename(parse_url($_SERVER['REQUEST_URI'])['path']);

        if ($x != 'record_home.php' && $x != 'record_status_dashboard.php') {
            return false;
        } else {
            return true;
        }

    }
}