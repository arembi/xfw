<?php

namespace Arembi\Xfw\Inc;

class FormFieldSet {

    private $name;
    private $fields;
    private $label;


    public function __construct()
    {
        $this->fields([]);
    }


    public function name(?string $name = null) {
        if ($name === null) {
            return $this->name;
        } else {
            $this->name = $name;
            return $this;
        }
    }
    

    public function fields(?array $fields = null)
    {
        if ($fields === null) {
            return $this->fields ?? null;
        } else {
            $this->fields = $fields;
            return $this;
        }
    }


    public function field(string $name, FormField|FormFieldSet|null $field = null)
    {
        if ($field === null) {
            return $this->fields[$name] ?? null;
        } else {
            $this->fields[$name] = $field;
            return $this->fields[$name];
        }
    }


    public function label(?string $label = null)
    {
        if ($label === null) {
            return $this->label;
        }   else {
            $this->label = $label;
            return $this;
        }
    }


    public function addField(string $name, FormField|FormFieldSet $field): FormFieldSet
    {
        if ($this->field($name) === null) {
            $this
                ->field($name, $field)
                ->name($name);
        } else {
            Debug::alert('Form field ' . $name . ' in {' . $this->name . '} has already been set.', 'f');
        }
        return $this;
    }


    public function removeField(string $name): FormFieldSet
    {
        unset($this->fields[$name]);
        return $this;
    }
    

    public function generateTags(): FormFieldSet
	{
		foreach ($this->fields() as $field) {
			if (get_class($field) == 'Arembi\Xfw\FormFieldSet') {
				$field->generateTags();
			} else {
				$field->generateTag();
			}
		}
		return $this;
	}
}