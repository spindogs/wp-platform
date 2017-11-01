<?php
namespace Platform;

use Platform\Setup;
use Platform\Filter;
use Platform\Html;

class Form {

    protected $uniquevar;
    protected $is_submitted;
    protected $has_success;
    protected $values = array();
    protected $fields = array();
    protected $hidden = array();
    protected $errors = array();
    protected $inputs = array();
    protected $html = array();

    public $require_all = true;
    public $action;
    public $method = 'post';
    public $form_id;
    public $form_class;
    public $error_class = 'error';
    public $error_wrap = 'error_feedback';
    public $field_wrap = 'field_wrap';
    public $label_wrap = 'label_wrap';
    public $input_wrap = 'input_wrap';
    public $submit_wrap = 'submit_wrap';
    public $required_wrap = 'required';
    public $sublabel_wrap = 'sublabel_wrap';
    public $helper_wrap = 'helper_wrap';
    public $keep_gets = true;
    public $placeholder_all = false;
    public $placeholders = array();
    public $labels = array();
    public $sublabels = array();
    public $helpers = array();
    public $fragment;

    /**
     * @return mixed
     */
    public function __get($name)
    {
        return $this->{$name};
    }

    /**
     * @param string $uniquevar
     * @return void
     */
    public function __construct($uniquevar = 'DefaultForm')
    {
        $this->action = self::getRequestPath();
        $this->uniquevar = $uniquevar;
        $this->form_id = $uniquevar;
        $this->hidden($this->uniquevar, 1);

        unset($this->values[$this->uniquevar]);

        if (isset($_REQUEST[$this->uniquevar]) && $_REQUEST[$this->uniquevar] == 1) {
            $this->is_submitted = true;
            $this->has_success = true; //this gets set false if we hit an error
        }

        if (function_exists('csrf_token')) {
            $csrf_token = csrf_token();
            $this->hidden('_token', $csrf_token);
        }
    }

    /**
     * @param string $name
     * @return array
     */
    public function getField($name)
    {
        if (isset($this->fields[$name])) {
            return $this->fields[$name];
        } else {
            return false;
        }
    }

    /**
     * @param string $field
     * @return mixed
     */
    public function getValue($field)
    {
        return $this->values[$field];
    }

    /**
     * @param string $field
     * @param string $value
     * @return void
     */
    public function setValue($field, $value)
    {
        //TODO $value = Html::purify($value);

        if (is_scalar($value)) {
            $value = stripslashes($value);
        }

        $this->values[$field] = $value;
    }

    /**
     * @param array $params
     * @return void
     */
    public function addField($name, $params)
    {
        $defaults = array(
            'type' => '',
            'label' => '',
            'options' => array(),
            'initial' => '',
            'required' => null,
            'attrs' => array()
        );

        $params = array_merge($defaults, $params);

        if (isset($params['name'])) {
            unset($params['name']);
        }

        $this->fields[$name] = $params;
    }

    /**
     * @param string $name
     * @param array $params
     * @return void
     */
    protected function addInput($name, $params)
    {
        $initial = $params['initial'];

        if (!$this->submitted()) {
            $value = $initial;
        } elseif (isset($_REQUEST[$name])) {
            $value = $_REQUEST[$name];
        } else {
            $value = '';
        }

        $this->addField($name, $params);
        $this->setValue($name, $value);

        $type = $params['type'];
        $label = $params['label'];
        $required = $this->isRequired($name);
        $attrs = $params['attrs'];
        $attrs = $this->filterAttrs($attrs, $name);

        if ($this->submitted()) {

            if ($required && $value == '') {
                $this->error($label.' not provided', $name);
            } elseif ($type == 'text' && isset($attrs['limit']) && strlen($value) > $attrs['limit']) {
                $this->error('Cannot enter more than '.$attrs['limit'].' characters', $name);
            }

        }

        $input = '<input ';
        $input .= 'type="'.$type.'" ';
        $input .= 'name="'.$name.'" ';
        $input .= 'id="{input_id}" ';
        $input .= 'value="'.self::escHtml($value).'" ';
        $input .= 'class="{error}" ';
        $input .= self::makeAttr('placeholder', $attrs);
        $input .= self::makeAttr('readonly', $attrs);
        $input .= self::makeAttr('disabled', $attrs);
        $input .= self::makeAttr('autocomplete', $attrs);
        $input .= self::makeAttr('step', $attrs);
        $input .= self::makeAttr('min', $attrs);
        $input .= self::makeAttr('max', $attrs);
        $input .= '>';

        $this->inputs[$name] = $input;

    }

    /**
     * @param string $name
     * @param array $params
     * @return void
     */
    protected function addSingular($name, $params)
    {
        $initial = $params['initial'];
        $label = $params['label'];
        $type = $params['type'];

        if (!$this->submitted()) {
            $value = $initial;
        } else if (isset($_REQUEST[$name])) {
            $value = $_REQUEST[$name];
        } else {
            $value = '';
        }

        $this->addField($name, $params);
        $this->setValue($name, $value);

        $required = $this->isRequired($name);

        if ($this->submitted()) {

            if ($required && $value == '') {
                $this->error($label.' not provided', $name);
            }

        }

    }

    /**
     * @param string $name
     * @param array $params
     * @return void
     */
    protected function addMulti($name, $params)
    {
        $initial = $params['initial'];
        $label = $params['label'];

        if (!$this->submitted()) {
            $value = (array)$initial;
        } elseif (isset($_REQUEST[$name])) {
            $value = $_REQUEST[$name];
        } else {
            $value = array();
        }

        $this->addField($name, $params);
        $this->setValue($name, $value);

        $required = $this->isRequired($name);

        if ($this->submitted()) {

            if ($required && count($this->values[$name]) < 1) {
                $this->error($label.' not provided', $name);
            }

        }

    }

    /**
    * @param string $name
    * @param string $value
    * @return void
    */
    public function hidden($name, $value)
    {

        if ($this->is_submitted && isset($_REQUEST[$name])) {
            $value = $_REQUEST[$name];
        }

        $this->hidden[$name] = $value;
        $this->values[$name] = $value;

    }

    /**
     * @param string $name
     * @param string $label
     * @param string $initial
     * @param bool $required
     * @param array $attrs
     * @return void
    */
    public function text($name, $label, $initial='', $required=null, $attrs=array())
    {
        $params = get_defined_vars();
        $params['type'] = __FUNCTION__;
        $this->addInput($name, $params);
    }

