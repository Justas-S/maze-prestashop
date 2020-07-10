<?php

namespace Maze\MazeTv\Configuration;

use AdminController;
use Configuration;
use HelperForm;
use Module;
use Tools;

class SettingsFormBuilder
{
    /**
     * @var SettingsFormFields
     */
    private $fields;

    public function __construct(SettingsFormFields $fields)
    {
        $this->fields = $fields;
    }

    public function build(Module $module)
    {
        $defaultLang = (int) Configuration::get('PS_LANG_DEFAULT');
        $translator = $module->getTranslator();

        $helper = new HelperForm();

        // Module, token and currentIndex
        $helper->module = $module;
        $helper->name_controller = $module->name;
        $helper->token = Tools::getAdminTokenLite('AdminModules');
        $helper->currentIndex = AdminController::$currentIndex . '&configure=' . $module->name;

        // Language
        $helper->default_form_language = $defaultLang;
        $helper->allow_employee_form_lang = $defaultLang;

        // Title and toolbar
        $helper->title = $module->displayName;
        $helper->show_toolbar = true;        // false -> remove toolbar
        $helper->toolbar_scroll = true;      // yes - > Toolbar is always visible on the top of the screen.
        $helper->submit_action = 'submit' . $module->name;
        $helper->toolbar_btn = [
            'save' => [
                'desc' => $translator->trans('Save', [], 'Modules.Mazetv.mazetv'),
                'href' => AdminController::$currentIndex . '&configure=' . $module->name . '&save' . $module->name .
                    '&token=' . Tools::getAdminTokenLite('AdminModules'),
            ],
            'back' => [
                'href' => AdminController::$currentIndex . '&token=' . Tools::getAdminTokenLite('AdminModules'),
                'desc' => $translator->trans('Back to list', [], 'Modules.Mazetv.mazetv')
            ]
        ];

        $helper->fields_value = array_merge($helper->fields_value, $this->fields->getFieldValues());

        // Init Fields form array
        $fieldsForm[0]['form'] = [
            'legend' => [
                'title' => $translator->trans('Settings', [], 'Modules.Mazetv.mazetv'),
            ],
            'input' => $this->fields->getFields($translator),
            'submit' => [
                'title' => $translator->trans('Save', [], 'Modules.Mazetv.mazetv'),
                'class' => 'btn btn-default pull-right'
            ]
        ];

        return new SettingsForm($helper, $fieldsForm, $this->fields, $translator);
    }
}
