<?php

/* 
 * Attributes can be overridden in the controllers
 * */
namespace Arembi\Xfw\Module;

use Arembi\Xfw\Core\Debug;
use Arembi\Xfw\FormField;
use Arembi\Xfw\FormFieldSet;

class FormBase extends \Arembi\Xfw\Core\ModuleCore {

	protected static $hasModel = true;

	private $overrides;
	private $fields;
	private $actionUrl;

	
	public function main(&$options)
	{
		$this->loadModel();

		$this->fields = new FormFieldSet();
		$this->overrides = new FormFieldSet();
		$this->actionUrl = '';

		if (!empty($options['id'])) { // User generated forms
			
			$form = $this->model->getForm($options['id']);
			if (!$form) {
				return false;
			}
			
			if (isset($form['fields'])) {
				foreach ($form['fields'] as $k => $f) {
					$this->fields->addField($k, $this->arrayToFormField($f));
				}
			}

			$this->actionUrl = $form->action_url ?? '';

			// Adding the non-optional formID hidden input
			$this->addField('formId', 'hidden')
				->attribute('value' , $options['id']);
			
			$options['layout'] = $form['options']['layout'] ?? $form['name'];
			$options['layoutVariant'] = $form['options']['layoutVariant'] ?? $form['name'];

		} elseif (!empty($options['handlerModule']) && !empty($options['handlerMethod'])) { // Standard forms
			
			if (isset($options['fields'])) {
				foreach ($options['fields'] as $k => $f) {
					$this->fields->addField($k, $this->arrayToFormField($f));
				}
			}

			$this->addField('handlerModule', 'hidden')
				->attribute('value', $options['handlerModule']);
			
			$this->addField('handlerMethod', 'hidden')
				->attribute('value', $options['handlerMethod']);

			$this->actionUrl = $options['actionUrl'] ?? '';
			
			$options['layout'] = $options['layout'] ?? $options['handlerMethod'];
			$options['layoutVariant'] = $options['layoutVariant'] ?? $options['handlerMethod'];
		} else {
			return false;
		}

		if (empty($options['autoBuild']) || $options['autoBuild'] === true) {
			$this->build();
		}
	}


	public function build()
	{
		$this->applyOverrides();
		$this->fields->generateTags();

		if (!empty($this->actionUrl)) {
			$actionLink = new Link(['href'=>$this->actionUrl]);
			$actionUrl = $actionLink->getHref();
		} else {
			$actionUrl = '';
		}

		$this->lv('fields', $this->fields->fields());
		$this->lv('action', $actionUrl);

		return $this;
	}


	private function applyOverrides()
	{
		foreach ($this->overrides->fields() as $field => $override) {
			$overrideIsSet = get_class($override) == 'Arembi\Xfw\FormFieldSet';
			
			if ($this->fields->field($field) === null) {
				$this->fields->addField($field, $overrideIsSet ? new FormFieldSet() : new FormField());
			}
			
			$fieldIsSet = get_class($this->fields->field($field)) == 'Arembi\Xfw\FormFieldSet';

			if ($overrideIsSet) {
				if ($fieldIsSet) {
					$this->fields->field($field, $override)
						->label($override->label())	;
				} else {
					Debug::alert('Cannot overwrite FormField {' . $field . '} with a FormFieldSet', 'f');
				}
			} else {
				if ($fieldIsSet) {
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


	public function fields(FormFieldSet $fields = null)
    {
        if ($fields === null) {
            return $this->fields ?? null;
        } else {
            $this->fields = $fields;
            return $this;
        }
    }


	public function overrides(FormFieldSet $overrides = null)
    {
        if ($overrides === null) {
            return $this->overrides ?? null;
        } else {
            $this->overrides = $overrides;
            return $this;
        }
    }


	public function actionUrl(?string $url = null)
	{
		if ($url === null) {
			return $this->actionUrl;
		} else {
			$this->actionUrl = $url;
			return $this;
		}
	}


	public function addField(string $fieldName, string $type = 'text'): FormField
	{
		
		$field = new FormField();
		$field->type($type);

		$this->overrides->addField($fieldName, $field);

		return $field;
	}


	public function addFieldSet($fieldName): FormFieldSet
	{
		$fieldSet = new FormFieldSet();
		
		$this->overrides->addField($fieldName, $fieldSet);

		return $fieldSet;
	}


	public function arrayToFormField(array $arrayField) : FormField
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
