<?php

namespace Developion\Core\fields;

use Craft;
use craft\fields\Matrix;
use craft\helpers\ArrayHelper;

class InceptionMatrix extends Matrix
{

    /**
     * @inheritdoc
     */
    public static function displayName(): string
    {
        return Craft::t('app', 'Inception');
    }

    /**
     * Returns info about each field type for the configurator.
     *
     * @return array
     */
    private function _getFieldOptionsForConfigurator(): array
    {
        $fieldTypes = [];

        foreach (Craft::$app->getFields()->getAllFieldTypes() as $class) {
            $fieldTypes[] = [
                'type' => $class,
                'name' => $class::displayName(),
            ];
        }

        // Sort them by name
        ArrayHelper::multisort($fieldTypes, 'name');

        return $fieldTypes;
    }
}