    /**
     * @param string $name
     * @param string $label
     * @param string $initial
     * @param bool $required
     * @param array $attrs
     * @return void
    */
    public function textarea($name, $label, $initial='', $required=null, $attrs=array())
    {

        $params = get_defined_vars();
        $params['type'] = __FUNCTION__;
        $this->addInput($name, $params);

        $attrs = $this->filterAttrs($attrs, $name);
        $value = $this->getValue($name);

        $input = '<textarea ';
        $input .= 'name="'.$name.'" ';
        $input .= 'id="{input_id}" ';
        $input .= 'class="{error}" ';
        $input .= self::makeAttr('placeholder', $attrs);
        $input .= self::makeAttr('readonly', $attrs);
        $input .= self::makeAttr('disabled', $attrs);
        $input .= self::makeAttr('rows', $attrs);
        $input .= self::makeAttr('cols', $attrs);
        $input .= '>';
        $input .= self::escHtml($value);
        $input .= '</textarea>';

        $this->inputs[$name] = $input;

    }

    /**
     * @param string $name
     * @param string $label
     * @param array $options
     * @param string $initial
     * @param bool $required
     * @param array $attrs
     * @return void
    */
    public function select($name, $label, $options, $initial=false, $required=null, $attrs=array())
    {
        $params = get_defined_vars();
        $params['type'] = __FUNCTION__;
        $attrs = $this->filterAttrs($attrs, $name);

        if (empty($attrs['is_multi'])) {
            $this->addSingular($name, $params);
            $is_multi = false;
            $multiple = '';
            $input_name = $name;
        } else {
            $this->addMulti($name, $params);
            $is_multi = true;
            $multiple = ' multiple="multiple"';
            $input_name = $name.'[]';
        }

        $value = $this->getValue($name);

        $input = '<select name="'.$input_name.'" ';
        $input .= 'id="{input_id}" ';
        $input .= 'class="{error}" ';
        $input .= $multiple;
        $input .= '>';
        $input .= "\n";

        if (!isset($attrs['first_option'])) {
            $input .= '<option value="">[please select]</option>';
            $input .= "\n";
        } elseif ($attrs['first_option']) {
            $input .= '<option value="">'.$attrs['first_option'].'</option>';
            $input .= "\n";
        }

        foreach ($options as $key => $text) {

            if (is_array($text)) { //optgroups

                $input .= '<optgroup label="'.$key.'">'."\n";

                foreach ($text as $key => $text) {

                    if ($is_multi && in_array($key, $value)) {
                        $selected = ' selected="selected"';
                    } elseif ($key == $value) {
                        $selected = ' selected="selected"';
                    } else {
                        $selected = '';
                    }

                    if (isset($attrs['disabled']) && in_array($key, $attrs['disabled'])) {
                        $disabled = ' disabled="disabled"';
                    } else {
                        $disabled = '';
                    }

                    $input .= '<option value="'.$key.'" '.$selected.$disabled.'>'.$text.'</option>';
                    $input .= "\n";

                }

                $input .= '</optgroup>';
                $input .= "\n";

            } else {

                if ($is_multi && in_array($key, $value)) {
                    $selected = ' selected="selected"';
                } elseif ($key == $value) {
                    $selected = ' selected="selected"';
                } else {
                    $selected = '';
                }

                if (isset($attrs['disabled']) && in_array($key, $attrs['disabled'])) {
                    $disabled = ' disabled="disabled"';
                } else {
                    $disabled = '';
                }

                $input .= '<option value="'.$key.'" '.$selected.$disabled.'>'.$text.'</option>';
                $input .= "\n";

            }

        }

        $input .= '</select>';

        $this->inputs[$name] = $input;

    }

    /**
     * @param string $name
     * @param string $label
     * @param array $options
     * @param string $initial
     * @param bool $required
     * @param array $attrs
     * @return void
    */
    public function multiselect($name, $label, $options, $initial=false, $required=null, $attrs=array())
    {
        $attrs['is_multi'] = true;
        $this->select($name, $label, $options, $initial, $required, $attrs);
    }

    /**
     * @param string $name
     * @param string $label
     * @param array $options
     * @param string $initial
     * @param bool $required
     * @param array $attrs
     * @return void
    */
    public function radiobuttons($name, $label, $options, $initial=false, $required=null, $attrs=array())
    {
        $params = get_defined_vars();
        $params['type'] = __FUNCTION__;
        $this->addSingular($name, $params);

        $value = $this->getValue($name);
        $this->setValue($name, $value);

        $attrs = $this->filterAttrs($attrs, $name);
        $input = '';

        $i = 0;
        foreach ($options as $key => $text) {

            if ($key == $value && $value !== false && $value !== '' && $value !== null) {
                $checked = ' checked="checked"';
            } else {
                $checked = '';
            }

            $input .= '<label class="{error}">';
            $input .= '<input ';
            $input .= 'type="radio" ';
            $input .= 'name="'.$name.'" ';

            if ($i == 0) {
                $input .= 'id="{input_id}" '; //first option only
            }

            $input .= 'value="'.self::escHtml($key).'" ';
            $input .= $checked;
            $input .= '> ';
            $input .= $text;
            $input .= '</label> ';

            $i++;
        }

        $this->inputs[$name] = $input;

    }

    /**
     * @param string $name
     * @param string $label
     * @param array $options
     * @param array $initial
     * @param bool $required
     * @param array $attrs
     * @return void
    */
    public function checkboxes($name, $label, $options, $initial=array(), $required=null, $attrs=array())
    {

        $params = get_defined_vars();
        $params['type'] = __FUNCTION__;
        $this->addMulti($name, $params);

        $value = $this->getValue($name);
        $attrs = $this->filterAttrs($attrs, $name);
        $input = '';

        foreach ($options as $key => $text) {

            $input_name = $name.'[]';

            if (in_array($key, $value)) {
                $checked = ' checked="checked"';
            } else {
                $checked = '';
            }

            $input .= '<label class="{error}">';
            $input .= '<input ';
            $input .= 'type="checkbox" ';
            $input .= 'name="'.$input_name.'" ';
            $input .= 'id="{input_id}" ';
            $input .= 'value="'.self::escHtml($key).'" ';
            $input .= $checked;
            $input .= '> ';
            $input .= $text;
            $input .= '</label> ';

        }

        $this->inputs[$name] = $input;

    }

