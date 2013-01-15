<?php
/**
 * Copyright (C) 2011 by Pieter Vogelaar (pietervogelaar.nl) and Kees Schepers (keesschepers.nl)
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
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
 * THE SOFTWARE.
 *
 * @category   PiKe
 * @copyright  Copyright (C) 2011 by Pieter Vogelaar (pietervogelaar.nl) and Kees Schepers (keesschepers.nl)
 * @package    Validate
 * @license    MIT
 */

/**
 * Test if a date is in a valid given range.
 *
 * Usage:
 * $element->addValidator(new Pike_Validate_DateRange(array('lt' => '2011-10-20')); //date should be less then 2011-10-20
 * $element->addValidator(new Pike_Validate_DateRange(array('gt' => '2011-10-20')); //date should be greater then 2011-10-20
 * $element->addValidator(new Pike_Validate_DateRange(array('lt' => '2011-09-20', 'gt' => '2011-10-20')); //date should be between 2011-09-20 AND  2011-10-20
 *
 * @category   PiKe
 * @copyright  Copyright (C) 2011 by Pieter Vogelaar (pietervogelaar.nl) and Kees Schepers (keesschepers.nl)
 * @package    Validate
 * @license    MIT
 */
class Pike_Validate_DateRange extends Zend_Validate_Abstract
{
    /**
     * Error codes
     *
     * @const string
     */
    const NOT_LATER = 'notLater';
    const NOT_EARLIER = 'notEarlier';
    const NOT_EARLIER_EQUAL = 'notEarlierEqual';
    const NOT_BETWEEN = 'notBetween';
    const INVALID_VALUE = 'invalidValue';
    const INVALID_TOKEN = 'invalidToken';

    /**
     * Error messages
     *
     * @var array
     */
    protected $_messageTemplates = array(
        self::NOT_BETWEEN => "The date '%value%' is not in the valid range",
        self::NOT_LATER => "The date '%value%' is not later than '%date%'",
        self::NOT_EARLIER => "The date '%value%' is not earlier than '%date%'",
        self::NOT_EARLIER_EQUAL => "The date '%value%' is not equal or earlier than '%date%'",
        self::INVALID_VALUE => "The date '%value%' is not valid",
        self::INVALID_TOKEN => "The date '%token%' is not valid"
    );

    /**
     * @var array
     */
    protected $_messageVariables = array('date' => '_inputValue');

    /**
     * @var Zend_Date
     */
    protected $lessThan;

    /**
     * Tells wether the value also allowed to be equal.
     *
     * @var boolean
     */
    protected $equals;

    /**
     * @var Zend_Date
     */
    protected $greaterThan;

    /**
     * @var string
     */
    protected $_inputValue;

    /**
     * @var string
     */
    protected $format = 'Y-M-d';

    /**
     * Sets validator options
     *
     * @param  mixed   $token
     * @param  boolean $compare
     * @param  string  $inputDateFormat
     */
    public function __construct(array $options)
    {
        foreach ($options as $key => $val) {
            switch ($key) {
                case 'lt' :
                case 'lessThan' :
                    $this->setDateOption('lessThan', $val);
                    break;
                case 'gt' :
                case 'greaterThan' :
                    $this->setDateOption('greaterThan', $val);
                    break;
                case 'eq' :
                case 'equals' :
                    $this->equals = (bool)$val;
                    break;
                case 'format' :
                    $this->format = $val;
                    break;
                default:
                    throw new \Pike_Exception('Unknown option');
            }
        }
    }

    public function setDateOption($type, $date)
    {
        if(!$date instanceof Zend_Date) {
            $timestamp = strtotime($date);

            if (false == $timestamp) {
                $this->_error(self::INVALID_VALUE);
            }

            $date = new Zend_Date();
            $date->setTimestamp($timestamp);
        }

        $date->setTime(array('hour' => 0, 'second' => 0));
        $this->$type = $date;
    }

    /**
     * Returns true if and only if a token has been set and the provided value matches that token.
     *
     * Defined by Zend_Validate_Interface
     *
     * @param  mixed $value
     * @return boolean
     */
    public function isValid($value, $context = null)
    {
        $this->_setValue((string) $value);

        $timestamp = strtotime($value);
        if (false == $value) {
            $this->_error(self::INVALID_VALUE);
            return false;
        }

        $valueDate = new Zend_Date();
        $valueDate->setTimestamp($timestamp);

        if ($this->lessThan instanceof Zend_Date && null === $this->greaterThan) {
            if (false === $valueDate->isEarlier($this->lessThan)) {

                if(true === $this->equals && 0 === $valueDate->compare($this->lessThan)) {
                    return true;
                }

                $this->_inputValue = $this->lessThan->toString($this->format);
                $this->_error(true === $this->equals ? self::NOT_EARLIER_EQUAL : self::NOT_EARLIER);
                return false;
            }
        } elseif ($this->greaterThan instanceof Zend_Date && null === $this->lessThan) {
            if (!$valueDate->isLater($this->greaterThan)) {

                if(true === $this->equals && 0 === $valueDate->compare($this->greaterThan)) {
                    return true;
                }

                $this->_inputValue = $this->greaterThan->toString($this->format);
                $this->_error(self::NOT_LATER);
                return false;
            }
        } elseif($this->greaterThan instanceof Zend_Date && $this->lessThan instanceof Zend_Date) {
            if ($valueDate->isEarlier($this->lessThan) || $valueDate->isLater($this->greaterThan)) {
                $this->_inputValue = $this->lessThan->toString($this->format) . ' and ' . $this->_inputValue = $this->greaterThan->toString($this->format);
                $this->_error(self::NOT_BETWEEN);
                return false;
            }
        }

        return true;
    }
}