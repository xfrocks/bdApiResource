<?php

class bdApiResource_XenResource_Model_ResourceField extends XFCP_bdApiResource_XenResource_Model_ResourceField
{
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

        if ($fieldValue !== null) {
            if (!empty($data['choices'])) {
                // choices
                if (is_array($field['field_value'])) {
                    // array
                    $fieldValueIds = array_keys($field['field_value']);
                } else {
                    // single
                    $fieldValueIds = $field['field_value'];
                }

                $data['value'] = $fieldValueIds;
            } else {
                // text
                $data['value'] = $field['field_value'];
            }
        }

        return $data;
    }
}