    /**
     * @param string $name
     * @param string $label
     * @param string $initial
     * @param array $attrs
     * @return void
    */
    public function checkbox($name, $label, $initial='', $attrs=array())
    {

        $params = get_defined_vars();
        $params['type'] = __FUNCTION__;
        $params['required'] = false;
        $params['label'] = false;
        $this->addInput($name, $params);

        $value = $this->getValue($name);
        $value = (bool)$value;
        $this->setValue($name, $value);

        if ($value) {
            $checked = ' checked="checked"';
        } else {
            $checked = '';
        }

        $input = '<label class="{error}">';
        $input .= '<input ';
        $input .= 'type="checkbox" ';
        $input .= 'name="'.$name.'" ';
        $input .= 'id="{input_id}" ';
        $input .= 'value="1" ';
        $input .= $checked;
        $input .= '> ';
        $input .= $label;
        $input .= '</label> ';

        $this->inputs[$name] = $input;

    }

    /**
     * @param string $name
     * @param string $label
     * @param int|DateTime $initial
     * @param bool $required
     * @param array $attrs
     * @return void
    */
    public function dateSelect($name, $label, $initial=false, $required=null, $attrs=array())
    {

        if (is_a($initial, 'DateTime')) {
            $initial = $initial->getTimestamp();
        }

        $params = get_defined_vars();
        $params['type'] = __FUNCTION__;
        $this->addField($name, $params);

        if (isset($_REQUEST[$name]) && isset($_REQUEST[$name.'-month']) && isset($_REQUEST[$name.'-year'])) {
            $is_valid_date = checkdate($_REQUEST[$name.'-month'], $_REQUEST[$name], $_REQUEST[$name.'-year']);
        } else {
            $is_valid_date = false;
        }

        if (!$this->submitted()) {
            $value = $initial;
        } elseif ($is_valid_date) {
            $value = mktime(0, 0, 0, $_REQUEST[$name.'-month'], $_REQUEST[$name], $_REQUEST[$name.'-year']);
        } else {
            $value = false;
        }

        $this->setValue($name, $value);
        $attrs = $this->filterAttrs($attrs, $name);

        if ($this->submitted()) {

            if ($required && !$is_valid_date) {
                $this->error($label.' is not a valid date', $name);
            }

            if (empty($attrs['greater_than'])) {
                //do nothing
            } elseif ($value < $this->values[$attrs['greater_than']]) {
                $target = $attrs['greater_than'];
                $target_label = $this->fields[$target]['label'];
                $this->error('Date must be after '.$target_label, $name);
            }

        }

        $fields = array('d' => 'day', 'm' => 'month', 'Y' => 'year');
        $input = '';
        $day = array();
        $year = array();
        $month = array(
            1 => 'January',
            2 => 'February',
            3 => 'March',
            4 => 'April',
            5 => 'May',
            6 => 'June',
            7 => 'July',
            8 => 'August',
            9 => 'September',
            10 => 'October',
            11 => 'November',
            12 => 'December'
        );

        if (isset($attrs['year_start'])) {
            $year_start = $attrs['year_start'];
        } else {
            $year_start = date('Y');
        }

        if (isset($attrs['year_end'])) {
            $year_end = $attrs['year_end'];
        } else {
            $year_end = $year_start + 5;
        }

        for ($i = 1; $i < 32; $i++) {
            $day[$i] = $i;
        }

        if ($year_start < $year_end) {

            for ($i = $year_start; $i <= $year_end; $i++) {
                $year[$i] = $i;
            }

        } else {

            for ($i = $year_start; $i >= $year_end; $i--) {
                $year[$i] = $i;
            }

        }

        foreach ($fields as $date_code => $date_input) {

            $input_name = $name.'-'.$date_input;
            $input_name = str_replace('-day', '', $input_name);
            $input_id = '{input_id}-'.$date_input;

            $input .= '<select ';
            $input .= 'name="'.$input_name.'" ';
            $input .= 'id="'.$input_id.'" ';
            $input .= 'class="{error}" ';
            $input .= '>';
            $input .= "\n";

            $input .= '<option value="0">['.$date_input.']</option>';
            $input .= "\n";

            foreach (${$date_input} as $key => $text) {

                if (($value && $key == date($date_code, $value))) {
                    $selected = ' selected="selected"';
                } else {
                    $selected = '';
                }

                $input .= '<option value="'.$key.'" '.$selected.'>';
                $input .= $text;
                $input .= '</option>';
                $input .= "\n";

            }

            $input .= '</select>';
            $input .= "\n";

        }

        $this->inputs[$name] = $input;

    }

