<?php

namespace Arembi\Xfw\Inc;

class FormField {

	private $name;
	private $type;
	private $label;
	private $tag;
	private $attributes;
	private $options;
	private $text;


	public function __construct(
		string $name = '',
		string $type = '',
		string|array $label = '',
		string $tag = '',
		array $attributes = [],
		array $options = [],
		string $text = ''
	)
	{
		$this->name = $name;
		$this->type = $type;
		$this->label = $label;
		$this->tag = $tag;
		$this->attributes = $attributes;
		$this->options = $options;
		$this->text = $text;
	}


	public function name(?string $name = null): FormField|string
	{
		if ($name === null) {
			return $this->name;
		}
		$this->name = $name;
		return $this;
	}


	public function type(?string $type = null): FormField|string
	{
		if ($type === null) {
			return $this->type;
		}
		$this->type = $type;
		return $this;
	}


	public function label(string|array|null $label = null): FormField|array|string|null
	{
		if ($label === null) {
			return $this->label;
		}
		$this->label = $label;
		return $this;
	}


	public function tag(?string $tag = null): FormField|string
	{
		if ($tag === null) {
			return $this->tag;
		}
		$this->tag = $tag;
		return $this;
	}


	public function attributes(?array $attributes = null): FormField|array
	{
		if ($attributes === null) {
			return $this->attributes;
		}
		$this->attributes = $attributes;
		return $this;
	}


	public function attribute(string $attribute, ?string $value = null): FormField|string
	{
		if ($value === null) {
			return $this->attributes[$attribute];
		}
		$this->attributes[$attribute] = $value;
		return $this;
	}


	public function options(?array $options = null): FormField|array
	{
		if ($options === null) {
			return $this->options;
		}
		$this->options = $options;
		return $this;
	}


	public function option(?string $option = null, ?string $value = null): FormField|string
	{
		if ($value === null) {
			return $this->options[$option];
		}
		$this->options[$option] = $value;
		return $this;
	}


	public function text(string|array|null $text = null): Formfield|string|array
	{
		if ($text === null) {
			return $this->text;
		}
		$this->text = $text;
		return $this;
	}


	public function isFieldSet(): bool
	{
		return false;
	}


	public function generateTag(): FormField
	{
		$tag = '';

		// If an attribute is set to true, no value will be assigned
		// f.i. readonly

		if ($this->type() == 'select') {
			
			$tag = '<select name="' . $this->name() . '"';
			foreach ($this->attributes() as $attribute => $value) {
				$tag .= ' ' . $attribute;
				$tag .= $value !== true ? '="' . $value . '"' : '';
			}
			$tag .= '>';

			foreach ($this->options() as $option => $attributes) {
				$tag .= PHP_EOL . '<option';
				
				foreach ($attributes as $attribute => $value) {
					$tag .= ' ' . $attribute;
					if ($value !== true) {
						$tag .= '="' . $value . '"';
					}
				}

				$tag .= '>' . $option . '</option>';
			}
			$tag .= '</select>';

		} elseif ($this->type() == 'textarea') {
			
			$tag = '<textarea name="' . $this->name() . '"';
			foreach ($this->attributes() as $attribute => $value) {
				$tag .= ' ' . $attribute;
				if ($value !== true) {
					$tag .= '="' . $value . '"';
				}
			}
			$tag .= '>';
			$tag .= htmlspecialchars($this->text());
			$tag .= '</textarea>';
		
		} elseif ($this->type() == 'datalist') {
			
			$tag = '<datalist id ="' . $this->name() . '"';
			foreach ($this->attributes() as $attribute => $value) {
				$tag .= ' ' . $attribute;
				if ($value !== true) {
					$tag .= '="' . $value . '"';
				}
			}
			$tag .= '>';

			foreach ($this->options as $o) {
				$tag .= '<option'
					. (' value="' . $o['value'] . '"')
					. ((isset($o['label']) && $o['label']) ? ' label="' . $o['label'] . '"' : '')
					. '></option>';
			}
			$tag .= '</datalist>';
		
		} else {

			$tag = '<input type="' . $this->type() . '" name="' . $this->name() . '"';
			foreach ($this->attributes() as $attribute => $value) {
				$tag .= ' ' . $attribute;
				if ($value !== true) {
					$tag .= '="' . $value . '"';
				}
			}
			$tag .= '/>';
		}

		$this->tag($tag);
		return $this;
	}

}