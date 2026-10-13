<?php
/**
 * XFormHelper
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright Copyright 2010, Yasushi Ichikawa http://github.com/ichikaway/
 * @package xform
 * @subpackage xform.helper
 * @license http://www.opensource.org/licenses/mit-license.php The MIT License
 */

/**
 * XFormHelper
 *
 * On confirmation screen, this helper just show value of post data
 *  instead of making form tags.
 * On form input screen, this helper behaves same as form helper.
 *
 * How does this helper know on confirmation screen?
 * When the confirmation transition, do following 1 or 2.
 *  1. in controller
 *     $this->params['xformHelperConfirmFlag'] = true;
 *  2. in controller or view file
 *     XformHelper::confirmScreenFlag = true;
 *
 * If you want to mask a password field on confirmation screen,
 *  use password() instead of input().
 *
 * If you want to change separator of datetime,
 *  set separator value on the changeDatetimeSeparator property.
 */
class XformHelper extends FormHelper
{
    /**
     * confirmation screen flag
     *
     * @var bool
     */
    public $confirmScreenFlag = false;

    /**
     * not fillin password value
     * if set false, password value is set on form input tag.
     *
     * @var bool
     */
    public $notFillinPasswordValue = true;

    /**
     * output value are escaped on confirmation screen.
     *
     * @var bool
     */
    public $doHtmlEscape = true;

    /**
     * execute nl2br() for output value on confirmation screen.
     *
     * @var bool
     */
    public $doNl2br = true;

    /**
     * If set true and change $doHtmlEscape or $doNl2br properties,
     * these properties are not changed by default value after output.
     *
     * @var bool
     */
    public $escapeBrPermanent = false;

    /**
     * The field which has array data like checkbox(),
     * these array value join with this separator on confirmation screen.
     *
     * @var string
     */
    public $confirmJoinSeparator = ', ';

    /**
     * change datetime separator on form input and confirmation screen.
     *
     * @var array
     *
     * Example:
     *   var $changeDatetimeSeparator = array(
     *       'datefmt' => array(
     *           'year' => ' / ',
     *           'month' => ' / ',
     *           'day' => '',
     *           'afterDateTag' => '&nbsp;&nbsp;&nbsp;', //set value between date and time tags.
     *           ),
     *       'timefmt' => array(
     *           'hour' => ' : ',
     *           'min' => '',
     *           'meridian' => '',
     *           )
     *       );
     */
    public $changeDatetimeSeparator = null;

    /**
     * set default options for the input method.
     *
     * @var array
     */
    public $inputDefaultOptions = [];

    /**
     * if set true, month name will be number.
     *
     * @var bool
     */
    public $monthNameSetNumber = false;

    /**
     * @param View $View
     * @param array $config
     */
    public function __construct(View $View, $config = [])
    {
        if (!empty($config)) {
            foreach ($config as $key => $val) {
                $this->{$key} = $val;
            }
        }
        parent::__construct($View);
    }

    /**
     * override __call for password() and text() method
     *
     * @param string $method
     * @param array $params
     * @return mixed
     */
    public function __call($method, $params)
    {
        $fieldName = $params[0];
        if ($method === 'password' && $this->checkConfirmScreen()) {
            $value = $this->getConfirmInput($fieldName);
            if (!empty($value)) {
                return '*****';
            } else {
                return '';
            }
        } elseif ($this->checkConfirmScreen()) {
            return $this->getConfirmInput($fieldName);
        }

        if ($method === 'password' && $this->notFillinPasswordValue) {
            $params[1]['value'] = ''; //password value clear if show input form.
        }

        return parent::__call($method, $params);
    }

    /**
     * @param string $fieldName
     * @param array $options
     * @return string
     */
    public function input($fieldName, $options = [])
    {
        $options = array_merge($this->inputDefaultOptions, $options);

        return parent::input($fieldName, $options);
    }

    /**
     * @param string $field
     * @param array|string|null $text
     * @param array $options
     * @return mixed
     */
    public function error($field, $text = null, $options = [])
    {
        $defaults = ['wrap' => true];
        $options = array_merge($defaults, $options);

        return parent::error($field, $text, $options);
    }