    /**
     * @param string $name
     * @param string $label
     * @param int|DateTime $initial
     * @param bool $required
     * @param array $attrs
     * @return void
    */
    public function datePicker($name, $label, $initial=false, $required=null, $attrs=array())
    {
        if (is_a($initial, 'DateTime')) {
            $initial = $initial->getTimestamp();
        }

        $params = get_defined_vars();
        $params['type'] = __FUNCTION__;
        $this->addField($name, $params);

        if (!$this->submitted()) {
            $value = $initial;
        } elseif (isset($_REQUEST[$name])) {
            $value = strtotime($_REQUEST[$name]);
        } else {
            $value = 0;
        }

        $this->setValue($name, $value);
        $attrs = $this->filterAttrs($attrs, $name);
        $required = $this->isRequired($name);

        if ($this->submitted()) {

            if ($required && !$value) {
                $this->error($label.' not provided', $name);
            }

            if (empty($attrs['min'])) {
                //do nothing if min not set
            } elseif (!$value) {
                //do nothing if value not set
            } elseif ($value < $attrs['min']) {
                $this->error($label.' not valid', $name);
            }

            if (empty($attrs['max'])) {
                //do nothing if max not set
            } elseif (!$value) {
                //do nothing if value not set
            } elseif ($attrs['max'] == 'today' && $value > time()) {
                $this->error($label.' not valid', $name);
            } elseif ($attrs['max'] != 'today' && $value > $attrs['max']) {
                $this->error($label.' not valid', $name);
            }

        }

        if (isset($attrs['format'])) {
            $format = $attrs['format'];
            $format = str_replace('yyyy', 'Y', $format);
            $format = str_replace('yy', 'y', $format);
            $format = str_replace('mmmm', 'F', $format);
            $format = str_replace('mmm', 'M', $format);
            $format = str_replace('mm', '@', $format);
            $format = str_replace('m', 'n', $format);
            $format = str_replace('@', 'm', $format);
            $format = str_replace('dddd', 'l', $format);
            $format = str_replace('ddd', 'D', $format);
            $format = str_replace('dd', '@', $format);
            $format = str_replace('d', 'j', $format);
            $format = str_replace('@', 'd', $format);
        } else {
            $format = 'j F Y';
        }

        if ($value) {
            $value_display = date($format, $value);
        } else {
            $value_display = '';
        }

        $input = '<input ';
        $input .= 'type="text" ';
        $input .= 'name="'.$name.'" ';
        $input .= 'id="{input_id}" ';
        $input .= 'value="'.self::escHtml($value_display).'" ';
        $input .= 'class="{error}" ';
        $input .= self::makeAttr('placeholder', $attrs);
        $input .= '>';

        if (isset($attrs['format'])) {
            $format = $attrs['format'];
        } else {
            $format = 'd mmmm yyyy';
        }

        if (isset($attrs['num_years'])) {
            $num_years = $attrs['num_years'];
        } else {
            $num_years = 6;
        }

        if (isset($attrs['min'])) {
            $min = ', min: new Date('.date('Y', $attrs['min']).', '.(date('n', $attrs['min']) - 1).', '.date('j', $attrs['min']).')';
        } else {
            $min = '';
        }

        if (empty($attrs['max'])) {
            $max = '';
        } elseif ($attrs['max'] == 'today') {
            $max = ', max: true';
        } else {
            $max = ', max: new Date('.date('Y', $attrs['max']).', '.(date('n', $attrs['max']) - 1).', '.date('j', $attrs['max']).')';
        }

        //wp_enqueue_style('pickadate', TEMPLATE_URI.'/css/plugins.min.css');
        //wp_enqueue_script('pickadate', TEMPLATE_URI.'/js/plugins.min.js', 'jquery');

        ob_start();
            ?>
            <script>
            jQuery('#<?= $this->makeID($name); ?>').pickadate({
                container: 'body',
                selectYears: '<?= $num_years; ?>',
                selectMonths: true,
                format: '<?= $format; ?>',
                formatSubmit: 'yyyy/mm/dd',
                hiddenName: true
                <?= $min; ?>
                <?= $max; ?>
            });
            </script>
            <?php
        $html = ob_get_contents();
        ob_end_clean();

        self::addToFooter($html);

        $this->inputs[$name] = $input;

    }

    /**
     * @param string $name
     * @param string $label
     * @param string|DateTime $initial
     * @param bool $required
     * @param array $attrs
     * @return void
    */
    public function timeSelect($name, $label, $initial=false, $required=null, $attrs=array())
    {

        if (is_a($initial, 'DateTime')) {
            $initial = $initial->getTimestamp();
        }

        $params = get_defined_vars();
        $params['type'] = __FUNCTION__;
        $this->addField($name, $params);

        if (!$this->is_submitted && is_numeric($initial)) {
            $value = date('H:i', $initial);
        } elseif (!$this->submitted()) {
            $value = $initial;
        } elseif (!isset($_REQUEST[$name.'-hours']) || !isset($_REQUEST[$name.'-mins'])) {
            $value = false;
        } elseif ($_REQUEST[$name.'-hours'] != '--' && $_REQUEST[$name.'-mins'] != '--') {
            $value = $_REQUEST[$name.'-hours'].':'.$_REQUEST[$name.'-mins'];
        } else {
            $value = false;
        }

        $this->setValue($name, $value);

        if ($this->submitted()) {

            if ($required && !$value) {
                $this->error($label.' not provided', $name);
            }

        }

        $attrs = $this->filterAttrs($attrs, $name);
        $fields = array('hours' => 'hh', 'mins' => 'mm');
        $input = '';
        $hours = array();
        $mins = array();

        if ($value) {
            $field_values = array();
            $field_values['hours'] = strstr($value, ':', true);
            $field_values['mins'] = str_replace(':', '', strstr($value, ':', false));
        }

        if (isset($attrs['increments'])) {
            $increments = $attrs['increments'];
        } else {
            $increments = 15;
        }

        for ($i = 0; $i <= 23; $i++) {
            $j = str_pad($i, 2, '0', STR_PAD_LEFT);
            $hours[$j] = $j;
        }

        for ($i = 0; $i <= 59; $i = $i + $increments) {
            $j = str_pad($i, 2, '0', STR_PAD_LEFT);
            $mins[$j] = $j;
        }

        foreach ($fields as $time_input => $default) {

            $input_name = $name.'-'.$time_input;
            $input_id = '{input_id}-'.$time_input;

            if ($value) {
                $field_value = $field_values[$time_input];
            }

            $input .= '<select ';
            $input .= 'name="'.$input_name.'" ';
            $input .= 'id="'.$input_id.'" ';
            $input .= 'class="{error}" ';
            $input .= '>';
            $input .= "\n";

            if (!$required) {
                $input .= '<option value="--">['.$default.']</option>';
                $input .= "\n";
            }

            foreach (${$time_input} as $key => $text) {

                if ($value && $key == $field_value) {
                    $selected = ' selected="selected"';
                } else {
                    $selected = '';
                }

                $input .= '<option value="'.$key.'" '.$selected.'>'.$text.'</option>';
                $input .= "\n";

            }

            $input .= '</select>';
            $input .= "\n";

        }

        $this->inputs[$name] = $input;

    }

    /**
     * @param string $name
     * @param string $label
     * @param string $error_msg
     * @param bool $initial
     * @return void
    */
    public function confirm($name, $label, $error_msg='You must tick the confirm box', $initial=0)
    {

        $options = array(
            1 => $label
        );

        $this->checkboxes($name, '', $options, array($initial), false, array());

        $value = $this->getValue($name);
        $value = reset($value);
        $value = intval($value);
        $this->setValue($name, $value);

        if ($this->is_submitted && $value != 1) {
            $this->error($error_msg, $name);
        }

    }

