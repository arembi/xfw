<?php

namespace Arembi\Xfw\Inc;

class FormFieldSet {

	private $name;
	private $fields;
	private $label;


	public function __construct(
		string $name = '',
		array $fields = [],
		string $label = ''
	)
	{
		$this->name($name);
		$this->fields($fields);
		$this->label($label);
	}


	public function name(?string $name = null): FormFieldSet|string
	{
		if ($name === null) {
			return $this->name;
		}
		$this->name = $name;
		return $this;
	}
	

	public function fields(?array $fields = null): FormFieldSet|array|null
	{
		if ($fields === null) {
			return $this->fields ?? null;
		}
		$this->fields = $fields;
		return $this;
	}


	public function field(string $name, FormField|FormFieldSet|Datalist|null $field = null): FormField|FormFieldSet|Datalist|null
	{
		if ($field === null) {
			return $this->fields[$name] ?? null;
		}
		$this->fields[$name] = $field;
		return $this->fields[$name];
	}


	public function label(string|array|null $label = null): FormFieldSet|array|string|null
	{
		if ($label === null) {
			return $this->label;
		}
		$this->label = $label;
		return $this;
	}


	public function addField(string $name, FormField|FormFieldSet $field): FormField|null
	{
		if ($this->field($name) === null) {
			$newField = $this
				->field($name, $field)
				->name($name);
			return $newField;
		} else {
			Debug::alert('Form field ' . $name . ' in {' . $this->name . '} has already been set.', 'f');
			return null;
		}
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