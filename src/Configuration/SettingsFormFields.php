<?php

namespace Maze\MazeTv\Configuration;

use Configuration;
use Tools;
use Validate;

class SettingsFormFields
{

    const FIELD_PREFIX = 'mazetv_';

    public function getFieldNames()
    {
        return [
            self::FIELD_PREFIX . 'authentication_id',
            self::FIELD_PREFIX . 'authentication_key',
        ];
    }

    /**
     * For field params @see HelperForm::generate
     */
    public function getFields($translator)
    {
        return [
            [
                'type' => 'text',
                'label' => $translator->trans('MazeTV authentication id', [], 'Modules.Mazetv.mazetv'),
                'hint'  => $translator->trans('Contact dev@maze.lt for access', [], 'Modules.Mazetv.mazetv'),
                'name' => self::FIELD_PREFIX . 'authentication_id',
                'size' => 30,
                'required' => true
            ],
            [
                'type' => 'text',
                'label' => $translator->trans('MazeTV authentication key', [], 'Modules.Mazetv.mazetv'),
                'hint'  => $translator->trans('Contact dev@maze.lt for access', [], 'Modules.Mazetv.mazetv'),
                'name' => self::FIELD_PREFIX . 'authentication_key',
                'size' => 30,
                'required' => true
            ],

        ];
    }

    public function getFieldValues()
    {
        $values = [];
        foreach ($this->getFieldNames() as $field) {
            $values[$field] = Tools::getValue($field, Configuration::get($field));
        }
        return $values;
    }

    public function updateFieldValues()
    {
        $authId = $this->getStringFromForm(self::FIELD_PREFIX . 'authentication_id');
        $authKey = $this->getStringFromForm(self::FIELD_PREFIX . 'authentication_key');
        return
            Configuration::updateValue(self::FIELD_PREFIX . 'authentication_key', $authKey)
            && Configuration::updateValue(self::FIELD_PREFIX . 'authentication_id', $authId);
    }

    public function validate($translator, &$errors = [])
    {
        $errors = [];
        $authId = $this->getStringFromForm(self::FIELD_PREFIX . 'authentication_id');
        $authKey = $this->getStringFromForm(self::FIELD_PREFIX . 'authentication_key');
        if (!Validate::isString($authKey) || empty($authKey)) {
            $errors[self::FIELD_PREFIX . 'authentication_id'] = $translator->trans(
                '%field% value is invalid',
                ['%field%' => $translator->trans('MazeTV authentication id', [], 'Modules.Mazetv.mazetv')],
                'Modules.Mazetv.mazetv'
            );
        }
        if (!Validate::isString($authKey) || empty($authKey)) {
            $errors[self::FIELD_PREFIX . 'authentication_key'] = $translator->trans(
                '%field% value is invalid',
                ['%field%' => $translator->trans('MazeTV authentiaction key', [], 'Modules.Mazetv.mazetv')],
                'Modules.Mazetv.mazetv'
            );
        }


        return empty($errors);
    }

    private function getStringFromForm($field)
    {
        return strval(Tools::getValue($field));
    }
}