    /**
     * @param string $name
     * @param string $label
     * @param string $initial
     * @param bool $required
     * @param array $attrs
     * @return void
    */
    public function yesno($name, $label, $initial=null, $required=null, $attrs=array())
    {
        if ($initial !== null) {
            $initial = intval($initial);
        }

        $options = array(
            1 => 'Yes',
            0 => 'No'
        );

        $this->radiobuttons($name, $label, $options, $initial, $required, $attrs);
    }

    /**
     * @param string $name
     * @param string $label
     * @param string $initial
     * @param bool $required
     * @param array $attrs
     * @return void
    */
    public function dob($name, $label, $initial=false, $required=null, $attrs=array())
    {

        $attrs['year_start'] = date('Y');
        $attrs['year_end'] = 1920;

        $this->dateSelect($name, $label, $initial, $required, $attrs);

    }

    /**
     * @param string $name
     * @param string $label
     * @param string $initial
     * @param bool $required
     * @param array $attrs
     * @return void
    */
    public function dobPicker($name, $label, $initial=false, $required=null, $attrs=array())
    {
        $attrs['min'] = mktime(0, 0, 0, 1, 1, 1920);
        $attrs['max'] = 'today';
        $attrs['num_years'] = 999;

        $this->datePicker($name, $label, $initial, $required, $attrs);
    }

    /**
     * @param string $name
     * @param string $label
     * @param string $initial
     * @param bool $required
     * @param array $attrs
     * @return void
    */
    public function gender($name, $label, $initial=false, $required=null, $attrs=array())
    {
        $options = array(
            'MALE' => 'Male',
            'FEMALE' => 'Female'
        );

        if (empty($attrs['use_radio'])) {
            $this->select($name, $label, $options, $initial, $required, $attrs);
        } else {
            $this->radioButtons($name, $label, $options, $initial, $required, $attrs);
        }
    }

    /**
     * @param string $name
     * @param string $label
     * @param string $initial
     * @param bool $required
     * @param array $attrs
     * @return void
    */
    public function salutation($name, $label, $initial=false, $required=null, $attrs=array())
    {

        $options = array(
            'Mr' => 'Mr',
            'Mrs' => 'Mrs',
            'Miss' => 'Miss',
            'Ms' => 'Ms'
        );

        $this->select($name, $label, $options, $initial, $required, $attrs);

    }

    /** @param string $name
     * @param string $label
     * @param string $initial
     * @param bool $required
     * @param array $attrs
     * @return void
    **/
    public function password($name, $label, $initial=false,$required=null, $attrs=array())
    {

        $params = get_defined_vars();
        $params['type'] = 'password'; // __FUNCTION__ ??
        $this->addInput($name, $params);

        $value = $this->getValue($name);

        if ($this->submitted()) {

        }

    }

    /**
     * @param string $name
     * @param string $label
     * @param string $initial
     * @param bool $required
     * @param array $attrs
     * @return void
    */
    public function email($name, $label, $initial=false, $required=null, $attrs=array())
    {

        $params = get_defined_vars();
        $params['type'] = __FUNCTION__;
        $this->addInput($name, $params);

        $value = $this->getValue($name);
        $value = trim($value);
        $this->setValue($name, $value);

        if ($this->submitted()) {

            if (!self::validateEmail($value)) {
                $this->error($field['label'].' is not a valid email address', $name, 'IGNORE');
            }

        }

    }

    /**
     * @param string $name
     * @param string $label
     * @param string $label_2
     * @param string $initial
     * @param bool $required
     * @param array $attrs
     * @return void
    */
    public function emailConfirm($name, $label, $label_2, $initial=false, $required=null, $attrs=array())
    {

        $name_2 = $name.'_confirm';

        $this->email($name, $label, $initial, $required, $attrs);
        $this->email($name_2, $label_2, $initial, $required, $attrs);

        $value_1 = $this->getValue($name);
        $value_2 = $this->getValue($name_2);

        if ($this->submitted()) {

            if ($value_1 != $value_2) {
                $this->error('The emails you entered do not match. Please try again', $name_2);
            }

        }

    }

    /**
     * @param string $name
     * @param string $label
     * @param string $initial
     * @param bool $required
     * @param array $attrs
     * @return void
    */
    public function url($name, $label, $initial=false, $required=null, $attrs=array())
    {

        if (isset($_REQUEST[$name])) {
            $_REQUEST[$name] = trim($_REQUEST[$name]);
        } elseif (!$initial) {
            $initial = 'http://';
        }

        $params = get_defined_vars();
        $params['type'] = 'text';
        $this->addInput($name, $params);

        $value = $this->getValue($name);

        if ($value == 'http://') {
            $value = false;
            $this->setValue($name, $value);
        }

        if ($this->submitted()) {

            if ($required && !$value) {
                $this->error($label.' not provided', $name);
            }

        }

    }

    /**
     * @param string $name
     * @param string $label
     * @param string $initial
     * @param bool $required
     * @param array $attrs
     * @return void
    */
    public function number($name, $label, $initial='', $required=null, $attrs=array())
    {

        $params = get_defined_vars();
        $params['type'] = __FUNCTION__;
        $this->addInput($name, $params);

        $value = $this->getValue($name);

        if ($this->submitted()) {

            if ($required && !intval($value)) {
                $this->error($label.' cannot be zero', $name);
            }

            if ($value && !is_numeric($value)) {
                $this->error($label.' must be numeric', $name);
            }

            if (isset($attrs['min']) && $value < $attrs['min']) {
                $this->error(sprintf('%s is below the minimum value', $label), $name);
            }

            if (isset($attrs['max']) && $value > $attrs['max']) {
                $this->error(sprintf('%s is above the maximum value', $label), $name);
            }

        }

    }

    /**
     * @param string $name
     * @param string $label
     * @param string $initial
     * @param bool $required
     * @param array $attrs
     * @return void
    */
    public function phone($name, $label, $initial='', $required=null, $attrs=array())
    {

        $params = get_defined_vars();
        $params['type'] = 'text';
        $this->addInput($name, $params);

        $value = $this->getValue($name);

        if ($this->submitted()) {

            if (strlen($value) > 0 && preg_match('/[A-Za-z]+/', $value)) {
                $msg = sprintf('%s is not a valid phone number', $value);
                $this->error($msg, $name, 'IGNORE');
            }

        }

    }

