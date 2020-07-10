<?php

namespace Maze\MazeTv\Configuration;

use HelperForm;
use Tools;

class SettingsForm
{
    /** @var HelperForm */
    private $form;

    /** @var array */
    private $fieldsForm;

    /** @var SettingsFormFields */
    private $fields;

    private $isValidated;
    private $validationErrors;
    private $translator;

    public function __construct(HelperForm $form, $fieldsForm, SettingsFormFields $fields, $translator)
    {
        $this->form = $form;
        $this->fieldsForm = $fieldsForm;
        $this->fields = $fields;
        $this->translator = $translator;
    }

    public function save()
    {
        return $this->fields->updateFieldValues();
    }

    /**
     * @return bool
     */
    public function validate()
    {
        if ($this->isValidated) {
            return;
        }
        return $this->fields->validate($this->translator, $this->validationErrors);
    }

    /**
     * @return array
     */
    public function getValidationErrors()
    {
        return $this->validationErrors;
    }

    /**
     * @return string
     */
    public function getHtml()
    {
        return $this->form->generateForm($this->fieldsForm);
    }

    /**
     * @return bool
     */
    public function isSubmitAction()
    {
        return Tools::isSubmit($this->form->submit_action);
    }
}