    /**
     * @param string $fieldName
     * @param string $dateFormat
     * @param string $timeFormat
     * @param array $attributes
     * @return mixed
     */
    public function dateTime($fieldName, $dateFormat = 'DMY', $timeFormat = '12', $attributes = [])
    {
        if ($this->checkConfirmScreen()) {
            $args = func_get_args();

            return $this->getConfirmDatetime($fieldName, $args);
        }

        if (empty($attributes['monthNames']) && $this->monthNameSetNumber) {
            $attributes['monthNames'] = false;
        }

        $separator = !empty($attributes['separator']) ? $attributes['separator'] : '-';
        $datefmt = [
            'year' => $separator,
            'month' => $separator,
            'day' => '',
            'afterDateTag' => '',
        ];
        $timefmt = [
            'hour' => ':',
            'min' => '',
            'meridian' => '',
        ];

        if (!empty($this->changeDatetimeSeparator)) {
            $datefmt = $this->changeDatetimeSeparator['datefmt'];
            $timefmt = $this->changeDatetimeSeparator['timefmt'];
        }

        $out = $out_date = $out_time = null;
        if (!empty($dateFormat) && $dateFormat !== 'NONE') {
            $tmp_separator = !empty($attributes['separator']) ? $attributes['separator'] : null;
            $attributes['separator'] = '__/__';
            $out_date = parent::dateTime($fieldName, $dateFormat, 'NONE', $attributes);
            $attributes['separator'] = $tmp_separator;
        }

        if (!empty($timeFormat) && $timeFormat !== 'NONE') {
            $out_time = parent::dateTime($fieldName, 'NONE', $timeFormat, $attributes);
        }

        if (!empty($out_date)) {
            $pattern = '#^(.+?)__/__(.+?)__/__(.+?)$#is';
            $out .= preg_replace($pattern, '$1' . $datefmt['year'] . ' $2' . $datefmt['month'] . ' $3' . $datefmt['day'], $out_date);
            $out .= $datefmt['afterDateTag'];
        }

        if (!empty($out_time) && $timeFormat == 24) {
            $pattern = '#^<select(.*?)</select>:<select(.*?)$#is';
            $replace = '<select$1</select>' . $timefmt['hour'] . ' <select$2' . $timefmt['min'];
            $out .= preg_replace($pattern, $replace, $out_time);
        }

        if (!empty($out_time) && $timeFormat == 12) {
            $pattern = '#^<select(.*?)</select>:<select(.*?)</select> <select(.*?)$#is';
            $replace = '<select$1</select>' . $timefmt['hour'] . ' <select$2</select>' . $timefmt['min'] . '<select$3';
            $out .= preg_replace($pattern, $replace, $out_time);
        }

        return $out;
    }

    /**
     * @param string $fieldName
     * @param array|null $options
     * @return mixed|string
     */
    public function textarea($fieldName, $options = null)
    {
        if ($this->checkConfirmScreen()) {
            return $this->getConfirmInput($fieldName);
        }

        return parent::textarea(...func_get_args());
    }

    /**
     * @param string $fieldName
     * @param array|null $options
     * @param array $attributes
     * @return mixed
     */
    public function radio($fieldName, $options = null, $attributes = [])
    {
        if ($this->checkConfirmScreen()) {
            return $this->getConfirmInput($fieldName, $options);
        }

        return parent::radio(...func_get_args());
    }

    /**
     * @param string $fieldName
     * @param array|null $options
     * @param array $attributes
     * @return mixed
     */
    public function select($fieldName, $options = null, $attributes = [])
    {
        if ($this->checkConfirmScreen()) {
            return $this->getConfirmInput($fieldName, $options);
        }

        return parent::select(...func_get_args());
    }

    /**
     * @param string $fieldName
     * @param array|null $options
     * @return mixed
     */
    public function checkbox($fieldName, $options = null)
    {
        if ($this->checkConfirmScreen()) {
            return $this->getConfirmInput($fieldName);
        }

        return parent::checkbox(...func_get_args());
    }

    /**
     * @return bool
     */
    public function checkConfirmScreen()
    {
        if (!empty($this->request->params['xformHelperConfirmFlag']) && $this->request->params['xformHelperConfirmFlag'] === true) {
            return true;
        }

        if ($this->confirmScreenFlag === true) {
            return true;
        }

        return false;
    }

