<?php
namespace App\Core;

class Validator
{
    private $errors = [];
    private $data = [];

    public function validate($data, $rules)
    {
        $this->data = $data;
        $this->errors = [];

        foreach ($rules as $field => $ruleSet) {
            $rules = explode('|', $ruleSet);
            foreach ($rules as $rule) {
                $this->applyRule($field, $rule);
            }
        }

        return empty($this->errors);
    }

    private function applyRule($field, $rule)
    {
        $value = $this->data[$field] ?? null;

        // Required
        if ($rule === 'required') {
            if (empty($value) && $value !== '0') {
                $this->addError($field, 'This field is required');
            }
            return;
        }

        // Email
        if ($rule === 'email') {
            if (!empty($value) && !filter_var($value, FILTER_VALIDATE_EMAIL)) {
                $this->addError($field, 'Invalid email address');
            }
            return;
        }

        // Min length
        if (strpos($rule, 'min:') === 0) {
            $min = (int)substr($rule, 4);
            if (!empty($value) && strlen($value) < $min) {
                $this->addError($field, "Must be at least {$min} characters");
            }
            return;
        }

        // Max length
        if (strpos($rule, 'max:') === 0) {
            $max = (int)substr($rule, 4);
            if (!empty($value) && strlen($value) > $max) {
                $this->addError($field, "Must not exceed {$max} characters");
            }
            return;
        }

        // In array
        if (strpos($rule, 'in:') === 0) {
            $allowed = explode(',', substr($rule, 3));
            if (!empty($value) && !in_array($value, $allowed)) {
                $this->addError($field, 'Invalid value selected');
            }
            return;
        }

        // Numeric
        if ($rule === 'numeric') {
            if (!empty($value) && !is_numeric($value)) {
                $this->addError($field, 'Must be a number');
            }
            return;
        }

        // Date
        if ($rule === 'date') {
            if (!empty($value) && !strtotime($value)) {
                $this->addError($field, 'Invalid date format');
            }
            return;
        }
    }

    private function addError($field, $message)
    {
        if (!isset($this->errors[$field])) {
            $this->errors[$field] = [];
        }
        $this->errors[$field][] = $message;
    }

    public function getErrors()
    {
        return $this->errors;
    }

    public function getFirstError($field = null)
    {
        if ($field) {
            return $this->errors[$field][0] ?? null;
        }
        return reset($this->errors)[0] ?? null;
    }

    public function isValid()
    {
        return empty($this->errors);
    }
}