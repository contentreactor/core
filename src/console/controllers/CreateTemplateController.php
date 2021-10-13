<?php

namespace Developion\Core\console\controllers;

use Developion\Core\Core;
use Craft;
use craft\console\Controller;
use craft\helpers\FileHelper;
use craft\helpers\StringHelper;
use craft\services\Path;
use yii\console\ExitCode;

class CreateTemplateController extends Controller
{
    public bool $js = false;

    public string $type;

    private string $templateRoot = __DIR__ . '/../../templates';

    private string $assetRoot = __DIR__ . '/../../web/assets/front/src';

    public function options($actionID)
    {
        $options = parent::options($actionID);

        $options[] = 'js';
        $options[] = 'type';
        if ($actionID != '') $this->type = $actionID;
        return $options;
    }

    /**
     * Handle developion-core/create-template console commands
     *
     * The first line of this method docblock is displayed as the description
     * of the Console Command in ./craft help
     *
     * @return mixed
     */
    public function actionIndex(string $fileName)
    {
        if ($this->type == 'index' || empty($this->type)) {
            echo "Select a type of component";
            return;
        }

        $messages = [];

        $assetFileName = StringHelper::slugify($fileName);

        $twigPath = $this->templateRoot . '/_' . $this->type . 's/' . $fileName . '.twig';
        $cssPath = $this->assetRoot . '/scss/builder/' . $this->type . 's/' . $assetFileName . '.scss';
        $jsPath = $this->assetRoot . $this->type . 's/' . $assetFileName . '.js';


        if (!file_exists($twigPath)) {
            $twigContent = "\n\n";
            $twigContent .= "{% css view.assetManager.getPublishedUrl('@Developion/Core/web/assets/front/dist', false, 'builder/{$this->type}s/$assetFileName.css') %}";

            if ($this->js) {
                if (!file_exists($jsPath)) {
                    FileHelper::writeToFile($jsPath, '');
                    $twigContent .= "\n{% js  view.assetManager.getPublishedUrl('@Developion/Core/web/assets/front/dist', false, '$assetFileName.js') %}";
                } else {
                    $messages[] = " - Script file $assetFileName.js already exists.";
                }
            }

            if (!file_exists($cssPath)) {
                FileHelper::writeToFile($cssPath, '');
            } else {
                $messages[] = " - Style file $assetFileName.scss already exists.";
            }
            FileHelper::writeToFile($twigPath, $twigContent);
            $output = sprintf("%s template file %s.twig sucessfully generated.", ucfirst($this->type), $fileName);
            if (count($messages) > 0) $output .= " Some errors encountered: \n" . implode("\n", $messages);
        } else {
            $this->stdout(sprintf("%s template file %s.twig already exists.", ucfirst($this->type), $fileName));
            return ExitCode::CANTCREAT;
        }

        $this->stdout($output);
        return ExitCode::OK;
    }

    public function actionBlock(string $fileName)
    {
        $this->actionIndex($fileName);
    }

    public function actionComponent(string $fileName)
    {
        $this->actionIndex($fileName);
    }
}