    /**
     * @param string $data
     * @return string
     */
    protected function _confirmValueOutput($data)
    {
        if ($this->doHtmlEscape) {
            $data = h($data);
        }

        if ($this->doNl2br) {
            $data = nl2br($data);
        }

        if ($this->escapeBrPermanent === false) {
            $this->doHtmlEscape = true;
            $this->doNl2br = true;
        }

        return $data;
    }

    /**
     * @param string $fieldName
     * @param array|null $options
     * @return mixed|false
     */
    protected function _getFieldData($fieldName, $options = null)
    {
        $modelname = !empty($this->request->params['models']) ? key($this->request->params['models']) : null;

        // for Model.field or Model.N.field pattern
        $model_field = explode('.', $fieldName);

        if (!empty($model_field[2]) && !empty($this->request->data[$model_field[0]])) {
            $fieldName = $model_field[2];
        } elseif (!empty($model_field[1]) && !empty($this->request->data[$model_field[0]])) {
            $fieldName = $model_field[1];
        } elseif (!empty($model_field[0])) {
            $fieldName = $model_field[0];
        }

        if (!empty($model_field[2]) && !empty($this->request->data[$model_field[0]])) {
            $data = $this->request->data[$model_field[0]][$model_field[1]];
        } elseif (!empty($model_field[1]) && !empty($this->request->data[$model_field[0]])) {
            $data = $this->request->data[$model_field[0]];
        } else {
            if (empty($modelname)) {
                $data = current($this->request->data);
            } elseif (isset($this->request->data[$modelname])) {
                $data = $this->request->data[$modelname];
            } else {
                $data = [];
            }
        }

        if (isset($data[$fieldName])) {
            return $data[$fieldName];
        }

        return false;
    }

    /**
     * @param string $fieldName
     * @param array|null $options
     * @return string
     */
    public function getConfirmInput($fieldName, $options = null)
    {
        $data = $this->_getFieldData($fieldName, $options);
        if (isset($data)) {
            if (is_array($data)) {
                if (is_array($options)) {
                    foreach ($data as $key => $val) {
                        $data[$key] = !empty($options[$val]) ? $options[$val] : $val;
                    }
                }
                $out = join($this->confirmJoinSeparator, $data);
            } else {
                $out = is_array($options) && !empty($options[$data]) ? $options[$data] : $data;
            }

            return $this->_confirmValueOutput($out);
        }

        return '';
    }

    /**
     * @param string $fieldName
     * @param array $options
     * @return mixed
     */
    public function getConfirmDatetime($fieldName, $options = [])
    {
        if ($data = $this->_getFieldData($fieldName)) {
            if (is_array($data)) {
                $nothing = true;
                foreach ($data as $key => $val) {
                    if (!empty($val)) {
                        $nothing = false;
                    }
                }

                if ($nothing) {
                    return '';
                }

                $separator = !empty($options[3]['separator']) ? $options[3]['separator'] : '-';
                $datefmt = [
                    'year' => $separator,
                    'month' => $separator,
                    'day' => '',
                    'afterDateTag' => '',
                ];
                $timefmt = [
                    'hour' => ':',
                    'min' => '',
                    'meridian' => '',
                ];

                $out = null;

                if (!empty($this->changeDatetimeSeparator)) {
                    $datefmt = $this->changeDatetimeSeparator['datefmt'];
                    $timefmt = $this->changeDatetimeSeparator['timefmt'];
                }

                foreach ($datefmt as $key => $val) {
                    $out .= (isset($data[$key]) ? $data[$key] . $val : '');
                }
                if (!empty($options[2]) && $options[2] !== 'NONE') {
                    $out .= ' ';
                    foreach ($timefmt as $key => $val) {
                        $sprintf_fmt = isset($data[$key]) && is_numeric($data[$key]) ? '%02d' : '%s';
                        $out .= (isset($data[$key]) ? sprintf($sprintf_fmt, $data[$key]) . $val : '');
                    }
                }
            } else {
                $out = $data;
            }

            return $this->_confirmValueOutput($out);
        }

        return '';
    }
}