    /**
     * @param string $group_name
     * @param string $label
     * @param array $settings
     * @param array $initial
     * @param bool $required
     * @param array $attrs
     * @return void
    */
    public function settings($group_name, $group_label, $settings, $initial=array(), $required=null, $attrs=array())
    {

        $initial = (array)$initial;
        $this->checkboxes($group_name, $group_label, $settings, $initial, false, $attrs);
        $value = $this->getValue($group_name);

        if ($this->submitted()) {

            foreach ($settings as $name => $label) {

                if (in_array($name, $value)) {
                    $this->setValue($name, true);
                } else {
                    $this->setValue($name, false);
                }

            }

            unset($this->values[$group_name]);

        }

    }

    /**
     * @param string $name
     * @param string $label
     * @param string $initial
     * @param bool $required
     * @param array $attrs
     * @return void
    */
    public function file($name, $label = '', $initial = '', $required = null, $attrs = [])
    {
        if (empty($attrs['extensions'])) {
            $attrs['extensions'] = array();
        }

        $params = get_defined_vars();
        $params['type'] = __FUNCTION__;
        $this->addField($name, $params);

        //path to directory
        if (isset($attrs['path_to']) && $attrs['path_to'] === true) {
            $path_to = Setup::uploadPath();
        } elseif (isset($attrs['path_to'])) {
            $path_to = rtrim($attrs['path_to'], '/');
        } else {
            $path_to = false;
        }

        //get source
        $source = null;

        if (!empty($_FILES[$name]['size'])) {

            //get uploaded file
            $source = $_FILES[$name]['tmp_name'];
            $filename = $_FILES[$name]['name'];
            $ext = Filter::extension($filename);

            //check image extension valid
            if (!empty($attrs['extensions'])) {

                $allowed_exts = (array)$attrs['extensions'];
                $allowed_exts_formatted = implode(', ', $allowed_exts);

                if (in_array($ext, $allowed_exts)) {
                    //do nothing if extension is valid
                } else {
                    $source = false;
                    $this->error(sprintf('Your file must be in one of the following formats: %s', $allowed_exts_formatted), $name);
                }

            }

        }

        //default value
        if ($source) {
            $value = $source;
        } elseif (isset($_REQUEST[$name])) {
            $value = $_REQUEST[$name];
        } elseif ($initial) {
            $value = $initial;
        } else {
            $value = null;
        }

        //move to target path
        if ($source && $path_to) {

            //generate filename
            if (empty($attrs['keep_filename'])) {
                $filelabel = str_replace('.'.$ext, '', $filename);
                $filelabel = Filter::clean($filelabel);
                $filelabel = substr($filelabel, 0, 50);
                $uid = microtime();
                $uid = md5($uid);
                $uid = substr($uid, 0, 8);
                $filename = $filelabel.'-'.$uid.'.'.$ext;
            }

            //upload file to directory
            $destination = $path_to.'/'.$filename;
            copy($source, $destination);
            $value = $filename;

        }

        //cleanup files
        if ($this->success()) {
            if ($initial) {
                if ($value != $initial) { //new file uploaded - delete old file
                    @unlink($path_to.'/'.$initial);
                } elseif (!empty($_REQUEST[$name.'_DELETE'])) { //delete old file
                    @unlink($path_to.'/'.$initial);
                    $value = false;
                }
            }
        }

        //set value
        $this->setValue($name, $value);

        //check required
        if ($this->submitted()) {
            if ($this->isRequired($name) && !$value && !$initial) {
                $msg = 'You must select a file to upload';
                $this->error($msg, $name);
            }
        }

        //make input html
        $attrs = $this->filterAttrs($attrs, $name);
        $abspath = Setup::rootPath();
        $base_uri = str_replace($abspath, '', $path_to);
        $filepath = $path_to.'/'.$value;
        $input = '';

        if ($this->isRequired($name)) {
            $show_delete = false;
        } elseif (isset($attrs['show_delete'])) {
            $show_delete = $attrs['show_delete'];
        } else {
            $show_delete = true;
        }

        if ($value && $path_to) {

            $input .= '<div class="preview_wrap">';

            if (is_readable($filepath)) {
                $input .= '<a href="'.$base_uri.'/'.$value.'" target="_blank">';
                $input .= $value;
                $input .= '</a>';
            } else {
                $input .= $value;
            }

            $input .= '&nbsp;&nbsp;';

            if ($show_delete) {
                $input .= '<label>';
                $input .= '<input type="checkbox" name="'.$name.'_DELETE'.'" value="1"> ';
                $input .= 'Delete';
                $input .= '</label> ';
            }

            $input .= '</div>';

        }

        $input .= '<input ';
        $input .= 'type="file" ';
        $input .= 'name="'.$name.'" ';
        $input .= 'id="{input_id}" ';
        $input .= 'value="'.self::escHtml($value).'" ';
        $input .= 'class="{error}" ';
        $input .= self::makeAttr('placeholder', $attrs);
        $input .= self::makeAttr('readonly', $attrs);
        $input .= self::makeAttr('disabled', $attrs);
        $input .= self::makeAttr('autocomplete', $attrs);
        $input .= self::makeAttr('step', $attrs);
        $input .= self::makeAttr('min', $attrs);
        $input .= self::makeAttr('max', $attrs);
        $input .= '>';

        if ($value && $path_to) {
            $input .= '<input ';
            $input .= 'type="hidden" ';
            $input .= 'name="'.$name.'" ';
            $input .= 'value="'.$value.'" ';
            $input .= '>';
        }

        $this->inputs[$name] = $input;
    }

    /**
     * @param string $html
     * @return void
     */
    public function html($html)
    {
        end($this->inputs);
        $last_input = key($this->inputs);

        if ($last_input) {
            $this->html[$last_input]['after'][] = $html;
        } else {
            $last_input = '__start__';
            $this->html[$last_input]['before'][] = $html;
        }

    }

    /**
     * @param string $html
     * @return void
     */
    public function prepend($html)
    {
        $this->html['__start__']['before'][] = $html;
    }

    /**
     * @param string $input_name
     * @param string $html
     * @return void
     */
    public function before($input_name, $html)
    {
        $this->html[$input_name]['before'][] = $html;
    }

    /**
     * @param string $input_name
     * @param string $html
     * @return void
     */
    public function after($input_name, $html)
    {
        $this->html[$input_name]['after'][] = $html;
    }

