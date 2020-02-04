<?php
/**
 * More Fit Images plugin for Craft CMS 3.x
 *
 * Fixes a bug in Craft 3.4 where thumbs in the Control Panel were cropped instead of fitted.
 *
 * @link      https://www.vaersaagod.no/
 * @copyright Copyright (c) 2020 André Elvan
 */

namespace aelvan\morefitimages;


use Craft;
use craft\base\Plugin;
use craft\errors\ImageException;
use craft\events\AssetThumbEvent;
use craft\events\TemplateEvent;
use craft\helpers\FileHelper;
use craft\helpers\Image;
use craft\services\Assets;
use craft\services\Plugins;
use craft\events\PluginEvent;

use craft\web\View;
use aelvan\morefitimages\MoreFitImagesAssetBundle;
use yii\base\Event;

/**
 * Class MoreFitImages
 *
 * @author    André Elvan
 * @package   MoreFitImages
 * @since     1.0.0
 *
 */
class MoreFitImages extends Plugin
{
    // Static Properties
    // =========================================================================

    /**
     * @var MoreFitImages
     */
    public static $plugin;

    // Public Properties
    // =========================================================================

    /**
     * @var string
     */
    public $schemaVersion = '1.0.0';

    // Public Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    public function init()
    {
        parent::init();
        self::$plugin = $this;

        // Register alias
        Craft::setAlias('@aelvan/more-fit-images', __DIR__);

        $request = Craft::$app->getRequest();

        if ($request->getIsCpRequest() && !$request->getIsConsoleRequest()) {
            Event::on(
                View::class,
                View::EVENT_BEFORE_RENDER_TEMPLATE,
                static function (TemplateEvent $event) {
                    try {
                        Craft::$app->getView()->registerAssetBundle(MoreFitImagesAssetBundle::class);
                    } catch (InvalidConfigException $e) {
                        Craft::error(
                            'Error registering AssetBundle - ' . $e->getMessage(),
                            __METHOD__
                        );
                    }
                }
            );


            Event::on(Assets::class, Assets::EVENT_GET_THUMB_PATH,
                static function (AssetThumbEvent $event) {
                    if ($event->asset !== null) {
                        $asset = $event->asset;
                        $width = $event->width;
                        $height = $event->height;
                        $generate = $event->generate;

                        $ext = $asset->getExtension();
                        
                        if (Image::canManipulateAsImage($ext) && in_array($ext, Image::webSafeFormats(), true)) {
                            $dir = Craft::$app->getPath()->getAssetThumbsPath() . DIRECTORY_SEPARATOR . $asset->id;
                            $path = $dir . DIRECTORY_SEPARATOR . "thumb-{$width}x{$height}.{$ext}";
                            
                            if (!file_exists($path) || $asset->dateModified->getTimestamp() > filemtime($path)) {
                                if (!$generate) {
                                    return false;
                                }

                                // Generate it
                                if (!file_exists($dir)) {
                                    FileHelper::createDirectory($dir);
                                }
                                
                                $imageSource = Craft::$app->getAssetTransforms()->getLocalImageSource($asset);
                                $svgSize = max($width, $height);

                                // "Hail Mary" - Andris
                                try {
                                    $image = Craft::$app->getImages()->loadImage($imageSource, false, $svgSize);

                                    // Prevent resize of all layers
                                    if ($image instanceof Raster) {
                                        $image->disableAnimation();
                                    }

                                    $image->scaleToFit($width, $height);
                                    $image->saveAs($path);

                                    $event->path = $path;
                                } catch (ImageException $exception) {
                                    Craft::warning($exception->getMessage());
                                    //$event->path = $this->getIconPath($asset);
                                }
                            }
                        }
                    }
                }
            );
        }

    }

    // Protected Methods
    // =========================================================================

}
