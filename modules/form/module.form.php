<?php

/* Attributes can be overridden in the controllers
 * If you want to use a dynamically created form inputs, you can use the
 * */
namespace Arembi\Xfw\Module;

use Arembi\Xfw\Core\Debug;

class FormBase extends \Arembi\Xfw\Core\ModuleCore {

	private $overrides = [];
	private $fields = [];
	private $actionUrl = '';


	public function main(&$options, $autoBuild = true)
	{
		$this->loadModel();

		if (!empty($options['ID'])) {
			// User generated forms

			$form = $this->model->getForm($options['ID']);
			if (!$form) {
				return false;
			}
			$options['layout'] = $form['name'];
			$this->fields = $form['fields'];

			$this->actionUrl = $form->action_url ?? '';

			// Adding the non-optional formID hidden input
			$this->addField('formID', 'hidden');
			$this->setFieldAttribute('formID', 'value' , $options['ID']);
		} elseif (!empty($options['handlerModule']) && !empty($options['handlerMethod'])) {
			// Standard forms

			$options['layout'] = $options['handlerMethod'];

			$this->fields = $options['fields'] ?? [];

			$this->addField('handlerModule', 'hidden');
			$this->addField('handlerMethod', 'hidden');
			$this->setFieldAttribute('handlerModule', 'value', $options['handlerModule']);
			$this->setFieldAttribute('handlerMethod', 'value', $options['handlerMethod']);

			$this->actionUrl = $options['actionUrl'] ?? '';
		} else {
			return false;
		}

		if ($autoBuild === true) {
			$this->build();
		}
	}


	public function build()
	{
		// Applying overrides
		foreach ($this->overrides as $field => $override) {
			if (isset($override['fieldType'])) {
				$this->fields[$field]['fieldType'] = $override['fieldType'];
			}

			if (isset($override['label'])) {
				$this->fields[$field]['label'] = $override['label'];
			}

			if (isset($override['attributes'])) {
				$this->fields[$field]['attributes'] = isset($this->fields[$field]['attributes'])
					? array_merge($this->fields[$field]['attributes'], $override['attributes'])
					: $override['attributes'];
			}

			if (isset($override['text'])) {
				$this->fields[$field]['text'] = $override['text'];
			}

			if (isset($override['options'])) {
				$this->fields[$field]['options'] = isset($this->fields[$field]['options'])
					? array_merge($this->fields[$field]['options'], $override['options'])
					: $override['options'];
			}
		}

		foreach ($this->fields as $fieldName => $fieldParams) {
			// Assigning field label to the template
			$this->fields[$fieldName]['label'] = isset($fieldParams['label']) ? $fieldParams['label'] : '';

			// Assigning field tag to the template
			if ($fieldParams['fieldType'] == 'select') {
				// Case it is a <select> tag
				$this->fields[$fieldName]['tag'] = '<select name="' . $fieldName . '"';

				if (isset($fieldParams['attributes'])) {
					foreach ($fieldParams['attributes'] as $attribute => $value) {
						$this->fields[$fieldName]['tag'] .= ' ' . $attribute . '="' . $value . '"';
					}
				}
				$this->fields[$fieldName]['tag'] .= '>';

				foreach ($fieldParams['options'] as $option => $attributes) {
					$this->fields[$fieldName]['tag'] .= PHP_EOL . '<option';
					foreach ($attributes as $attribute => $value) {
						$this->fields[$fieldName]['tag'] .= ' ' . $attribute;
						// If an attribute is set to true, no value will be assigned
						// f.i. readonly
						if ($value !== true) {
							$this->fields[$fieldName]['tag'] .= '="' . $value . '"';
						}
					}
					$this->fields[$fieldName]['tag'] .= '>' . $option . '</option>';
				}

				$this->fields[$fieldName]['tag'] .= '</select>';
			} elseif ($fieldParams['fieldType'] == 'textarea') {
				// Case it is a <textarea> tag
				$this->fields[$fieldName]['tag'] = '<textarea name="' . $fieldName . '"';

				if (isset($fieldParams['attributes'])) {
					foreach ($fieldParams['attributes'] as $attribute => $value) {
						$this->fields[$fieldName]['tag'] .= ' ' . $attribute;
						// If an attribute is set to true, no value will be assigned
						// f.i. readonly
						if ($value !== true) {
							$this->fields[$fieldName]['tag'] .= '="' . $value . '"';
						}
					}
				}
				$this->fields[$fieldName]['tag'] .= '>';

				if (isset($this->fields[$fieldName]['text'])) {
					$this->fields[$fieldName]['tag'] .= htmlspecialchars($this->fields[$fieldName]['text']);
				}
				$this->fields[$fieldName]['tag'] .= '</textarea>';
			} else {
				// Case it is an <input> tag
				$this->fields[$fieldName]['tag'] = '<input type="' . $fieldParams['fieldType'] . '" name="' . $fieldName . '"';

				if (isset($fieldParams['attributes'])) {
					foreach ($fieldParams['attributes'] as $attribute => $value) {
						$this->fields[$fieldName]['tag'] .= ' ' . $attribute;
						// If an attribute is set to true, no value will be assigned
						// f.i. readonly
						if ($value !== true) {
							$this->fields[$fieldName]['tag'] .= '="' . $value . '"';
						}
					}
				}

				$this->fields[$fieldName]['tag'] .= '/>';
			}
		}

		if (!empty($this->actionUrl)) {
			$actionLink = new Link(['href'=>$this->actionUrl]);
			$actionUrl = $actionLink->getHref();
		} else {
			$actionUrl = '';
		}


		$this->lv('fields', $this->fields);
		$this->lv('action', $actionUrl);
	}


	public function addField($field, $type = 'text')
	{
		$this->overrides[$field]['fieldType'] = $type;
		return $this;
	}


	public function setFieldType($field, $type)
	{
		if (isset($this->overrides[$field])) {
			$this->overrides[$field]['fieldType'] = $type;
		}
		return $this;
	}


	public function setFieldLabel($field, $label)
	{
		if (isset($this->overrides[$field])) {
			$this->overrides[$field]['label'] = $label;
		}
		return $this;
	}


	public function setFieldAttribute($field, $attribute, $value)
	{
		$this->overrides[$field]['attributes'][$attribute] = $value;
		return $this;
	}


	// Attributes can be passed as an array with keys = attribute names
	// and values = attribute values
	public function setFieldAttributes($field, $attributes)
	{
		$this->overrides[$field]['attributes'] = $attributes;
		return $this;
	}


	public function setFieldText($field, $text)
	{
		$this->overrides[$field]['text'] = $text;
		return $this;
	}


	/*
	TODO: implement support for datalists
	*/
	public function setFieldOptions($field, $options)
	{
		if ((isset($this->overrides[$field]['fieldType']) && $this->overrides[$field]['fieldType'] == 'select')
			|| (isset($this->fields[$field]) && $this->fields[$field] == 'select')) {
			$this->overrides[$field]['options'] = $options;
		} else {
			Debug::alert('Trying to set options for a non-select form field', 'w');
		}
		return $this;
	}


	public function getActionUrl()
	{
		return $this->actionUrl;
	}


	public function setActionUrl(string $url)
	{
		$this->actionUrl = $url;
		return $this;
	}
}