    /**
     * @return bool
     */
    public function submitted()
    {
        return $this->is_submitted;
    }

    /**
     * @return bool
     */
    public function success()
    {
        return $this->has_success;
    }

    /**
     * @param string $msg
     * @param string $key
     * @param bool $ignore
     * @return void
     */
    public function error($msg, $key=false, $ignore=false)
    {

        $this->has_success = false;

        if ($key && !isset($this->errors[$key])) {
            $this->errors[$key] = $msg;
        } elseif (!$ignore) {
            $this->errors[] = $msg;
        }

    }

    /**
     * @param Exception $Exception
     * @param string $key
     * @param bool $ignore
     * @return void
     */
    public function exception($Exception, $key=false, $ignore=false)
    {
        $msg = $Exception->getMessage();
        $this->error($msg, $key, $ignore);
    }

    /**
     * @param string $label
     * @param array $attrs
     * @return void
     */
    public function submit($label, $attrs=array())
    {
        $html = '<div class="'.$this->submit_wrap.'">';
        $html .= '<button type="submit" class="{class}">'.$label.'</button>';
        $html .= '{append}';
        $html .= '</div>';
        $html .= "\n";

        if (isset($attrs['append'])) {
            $html = str_replace('{append}', $attrs['append'], $html);
        } else {
            $html = str_replace('{append}', '', $html);
        }

        if (isset($attrs['class'])) {
            $html = str_replace('{class}', $attrs['class'], $html);
        } else {
            $html = str_replace('{class}', '', $html);
        }

        $this->inputs['submit'] = $html;
    }

    /**
     * @return void
     */
    public function display()
    {
        echo '<div id="'.$this->form_id.'">';
        echo "\n";

        $this->putErrors();
        $this->putForm();

        echo '</div>';
        echo "\n";
    }

    /**
     * @return void
     */
    public function clearErrors()
    {
        $this->errors = [];

        if ($this->is_submitted) {
            $this->has_success = true;
        }
    }

    /**
     * @return void
     */
    public function stopSubmit()
    {
        $this->is_submitted = false;
        $this->has_success = false;
    }

    /**
     * @return void
     */
    public function open()
    {
        //make action url
        $action = $this->action;
        $action .= ($this->keep_gets ? self::getRequestQuery() : '');
        $action .= ($this->fragment ? '#'.$this->fragment : '');

        //start form
        echo '<form action="'.$action.'" ';
        echo 'method="'.$this->method.'" ';
        echo 'class="'.$this->form_class.'" ';
        echo 'enctype="multipart/form-data">';
        echo "\n";

        $this->putHidden();
    }

    /**
     * @return void
     */
    public function close()
    {
        echo '</form>';
        echo "\n";
    }

