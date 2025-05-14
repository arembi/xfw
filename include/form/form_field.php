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


    public function __construct()
    {
        $this->name = '';
        $this->type = '';
        $this->label = '';
        $this->tag = '';
        $this->attributes = [];
        $this->options = [];
        $this->text = '';
    }


    public function name($name = null)
    {
        if ($name === null) {
            return $this->name;
        }   else {
            $this->name = $name;
            return $this;
        }
    }
    

    public function type($type = null)
    {
        if ($type === null) {
            return $this->type;
        }   else {
            $this->type = $type;
            return $this;
        }
    }


    public function label($label = null)
    {
        if ($label === null) {
            return $this->label;
        }   else {
            $this->label = $label;
            return $this;
        }
    }


    public function tag($tag = null)
    {
        if ($tag === null) {
            return $this->tag;
        }   else {
            $this->tag = $tag;
            return $this;
        }
    }


    public function attributes($attributes = null)
    {
        if ($attributes === null) {
            return $this->attributes;
        }   else {
            $this->attributes = $attributes;
            return $this;
        }
    }


    public function attribute($attribute, $value = null)
    {
        if ($value === null) {
            return $this->attributes[$attribute];
        }   else {
            $this->attributes[$attribute] = $value;
            return $this;
        }
    }


    public function options($options = null)
    {
        if ($options === null) {
            return $this->options;
        }   else {
            $this->options = $options;
            return $this;
        }
    }


    public function option($option = null, $value = null)
    {
        if ($option === null) {
            return $this->options[$option];
        }   else {
            $this->options[$option] = $value;
            return $this;
        }
    }
    

    public function text($text = null)
    {
        if ($text === null) {
            return $this->text;
        }   else {
            $this->text = $text;
            return $this;
        }
    }


    public function generateTag()
	{
		$tag = '';

		if ($this->type() == 'select') {
			// Case it is a <select> tag
			$tag = '<select name="' . $this->name() . '"';

			foreach ($this->attributes() as $attribute => $value) {
				$tag .= ' ' . $attribute . '="' . $value . '"';
			}

			$tag .= '>';

			foreach ($this->options() as $option => $attributes) {
				$tag .= PHP_EOL . '<option';
				
				foreach ($attributes as $attribute => $value) {
					$tag .= ' ' . $attribute;
					// If an attribute is set to true, no value will be assigned
					// f.i. readonly
					if ($value !== true) {
						$tag .= '="' . $value . '"';
					}
				}

				$tag .= '>' . $option . '</option>';
			}

			$tag .= '</select>';

		} elseif ($this->type() == 'textarea') {
			// Case it is a <textarea> tag
			$tag = '<textarea name="' . $this->name() . '"';

			foreach ($this->attributes() as $attribute => $value) {
				$tag .= ' ' . $attribute;
				// If an attribute is set to true, no value will be assigned
				// f.i. readonly
				if ($value !== true) {
					$tag .= '="' . $value . '"';
				}
			}

			$tag .= '>';

			$tag .= htmlspecialchars($this->text());

			$tag .= '</textarea>';

		} else {
			// Case it is an <input> tag
			$tag = '<input type="' . $this->type() . '" name="' . $this->name() . '"';

			foreach ($this->attributes() as $attribute => $value) {
				$tag .= ' ' . $attribute;
				// If an attribute is set to true, no value will be assigned
				// f.i. readonly
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