<?php

class bdApiResource_XenResource_Model_ResourceField extends XFCP_bdApiResource_XenResource_Model_ResourceField
{
    public function bdApiResource_getResourceFields()
    {
        static $fields = null;

        if ($fields === null) {
            $fields = $this->getResourceFields();
        }

        return $fields;
    }

    public function prepareApiDataForField(array $field, $fieldValue = null)
    {
        $field = $this->prepareResourceField($field, true, $fieldValue);
        $data = array();

        $publicKeys = array(
            'title' => 'title',
            'description' => 'description',
        );
        if (!empty($field['isChoice'])) {
            $publicKeys['fieldChoices'] = 'choices';
            $data['is_multi_choice'] = !empty($field['isMultiChoice']);
        }

        $data += bdApi_Data_Helper_Core::filter($field, $publicKeys);
        $data['is_required'] = !empty($field['required']);

        if (!empty($data['choices'])) {
            $choices = array();
            foreach ($data['choices'] as $key => $value) {
                $choices[] = array(
                    'key' => strval($key),
                    'value' => strval($value),
                );
            }
            $data['choices'] = $choices;
        }

        if ($fieldValue !== null) {
            if (!empty($data['choices'])) {
                // choices
                if (is_array($field['field_value'])) {
                    // array
                    $fieldValueIds = array_keys($field['field_value']);
                } else {
                    // single
                    $fieldValueIds = array($field['field_value']);
                }

                $data['values'] = array();
                foreach ($fieldValueIds as $fieldValueId) {
                    $choiceValue = null;
                    foreach ($data['choices'] as $choice) {
                        if ($choice['key'] == $fieldValueId) {
                            $choiceValue = $choice['value'];
                        }
                    }

                    $data['values'][] = array(
                        'key' => strval($fieldValueId),
                        'value' => $choiceValue,
                    );
                }
            } else {
                // text
                $data['value'] = $field['field_value'];
            }
        }

        return $data;
    }
}