    /**
     * @param string $name
     * @return void
     */
    public function hasError($name)
    {
        if (isset($this->errors[$name])) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * @return void
     */
    public function putErrors()
    {

        if (!$this->errors) {
            return false;
        }

        $html = '<div class="'.$this->error_wrap.'">'."\n";
        $html .= '<ul>'."\n";

        foreach ($this->errors as $msg) {
            $html .= '<li>'.self::escHtml($msg).'</li>'."\n";
        }

        $html .= '</ul>'."\n";
        $html .= '</div>'."\n";

        echo $html;

    }

    /**
     * @return void
     */
    public function putForm()
    {
        //open form
        $this->open();

        //put hidden
        $this->putHidden();

        //add html
        if (isset($this->html['__start__']['before'])) {
            foreach ($this->html['__start__']['before'] as $html_bit) {
                echo $html_bit."\n";
            }
        }

        //put fields
        foreach ($this->fields as $name => $params) {
            $this->putField($name);
        }

        //put submit
        if (isset($this->inputs['submit'])) {
            $this->putField('submit');
        }

        //close form
        $this->close();

    }

    /**
     * @return void
     */
    public function putHidden()
    {

        $html = '<div style="display:none">'."\n";

        //for forms with get method, add relevant hidden fields to match current query string
        if ($this->keep_gets && $this->method == 'get') {

            $qstring = self::getRequestQuery();
            $qstring = trim($qstring, '?');
            $vars = explode('&', $qstring);
            $vars = array_filter($vars);

            foreach ($vars as $var) {

                $var = explode('=', $var);
                $key = urldecode($var[0]);
                $val = urldecode($var[1]);
                $master_key = strstr($key, '[', true);

                if (!$master_key) {
                    $master_key = $key;
                }

                if (isset($this->fields[$master_key]) || isset($this->hidden[$master_key])) {
                    continue; //skip fields that exist in this form
                }

                $html .= '<input type="hidden" name="'.$key.'" value="'.$val.'">'."\n";

            }

        }

        foreach ($this->hidden as $name => $value) {
            if (!is_scalar($value)) {
                $value = '';
            }

            $html .= '<input type="hidden" name="'.$name.'" value="'.$value.'">'."\n";
        }

        $html .= '</div>'."\n";

        echo $html;

    }

    /**
     * @param string $name
     * @return void
     */
    public function putField($name)
    {
        $html = '';

        if (isset($this->html[$name]['before'])) {
            foreach ($this->html[$name]['before'] as $html_bit) {
                $html .= $html_bit."\n";
            }
        }

        if ($name == 'submit') {
            $html .= $this->inputs[$name];
        } elseif (empty($this->fields[$name])) {
            return;
        } else {

            if (empty($this->fields[$name]['attrs']['lang'])) {
                $data_lang = '';
            } else {
                $data_lang = 'data-lang="'.$this->fields[$name]['attrs']['lang'].'"';
            }

            $label_html = $this->putLabel($name, false);
            $input_html = $this->putInput($name, false);
            $helper_html = $this->putHelper($name, false);
            $type = $this->fields[$name]['type'];
            $type_modifier = '__'.strtolower($type);

            $html .= '<div class="'.$this->field_wrap.' '.$type_modifier.'" '.$data_lang.'>'."\n";
            $html .= '{label}';
            $html .= '<div class="'.$this->input_wrap.'">';
            $html .= '{input}';
            $html .= '</div>';
            $html .= '{helper}'."\n";
            $html .= '</div>'."\n";

            $html = str_replace('{label}', $label_html, $html);
            $html = str_replace('{input}', $input_html, $html);
            $html = str_replace('{helper}', $helper_html, $html);
        }

        if (isset($this->html[$name]['after'])) {
            foreach ($this->html[$name]['after'] as $html_bit) {
                $html .= $html_bit."\n";
            }
        }

        echo $html;

    }

    /**
     * @param string $name
     * @param bool $echo
     * @return string
     */
    public function putLabel($name, $echo=true)
    {
        //make id
        $input_id = $this->makeID($name);

        //label text
        if (isset($this->labels[$name])) {
            $label_text = $this->labels[$name];
        } else {
            $label_text = $this->fields[$name]['label'];
        }

        //remove label if empty
        if (!$label_text) {
            return '';
        }

        //error class
        if (isset($this->errors[$name])) {
            $error_class = $this->error_class;
        } else {
            $error_class = '';
        }

        //required html
        if ($this->isRequired($name)) {
            $required_html = ' <span class="'.$this->required_wrap.'">*</span>';
        } else {
            $required_html = '';
        }

        //sublabel
        if (isset($this->fields[$name]['attrs']['sublabel'])) {
            $sublabel = $this->fields[$name]['attrs']['sublabel'];
        } elseif (isset($this->sublabels[$name])) {
            $sublabel = $this->sublabels[$name];
        } else {
            $sublabel = false;
        }

        if ($sublabel) {
            $sublabel_html = '<span class="'.$this->sublabel_wrap.'">';
            $sublabel_html .= $sublabel;
            $sublabel_html .= '</span>';
        } else {
            $sublabel_html = '';
        }

        //label
        $html = '<div class="'.$this->label_wrap.'">';
        $html .= '<label for="{input_id}" class="{error}">';
        $html .= '{label_text}';
        $html .= '{label_required}';
        $html .= '</label>';
        $html .= '{sublabel}';
        $html .= '</div>';
        $html .= "\n";

        $html = str_replace('{input_id}', $input_id, $html);
        $html = str_replace('for=""', '', $html); //remove blank for=""
        $html = str_replace('{error}', $error_class, $html);
        $html = str_replace('{label_text}', $label_text, $html);
        $html = str_replace('{label_required}', $required_html, $html);
        $html = str_replace('{sublabel}', $sublabel_html, $html);

        if ($echo) {
            echo $html;
        } else {
            return $html;
        }

    }

    /**
     * @param string $name
     * @param bool $echo
     * @return string
     */
    public function putInput($name, $echo=true)
    {

        //make id
        $input_id = $this->makeID($name);

        //error class
        if (isset($this->errors[$name])) {
            $error_class = $this->error_class;
        } else {
            $error_class = '';
        }

        $html = $this->inputs[$name];
        $html = str_replace('{input_id}', $input_id, $html);
        $html = str_replace('{error}', $error_class, $html);

        if ($echo) {
            echo $html;
        } else {
            return $html;
        }

    }

    /**
     * @param string $name
     * @param bool $echo
     * @return string
     */
    public function putHelper($name, $echo=true)
    {

        if (isset($this->fields[$name]['attrs']['helper'])) {
            $helper_text = $this->fields[$name]['attrs']['helper'];
        } elseif (isset($this->helpers[$name])) {
            $helper_text = $this->helpers[$name];
        } else {
            $helper_text = false;
        }

        if ($helper_text) {

            $html = '<div class="'.$this->helper_wrap.'">';
            $html .= '<i class="'.$this->helper_wrap.'-icon">i</i>';
            $html .= '<span class="'.$this->helper_wrap.'-text">';
            $html .= '{helper_text}';
            $html .= '</span>';
            $html .= '</div>';

            $html = str_replace("{helper_text}", $helper_text, $html);

        } else {
            $html = '';
        }

        if ($echo) {
            echo $html;
        } else {
            return $html;
        }

    }

    /**
     * @param string $name
     * @return string
     */
    protected function makeID($name)
    {
        return $this->form_id.'-'.$name;
    }

    /**
     * @param array $attrs
     * @param string $name
     * @return array
     */
    protected function filterAttrs($attrs, $name)
    {
        if (isset($this->fields[$name]['label'])) {
            $label = $this->fields[$name]['label'];
        } else {
            $label = '';
        }

        if (isset($attrs['placeholder'])) {

            if ($attrs['placeholder'] === true) {
                $attrs['placeholder'] = $label;
            } else {
                //do nothing if manual placeholder
            }

        } elseif (isset($this->placeholders[$name])) {
            $attrs['placeholder'] = $this->placeholders[$name];
        } elseif ($this->placeholder_all) {
            $attrs['placeholder'] = $label;
        }

        return $attrs;
    }

    /**
     * @param string $name
     * @return bool
     */
    public function isRequired($name)
    {
        $field = $this->getField($name);

        if (!$field) {
            return;
        } elseif ($field['required'] === null) {
            return $this->require_all;
        } else {
            return $field['required'];
        }
    }

    /**
     * @param string $key
     * @param array $attrs
     * @param mixed $default
     * @return mixed
     */
    protected static function makeAttr($key, $attrs, $default=false)
    {

        if (isset($attrs[$key])) {
            return ' '.$key.'="'.$attrs[$key].'"';
        } else if ($default) {
            return ' '.$key.'="'.$default.'"';
        } else {
            return '';
        }

    }

    /**
     * @param string $string
     * @return string
     */
    protected static function escHtml($string)
    {
        return htmlspecialchars($string, ENT_QUOTES, 'UTF-8');
    }

    /**
     * @todo Validate the email passed.
     * @return bool
     */
    protected static function validateEmail($email)
    {
        return true;
    }

    /**
     * @param string $html
     * @return bool
     */
    protected static function addToFooter($html)
    {
        add_action('wp_footer', function() use ($html){
            echo $html;
        }, 100);
    }

    /**
     * @return string
     */
    protected static function getRequestPath()
    {
        if (isset($_SERVER['REQUEST_URI'])) {
            return parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
        } else {
            return false;
        }
    }

    /**
     * @return string
     */
    protected static function getRequestQuery()
    {
        if (isset($_SERVER['REQUEST_URI'])) {

            $query = parse_url($_SERVER['REQUEST_URI'], PHP_URL_QUERY);

            if ($query) {
                return '?'.$query;
            } else {
                return '';
            }

        } else {
            return false;
        }
    }

}
