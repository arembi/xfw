<?php

namespace Arembi\Xfw\Module;

use Arembi\Xfw\Core\Debug;
use Arembi\Xfw\Core\Router;
use Arembi\Xfw\Inc\FormField;
use Arembi\Xfw\Inc\FormFieldSet;

class FormBase extends \Arembi\Xfw\Core\ModuleCore {

	protected static $hasModel = true;
	protected static $encTypes = [
		'application/x-www-form-urlencoded',
		'multipart/form-data',
		'text/plain'
	];

	private $fields;
	private $overrides;
	private $actionUrl;
	private $encType;
	private $hasFileField;

	
	public function init()
	{
		$this->loadModel();

		$this->fields = new FormFieldSet();
		$this->overrides = new FormFieldSet();
		$this->actionUrl = $this->params['actionUrl'] ?? '';
		$this->encType = $this->params['encType'] ?? 0;

		if (!empty($this->params['formId'])) {
			
			$form = $this->model->getForm($this->params['formId']);
			if (!$form) {
				return false;
			}
			
			if (isset($form['fields'])) {
				foreach ($form['fields'] as $k => $f) {
					$this->fields->addField($k, $this->arrayToFormField($f));
				}
			}

			if (!empty($form->action_url)) {
				$this->actionUrl = $form->action_url;
			}

			// Adding the non-optional formID hidden input
			$this->addField('formId', 'hidden')
				->attribute('value' , $this->params['formId']);
			
			$this->layout($form['options']['layout'] ?? $form['name']);
			$this->layoutVariant($form['options']['layoutVariant'] ?? $form['name']);

		} elseif (!empty($this->params['handlerModule']) && !empty($this->params['handlerMethod'])) { // Standard forms
			
			if (isset($this->params['fields'])) {
				foreach ($this->params['fields'] as $k => $f) {
					$this->fields->addField($k, $this->arrayToFormField($f));
				}
			}

			$this->addField('handlerModule', 'hidden')
				->attribute('value', $this->params['handlerModule']);
			
			$this->addField('handlerMethod', 'hidden')
				->attribute('value', $this->params['handlerMethod']);

			$this->actionUrl = $this->params['actionUrl'] ?? '';
			
			$this->layout($this->params['layout'] ?? $this->params['handlerMethod']);
			$this->layoutVariant($this->params['layoutVariant'] ?? $this->params['handlerMethod']);
		} else {
			return false;
		}
	}


	public function finalize()
	{
		$this->applyOverrides()
			->generateFieldTags();
		
		$actionUrl = $this->actionUrl ? Router::url($this->actionUrl) : '';
		
		if ($this->hasFileField) {
			$this->encType = 1;
		}

		$this->lv('enctype', self::$encTypes[$this->encType]);
		$this->lv('fields', $this->fields->fields());
		$this->lv('action', $actionUrl);

		return $this;
	}


	private function applyOverrides()
	{
		foreach ($this->overrides->fields() as $field => $override) {
			$overrideIsASet = get_class($override) == 'Arembi\Xfw\FormFieldSet';
			
			if ($this->fields->field($field) === null) {
				$this->fields->addField($field, $overrideIsASet ? new FormFieldSet() : new FormField());
			}
			
			$fieldIsASet = get_class($this->fields->field($field)) == 'Arembi\Xfw\FormFieldSet';

			if ($overrideIsASet) {
				if ($fieldIsASet) {
					$this->fields->field($field, $override)
						->label($override->label())	;
				} else {
					Debug::alert('Cannot overwrite FormField {' . $field . '} with a FormFieldSet', 'f');
				}
			} else {
				if ($fieldIsASet) {
					Debug::alert('Cannot overwrite FormFieldSet {' . $field . '} with a FormField', 'f');
				} else {
					$this->fields->field($field)
						->type($override->type())
						->label($override->label())
						->attributes(array_merge($this->fields->field($field)->attributes(), $override->attributes()))
						->text($override->text())
						->options(array_merge($this->fields->field($field)->options(), $override->options()));
				}
			}

		}
		return $this;
	}


	private function generateFieldTags(): FormBase
	{
		$this->fields->generateTags();
		return $this;
	}


	public function fields(?FormFieldSet $fields = null): FormBase|FormFieldSet
    {
        if ($fields === null) {
            return $this->fields;
        }
		
		$this->fields = $fields;
		return $this;
    }


	public function overrides(?FormFieldSet $overrides = null): FormBase|FormFieldSet
    {
        if ($overrides === null) {
            return $this->overrides;
        }
		
		$this->overrides = $overrides;
		return $this;
    }


	public function actionUrl(?string $url = null): FormBase|string
	{
		if ($url === null) {
			return $this->actionUrl;
		}
		
		$this->actionUrl = $url;
		return $this;
	}


	public function encType($encType = null)
	{
		if ($encType === null) {
			return $this->encType;
		} elseif (is_string($encType)) {
			$key = array_search($encType, self::$encTypes);
			if ($key !== false) {
				$this->encType = $key;
				return $this;
			} else {
				return false;
			}
		} elseif (is_int($encType) && in_array($encType, array_keys(self::$encTypes))) {
			$this->encType = $encType;
			return $this;
		} else {
			return false;
		}
	}


	public function addField(string $fieldName, string $type = 'text'): FormField
	{
		$field = new FormField();
		$field->type($type);
		
		$this->overrides->addField($fieldName, $field);
		if ($type == 'file') {
			$this->hasFileField = true;
		}
		return $field;
	}


	public function addFieldSet(string $fieldName): FormFieldSet
	{
		$fieldSet = new FormFieldSet();
		$this->overrides->addField($fieldName, $fieldSet);

		return $fieldSet;
	}


	public function arrayToFormField(array $arrayField): FormField
	{
		$field = new FormField();

		$field
			->type($arrayField['fieldType'] ?? 'text')
			->label($arrayField['label'] ?? '')
			->text($arrayField['text'] ?? '')
			->attributes($arrayField['attributes'] ?? [])
			->options($arrayField['options'] ?? []);
		
		return $field;
	}
}
